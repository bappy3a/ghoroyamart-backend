<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTimeline;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SteadfastWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->hasValidSecret($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized webhook request.',
            ], 401);
        }

        $validated = $request->validate([
            'notification_type' => ['required', Rule::in(['delivery_status', 'tracking_update'])],
            'consignment_id' => ['required', 'integer'],
            'invoice' => ['required', 'string', 'max:255'],
            'tracking_message' => ['nullable', 'string'],
            'updated_at' => ['required', 'date_format:Y-m-d H:i:s'],
            'cod_amount' => ['required_if:notification_type,delivery_status', 'nullable', 'numeric'],
            'status' => ['required_if:notification_type,delivery_status', 'nullable', 'string'],
            'delivery_charge' => ['required_if:notification_type,delivery_status', 'nullable', 'numeric'],
        ]);

        if ($validated['notification_type'] === 'delivery_status') {
            $validated['status'] = strtolower(trim((string) $validated['status']));

            validator($validated, [
                'status' => ['required', Rule::in([
                    'pending',
                    'delivered',
                    'partial_delivered',
                    'cancelled',
                    'unknown',
                ])],
            ])->validate();
        }

        $order = Order::query()
            ->where('steadfast_consignment_id', $validated['consignment_id'])
            ->orWhere('order_number', $validated['invoice'])
            ->first();

        if (! $order) {
            Log::warning('Steadfast webhook order not found.', [
                'consignment_id' => $validated['consignment_id'],
                'invoice' => $validated['invoice'],
                'notification_type' => $validated['notification_type'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order not found for this Steadfast webhook.',
            ], 404);
        }

        $webhookDate = Carbon::createFromFormat('Y-m-d H:i:s', $validated['updated_at']);

        DB::transaction(function () use ($order, $validated, $webhookDate) {
            $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $updates = [
                'steadfast_consignment_id' => $validated['consignment_id'],
            ];

            if ($validated['notification_type'] === 'delivery_status') {
                $updates['steadfast_status'] = $validated['status'];
                $updates['steadfast_response'] = $this->mergeSteadfastResponse($lockedOrder, $validated);
                $updates['steadfast_delivery_charges'] = round((float) $validated['delivery_charge'], 2);

                $orderStatus = $this->orderStatusForSteadfastStatus($validated['status']);

                if ($orderStatus && $this->shouldUpdateOrderStatus($lockedOrder->order_status, $orderStatus)) {
                    $updates['order_status'] = $orderStatus;

                    if ($orderStatus === 'cancelled') {
                        $updates['cancelled_by_type'] = 'courier';
                        $updates['cancellation_reason'] = $validated['tracking_message'] ?? 'Cancelled by Steadfast courier.';
                        $updates['cancelled_at'] = $webhookDate;
                    }
                }
            } else {
                $updates['steadfast_response'] = $this->mergeSteadfastResponse($lockedOrder, $validated);

                if ($this->shouldUpdateOrderStatus($lockedOrder->order_status, 'shipped')) {
                    $updates['order_status'] = 'shipped';
                }
            }

            $lockedOrder->update($updates);

            if (($updates['order_status'] ?? null) === 'cancelled') {
                OrderItem::where('order_id', $lockedOrder->id)
                    ->lockForUpdate()
                    ->get()
                    ->each(function (OrderItem $item) use ($validated, $webhookDate) {
                        $item->update([
                            'cancelled_quantity' => $item->quantity,
                            'item_status' => 'cancelled',
                            'cancelled_by_type' => 'courier',
                            'cancelled_by_id' => null,
                            'cancellation_reason' => $validated['tracking_message'] ?? 'Cancelled by Steadfast courier.',
                            'cancelled_at' => $webhookDate,
                        ]);
                    });
            }

            OrderTimeline::create([
                'order_id' => $lockedOrder->id,
                'updated_by' => null,
                'status' => $this->timelineStatus($validated),
                'description' => $this->timelineDescription($validated),
                'date' => $webhookDate,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Steadfast webhook processed successfully.',
        ]);
    }

    private function hasValidSecret(Request $request): bool
    {
        $secret = config('services.steadfast.webhook_secret');

        if (blank($secret)) {
            return true;
        }

        return hash_equals((string) $secret, (string) $request->header('X-Steadfast-Webhook-Secret'));
    }

    private function mergeSteadfastResponse(Order $order, array $payload): array
    {
        $response = is_array($order->steadfast_response) ? $order->steadfast_response : [];

        $response['latest_webhook'] = $payload;
        $response['webhook_history'] = array_slice([
            ...Arr::wrap($response['webhook_history'] ?? []),
            $payload,
        ], -20);

        return $response;
    }

    private function orderStatusForSteadfastStatus(string $status): ?string
    {
        return match ($status) {
            'pending' => 'processing',
            'partial_delivered' => 'partial_delivered',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => null,
        };
    }

    private function shouldUpdateOrderStatus(string $currentStatus, string $targetStatus): bool
    {
        if ($currentStatus === $targetStatus) {
            return true;
        }

        if (in_array($currentStatus, ['delivered', 'cancelled'], true)) {
            return false;
        }

        if ($targetStatus === 'cancelled') {
            return true;
        }

        $rank = [
            'pending' => 0,
            'confirmed' => 1,
            'processing' => 2,
            'shipped' => 3,
            'partial_delivered' => 4,
            'delivered' => 5,
        ];

        return ($rank[$targetStatus] ?? -1) >= ($rank[$currentStatus] ?? -1);
    }

    private function timelineStatus(array $payload): string
    {
        if ($payload['notification_type'] === 'delivery_status') {
            return 'Steadfast Delivery Status: '.(string) str($payload['status'])->replace('_', ' ')->title();
        }

        return 'Steadfast Tracking Update';
    }

    private function timelineDescription(array $payload): string
    {
        $parts = [
            $payload['tracking_message'] ?? null,
            'Consignment ID: '.$payload['consignment_id'],
            'Invoice: '.$payload['invoice'],
        ];

        if ($payload['notification_type'] === 'delivery_status') {
            $parts[] = 'COD Amount: '.number_format((float) ($payload['cod_amount'] ?? 0), 2, '.', '');
            $parts[] = 'Delivery Charge: '.number_format((float) ($payload['delivery_charge'] ?? 0), 2, '.', '');
        }

        return collect($parts)->filter(fn ($part) => filled($part))->implode("\n");
    }
}
