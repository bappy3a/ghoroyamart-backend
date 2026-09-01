<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SteadfastCourier
{
    public function createOrder(Order $order): array
    {
        $apiKey = config('services.steadfast.api_key');
        $secretKey = config('services.steadfast.secret_key');

        if (blank($apiKey) || blank($secretKey)) {
            throw new RuntimeException('Steadfast API credentials are not configured.');
        }

        $baseUrl = rtrim((string) config('services.steadfast.base_url'), '/');
        $payload = $this->payload($order);
        $response = Http::withHeaders([
            'Api-Key' => $apiKey,
            'Secret-Key' => $secretKey,
            'Content-Type' => 'application/json',
        ])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.steadfast.timeout', 15))
            ->post($baseUrl.'/create_order', $payload);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response->status(), $response->json(), $response->body()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Steadfast returned an invalid response.');
        }

        if ((int) ($data['status'] ?? 0) !== 200 || empty($data['consignment'])) {
            throw new RuntimeException($data['message'] ?? 'Steadfast could not create the consignment.');
        }

        return $data;
    }

    public function bulkCreateOrders(iterable $orders): array
    {
        $apiKey = config('services.steadfast.api_key');
        $secretKey = config('services.steadfast.secret_key');

        if (blank($apiKey) || blank($secretKey)) {
            throw new RuntimeException('Steadfast API credentials are not configured.');
        }

        $payloads = collect($orders)
            ->map(fn (Order $order) => $this->payload($order))
            ->values();

        if ($payloads->isEmpty()) {
            return [];
        }

        if ($payloads->count() > 500) {
            throw new RuntimeException('Steadfast allows a maximum of 500 orders per bulk request.');
        }

        $baseUrl = rtrim((string) config('services.steadfast.base_url'), '/');
        $response = Http::withHeaders([
            'Api-Key' => $apiKey,
            'Secret-Key' => $secretKey,
            'Content-Type' => 'application/json',
        ])
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.steadfast.timeout', 15))
            ->post($baseUrl.'/create_order/bulk-order', [
                'data' => $payloads->toJson(JSON_UNESCAPED_UNICODE),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response->status(), $response->json(), $response->body()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Steadfast returned an invalid bulk response.');
        }

        if (array_is_list($data)) {
            return collect($data)->filter(fn ($item) => is_array($item))->values()->all();
        }

        $items = data_get($data, 'data');

        if (is_array($items)) {
            return collect($items)->filter(fn ($item) => is_array($item))->values()->all();
        }

        if ((int) ($data['status'] ?? 200) !== 200) {
            throw new RuntimeException($data['message'] ?? 'Steadfast could not create the bulk consignments.');
        }

        throw new RuntimeException('Steadfast returned an invalid bulk response.');
    }

    private function errorMessage(int $status, mixed $json, string $body): string
    {
        $message = is_array($json) ? data_get($json, 'message') : null;

        if (blank($message) && is_array($json)) {
            $message = collect($json)
                ->flatten()
                ->filter(fn ($value) => is_scalar($value) && filled((string) $value))
                ->implode(' ');
        }

        if (blank($message)) {
            $message = Str::limit(trim(strip_tags($body)), 200, '');
        }

        return filled($message)
            ? 'Steadfast returned HTTP '.$status.': '.$message
            : 'Steadfast returned HTTP '.$status.'.';
    }

    private function payload(Order $order): array
    {
        $order->loadMissing([
            'items',
            'shippingAddress.district',
            'shippingAddress.thana',
        ]);

        $shippingAddress = $order->shippingAddress;
        $recipientName = $shippingAddress?->name ?: $order->customer_name;
        $recipientPhone = $this->normalizePhone($shippingAddress?->phone ?: $order->customer_phone);
        $recipientAddress = $this->address($order);

        if (blank($recipientName)) {
            throw new RuntimeException('Recipient name is missing.');
        }

        if (blank($recipientAddress)) {
            throw new RuntimeException('Recipient address is missing.');
        }

        $activeItems = $order->displayItems('active');

        return [
            'invoice' => $order->order_number,
            'recipient_name' => Str::limit($recipientName, 100, ''),
            'recipient_phone' => $recipientPhone,
            'recipient_email' => $shippingAddress?->email ?: $order->customer_email,
            'recipient_address' => Str::limit($recipientAddress, 250, ''),
            'cod_amount' => $this->codAmount($order),
            'note' => $order->order_notes,
            'item_description' => $this->itemDescription($order),
            'total_lot' => max(1, (int) $activeItems->sum(fn ($item) => $item->activeQuantity())),
            'delivery_type' => 0,
        ];
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '88') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) > 11) {
            $digits = substr($digits, -11);
        }

        if (! preg_match('/^01\d{9}$/', $digits)) {
            throw new RuntimeException('Recipient phone must be a valid 11 digit Bangladesh number.');
        }

        return $digits;
    }

    private function address(Order $order): string
    {
        $shippingAddress = $order->shippingAddress;

        if (! $shippingAddress) {
            return '';
        }

        $address = collect([
            $shippingAddress->address,
            $shippingAddress->thana?->name,
            $shippingAddress->district?->name,
        ])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');

        $zipCode = $shippingAddress->thana?->zip_code;

        return filled($zipCode) ? $address.'-'.$zipCode : $address;
    }

    private function codAmount(Order $order): float
    {
        if ($order->payment_status === 'paid') {
            return 0;
        }

        return $order->payment_method === 'cash_on_delivery' ? (float) $order->total : 0;
    }

    private function itemDescription(Order $order): string
    {
        $description = $order->displayItems('active')
            ->map(fn ($item) => trim($item->product_name.' x '.$item->activeQuantity()))
            ->filter()
            ->implode(', ');

        return Str::limit($description ?: $order->order_number, 500, '');
    }
}
