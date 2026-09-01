<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TotalOrderReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $validated['from'] ?? now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $validated['to'] ?? now()->format('Y-m-d'))->endOfDay();

        $orders = Order::query()
            ->with(['items:id,order_id,quantity,restocked_quantity'])
            ->whereBetween('created_at', [$from, $to])
            ->oldest('created_at')
            ->get()
            ->map(function (Order $order) {
                $order->report_units = $order->items->sum('quantity');
                $order->report_is_delivered = in_array($order->order_status, ['delivered', 'partial_delivered'], true);
                $order->report_is_cancelled = $order->order_status === 'cancelled';
                $order->report_returned_quantity = $order->items->sum('restocked_quantity');

                return $order;
            });

        $ordersByDate = $orders->groupBy(fn (Order $order) => $order->created_at->format('Y-m-d'));

        $dailyReport = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(function (Carbon $date) use ($ordersByDate) {
                $dayOrders = $ordersByDate->get($date->format('Y-m-d'), collect());

                return (object) [
                    'date' => $date->copy(),
                    'orders' => $dayOrders->count(),
                    'units' => $dayOrders->sum('report_units'),
                    'delivered' => $dayOrders->where('report_is_delivered', true)->count(),
                    'cancelled' => $dayOrders->where('report_is_cancelled', true)->count(),
                    'returned' => $dayOrders->sum('report_returned_quantity'),
                    'active_orders' => $dayOrders
                        ->where('report_is_delivered', false)
                        ->where('report_is_cancelled', false)
                        ->count(),
                    'order_value' => $dayOrders->sum(fn (Order $order) => (float) $order->total),
                ];
            })
            ->sortByDesc('date')
            ->values();

        $summary = (object) [
            'orders' => $orders->count(),
            'units' => $orders->sum('report_units'),
            'delivered' => $orders->where('report_is_delivered', true)->count(),
            'cancelled' => $orders->where('report_is_cancelled', true)->count(),
            'returned' => $orders->sum('report_returned_quantity'),
            'active_orders' => $orders
                ->where('report_is_delivered', false)
                ->where('report_is_cancelled', false)
                ->count(),
            'order_value' => $orders->sum(fn (Order $order) => (float) $order->total),
        ];

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 31;
        $paginatedDailyReport = new LengthAwarePaginator(
            $dailyReport->forPage($page, $perPage)->values(),
            $dailyReport->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.reports.total-orders', compact(
            'from',
            'to',
            'summary',
            'paginatedDailyReport'
        ));
    }
}
