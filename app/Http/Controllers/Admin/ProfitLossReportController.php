<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $this->validatedFilters($request);

        $from = Carbon::createFromFormat('Y-m-d', $validated['from'] ?? now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $validated['to'] ?? now()->format('Y-m-d'))->endOfDay();
        $status = $validated['status'] ?? 'delivered';

        $orders = $this->reportOrders($from, $to, $status);
        $summary = $this->summary($orders);

        if ($request->query('export') === 'csv') {
            return $this->csvResponse($orders, $summary, $from, $to, $status);
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $paginatedOrders = new LengthAwarePaginator(
            $orders->forPage($page, $perPage)->values(),
            $orders->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.reports.profit-loss', compact(
            'from',
            'to',
            'status',
            'summary',
            'paginatedOrders'
        ));
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in(['delivered', 'all'])],
            'export' => ['nullable', Rule::in(['csv'])],
        ]);
    }

    private function reportOrders(Carbon $from, Carbon $to, string $status): Collection
    {
        return Order::query()
            ->with(['items.product:id,purchase_price', 'items.productVariant:id,purchase_price'])
            ->whereBetween('created_at', [$from, $to])
            ->when(
                $status !== 'all',
                fn ($query) => $query->whereIn('order_status', ['delivered', 'partial_delivered'])
            )
            ->latest('created_at')
            ->get()
            ->map(fn (Order $order) => $this->withReportTotals($order));
    }

    private function withReportTotals(Order $order): Order
    {
        $cost = $order->items->sum(function ($item) {
            $unitCost = $item->purchase_price
                ?? $item->productVariant?->purchase_price
                ?? $item->product?->purchase_price
                ?? 0;

            return (float) $unitCost * $item->activeQuantity();
        });

        $productSales = max(0, $order->displaySubtotal('active') - $order->displayDiscount('active'));
        $shippingCollected = $order->displayShippingCost('active');
        $taxCollected = $order->displayTax('active');
        $revenue = $productSales + $shippingCollected;
        $steadfastDeliveryCharges = (float) $order->steadfast_delivery_charges;
        $steadfastCodCharge = (float) $order->steadfast_cod_charger;
        $courierCharges = $steadfastDeliveryCharges + $steadfastCodCharge;

        $order->report_units = $order->items->sum(fn ($item) => $item->activeQuantity());
        $order->report_product_sales = $productSales;
        $order->report_shipping_collected = $shippingCollected;
        $order->report_tax_collected = $taxCollected;
        $order->report_net_sales = $revenue;
        $order->report_cost = $cost;
        $order->report_steadfast_delivery_charges = $steadfastDeliveryCharges;
        $order->report_steadfast_cod_charger = $steadfastCodCharge;
        $order->report_courier_charges = $courierCharges;
        $order->report_profit = $revenue - $cost - $courierCharges;

        return $order;
    }

    private function summary(Collection $orders): object
    {
        return (object) [
            'orders' => $orders->count(),
            'units' => $orders->sum('report_units'),
            'gross_sales' => $orders->sum(fn (Order $order) => $order->displaySubtotal('active')),
            'discounts' => $orders->sum(fn (Order $order) => $order->displayDiscount('active')),
            'product_sales' => $orders->sum('report_product_sales'),
            'net_sales' => $orders->sum('report_net_sales'),
            'cost' => $orders->sum('report_cost'),
            'steadfast_delivery_charges' => $orders->sum('report_steadfast_delivery_charges'),
            'steadfast_cod_charger' => $orders->sum('report_steadfast_cod_charger'),
            'courier_charges' => $orders->sum('report_courier_charges'),
            'profit' => $orders->sum('report_profit'),
            'shipping_collected' => $orders->sum('report_shipping_collected'),
            'tax_collected' => $orders->sum('report_tax_collected'),
        ];
    }

    private function csvResponse(Collection $orders, object $summary, Carbon $from, Carbon $to, string $status): StreamedResponse
    {
        $filename = sprintf('profit-loss-report-%s-to-%s.csv', $from->format('Y-m-d'), $to->format('Y-m-d'));

        return response()->streamDownload(function () use ($orders, $summary, $from, $to, $status) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Profit / Loss Report']);
            fputcsv($handle, ['From', $from->format('Y-m-d')]);
            fputcsv($handle, ['To', $to->format('Y-m-d')]);
            fputcsv($handle, ['Status', $status === 'all' ? 'All orders' : 'Delivered & partial delivered']);
            fputcsv($handle, []);
            fputcsv($handle, [
                'Orders',
                'Units',
                'Gross Sales',
                'Discounts',
                'Product Sales',
                'Shipping Collected',
                'Tax Collected',
                'Revenue',
                'Purchase Price',
                'Delivery Charge',
                'COD Charge',
                'Courier Charges',
                'Profit / Loss',
            ]);
            fputcsv($handle, [
                $summary->orders,
                $summary->units,
                $this->moneyForCsv($summary->gross_sales),
                $this->moneyForCsv($summary->discounts),
                $this->moneyForCsv($summary->product_sales),
                $this->moneyForCsv($summary->shipping_collected),
                $this->moneyForCsv($summary->tax_collected),
                $this->moneyForCsv($summary->net_sales),
                $this->moneyForCsv($summary->cost),
                $this->moneyForCsv($summary->steadfast_delivery_charges),
                $this->moneyForCsv($summary->steadfast_cod_charger),
                $this->moneyForCsv($summary->courier_charges),
                $this->moneyForCsv($summary->profit),
            ]);
            fputcsv($handle, []);
            fputcsv($handle, [
                'Order',
                'Date',
                'Status',
                'Units',
                'Gross Sales',
                'Discounts',
                'Product Sales',
                'Shipping Collected',
                'Tax Collected',
                'Revenue',
                'Purchase Price',
                'Delivery Charge',
                'COD Charge',
                'Courier Charges',
                'Profit / Loss',
            ]);

            $orders->each(function (Order $order) use ($handle) {
                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->order_status,
                    $order->report_units,
                    $this->moneyForCsv($order->displaySubtotal('active')),
                    $this->moneyForCsv($order->displayDiscount('active')),
                    $this->moneyForCsv($order->report_product_sales),
                    $this->moneyForCsv($order->report_shipping_collected),
                    $this->moneyForCsv($order->report_tax_collected),
                    $this->moneyForCsv($order->report_net_sales),
                    $this->moneyForCsv($order->report_cost),
                    $this->moneyForCsv($order->report_steadfast_delivery_charges),
                    $this->moneyForCsv($order->report_steadfast_cod_charger),
                    $this->moneyForCsv($order->report_courier_charges),
                    $this->moneyForCsv($order->report_profit),
                ]);
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function moneyForCsv(float|int $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
