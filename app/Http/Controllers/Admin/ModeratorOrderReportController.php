<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class ModeratorOrderReportController extends Controller
{
    private const STATUSES = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'moderator_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(array_merge(['all'], self::STATUSES))],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $validated['from'] ?? now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $validated['to'] ?? now()->format('Y-m-d'))->endOfDay();
        $moderatorId = isset($validated['moderator_id']) ? (int) $validated['moderator_id'] : null;
        $status = $validated['status'] ?? 'all';

        $moderators = User::query()
            ->whereHas('createdOrders')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $orders = Order::query()
            ->with(['creator:id,name,email', 'items:id,order_id,quantity'])
            ->whereNotNull('created_by_id')
            ->whereBetween('created_at', [$from, $to])
            ->when($moderatorId, fn ($query) => $query->where('created_by_id', $moderatorId))
            ->when($status !== 'all', fn ($query) => $query->where('order_status', $status))
            ->latest('created_at')
            ->get()
            ->each(fn (Order $order) => $order->report_units = $order->items->sum('quantity'));

        $moderatorReport = $orders
            ->groupBy('created_by_id')
            ->map(function ($moderatorOrders) {
                $firstOrder = $moderatorOrders->first();

                return (object) [
                    'moderator' => $firstOrder->creator,
                    'orders' => $moderatorOrders->count(),
                    'units' => $moderatorOrders->sum('report_units'),
                    'order_value' => $moderatorOrders->sum(fn (Order $order) => (float) $order->total),
                    'status_counts' => collect(self::STATUSES)
                        ->mapWithKeys(fn ($orderStatus) => [$orderStatus => $moderatorOrders->where('order_status', $orderStatus)->count()]),
                ];
            })
            ->sortByDesc('orders')
            ->values();

        $summary = (object) [
            'moderators' => $moderatorReport->count(),
            'orders' => $orders->count(),
            'units' => $orders->sum('report_units'),
            'order_value' => $orders->sum(fn (Order $order) => (float) $order->total),
        ];

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;
        $paginatedOrders = new LengthAwarePaginator(
            $orders->forPage($page, $perPage)->values(),
            $orders->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.reports.moderator-orders', compact(
            'from',
            'to',
            'moderatorId',
            'status',
            'moderators',
            'moderatorReport',
            'summary',
            'paginatedOrders'
        ));
    }
}
