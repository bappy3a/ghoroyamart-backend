<?php

namespace App\Services\AiMessage\Support;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use Carbon\Carbon;

/**
 * Read-only order lookups for the AI message service. Never guesses —
 * returns null when nothing matches so the caller can be told honestly.
 */
class OrderLookupService
{
    /**
     * Human-readable Bangla labels for the known order_status values.
     * Purely a translation of real DB values — never a source of truth.
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS_BN = [
        'pending' => 'অর্ডারটি জমা হয়েছে, এখনো কনফার্ম করা হয়নি',
        'confirmed' => 'অর্ডারটি কনফার্ম করা হয়েছে',
        'processing' => 'অর্ডারটি প্রসেসিং করা হচ্ছে',
        'shipped' => 'অর্ডারটি কুরিয়ারে পাঠানো হয়েছে',
        'delivered' => 'অর্ডারটি ডেলিভারি সম্পন্ন হয়েছে',
        'cancelled' => 'অর্ডারটি বাতিল করা হয়েছে',
    ];

    /**
     * Find an order by its order number (e.g. AGO-AB12CD) or numeric ID.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $order = Order::query()
            ->with(['items', 'timelines'])
            ->where(function ($q) use ($identifier) {
                $q->whereRaw('UPPER(order_number) = ?', [strtoupper($identifier)]);

                if (ctype_digit($identifier)) {
                    $q->orWhere('id', (int) $identifier);
                }
            })
            ->first();

        return $order ? $this->present($order) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->order_status,
            'status_label_bn' => self::STATUS_LABELS_BN[$order->order_status] ?? null,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'cancellation_reason' => $order->order_status === 'cancelled'
                ? $order->cancellationReasonText()
                : null,
            'total' => (float) $order->total,
            'placed_at' => optional($order->created_at)?->format('d M, Y h:i A'),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'name' => $item->product_name,
                'variant' => $item->variant_name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
            ])->values()->all(),
            'timeline' => $this->timeline($order),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function timeline(Order $order): array
    {
        return $order->timelines
            ->sortBy(function (OrderTimeline $timeline) {
                $raw = $timeline->getRawOriginal('date');

                return $raw ? Carbon::parse($raw) : $timeline->created_at;
            })
            ->values()
            ->map(function (OrderTimeline $timeline) {
                $raw = $timeline->getRawOriginal('date');
                $date = $raw ? Carbon::parse($raw) : $timeline->created_at;

                return [
                    'status' => $timeline->status,
                    'description' => $timeline->description,
                    'date' => optional($date)?->format('d M, Y h:i A'),
                ];
            })
            ->all();
    }
}
