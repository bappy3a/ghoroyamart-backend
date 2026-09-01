<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    use ApiResponse;

    /**
     * Public order tracking by order number + customer phone.
     */
    public function track(Request $request)
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $orderNumber = trim($validated['order_number']);
        $phone = $this->normalizePhone($validated['phone']);

        $order = Order::query()
            ->with(['items', 'shippingAddress.deliveryArea', 'timelines'])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order || ! $this->phoneMatchesOrder($order, $phone)) {
            return $this->error(
                'No order found for that order number and phone.',
                null,
                null,
                404
            );
        }

        return $this->success(
            (new OrderResource($order))->resolve(),
            null,
            'Order found successfully.'
        );
    }

    /**
     * List authenticated user's orders (newest first).
     */
    public function index(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->with(['items', 'shippingAddress.deliveryArea', 'timelines'])
            ->latest('id')
            ->paginate(min(max((int) $request->input('per_page', 20), 1), 50));

        $items = $orders->getCollection()
            ->map(fn (Order $order) => (new OrderResource($order))->resolve())
            ->values()
            ->all();

        return $this->success([
            'orders' => $items,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], null, 'Orders fetched successfully.');
    }

    /**
     * Show a single order owned by the authenticated user.
     */
    public function show(Request $request, string $orderNumber)
    {
        $order = $request->user()
            ->orders()
            ->with(['items', 'shippingAddress.deliveryArea', 'timelines'])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return $this->error('Order not found.', null, null, 404);
        }

        return $this->success(
            (new OrderResource($order))->resolve(),
            null,
            'Order fetched successfully.'
        );
    }

    /**
     * Printable invoice for the authenticated order owner.
     */
    public function invoice(Request $request, string $orderNumber): View
    {
        $order = $request->user()
            ->orders()
            ->with([
                'items.product',
                'items.productVariant.values.attribute',
                'items.productVariant.values.value',
                'shippingAddress.deliveryArea',
                'coupon',
            ])
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            abort(404, 'Order not found.');
        }

        return view('admin.orders.delivery-receipt', [
            'order' => $order,
            'itemScope' => 'active',
            'hideBack' => true,
            'toolbarTitle' => 'Invoice - '.$order->order_number,
        ]);
    }

    private function phoneMatchesOrder(Order $order, string $normalizedPhone): bool
    {
        if ($normalizedPhone === '') {
            return false;
        }

        $candidates = array_filter([
            $order->customer_phone,
            $order->shippingAddress?->phone,
        ]);

        foreach ($candidates as $candidate) {
            if ($this->normalizePhone((string) $candidate) === $normalizedPhone) {
                return true;
            }
        }

        return false;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '88') && strlen($digits) === 12) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
