<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Receipt {{ $order->order_number }}</title>
    <style>
        @page {
            margin: 22px;
        }

        :root {
            --ink: #111827;
            --muted: #6b7280;
            --line: #d1d5db;
            --soft: #f9fafb;
            --panel: #ffffff;
            --brand: #0f766e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            color: var(--ink);
            background: #eef2f7;
        }

        .receipt-page {
            max-width: 860px;
            margin: 0 auto;
            padding: 18px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
        }

        .toolbar-title {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .toolbar-btn {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 11px;
            text-decoration: none;
        }

        .toolbar-btn.primary {
            color: #ffffff;
            background: var(--brand);
        }

        .toolbar-btn.ghost {
            color: var(--ink);
            border: 1px solid var(--line);
            background: #ffffff;
        }

        .receipt {
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            overflow: hidden;
        }

        .header {
            display: table;
            width: 100%;
            padding: 18px 22px;
            border-bottom: 2px solid var(--ink);
        }

        .brand,
        .receipt-title {
            display: table-cell;
            vertical-align: top;
        }

        .brand {
            width: 52%;
        }

        .receipt-title {
            width: 48%;
            text-align: right;
        }

        .brand-logo {
            max-width: 140px;
            max-height: 54px;
            object-fit: contain;
        }

        .brand-fallback {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 22px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .meta-line,
        .muted {
            color: var(--muted);
        }

        .content {
            padding: 18px 22px 22px;
        }

        .info-grid {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 16px;
            border-spacing: 12px 0;
        }

        .info-card {
            display: table-cell;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            vertical-align: top;
            background: var(--soft);
        }

        .card-title {
            margin: 0 0 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .info-line {
            margin: 0 0 5px;
            word-break: break-word;
        }

        .items-table,
        .totals-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table {
            margin-top: 12px;
            border: 1px solid var(--line);
        }

        .items-table th,
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }

        .items-table th {
            background: #f3f4f6;
            color: var(--muted);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            text-align: left;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .text-right {
            text-align: right;
        }

        .product-name {
            margin: 0 0 3px;
            font-weight: 700;
        }

        .summary {
            display: table;
            width: 100%;
            margin-top: 14px;
        }

        .notes,
        .totals {
            display: table-cell;
            vertical-align: top;
        }

        .notes {
            width: 55%;
            padding-right: 18px;
        }

        .totals {
            width: 45%;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--soft);
        }

        .totals-table td {
            padding: 5px 0;
        }

        .totals-table td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .totals-table tr.total td {
            padding-top: 10px;
            border-top: 1px solid var(--line);
            font-size: 15px;
            font-weight: 800;
        }

        .signature-table {
            margin-top: 28px;
            table-layout: fixed;
        }

        .signature-table td {
            padding-top: 28px;
            border-top: 1px solid var(--ink);
            color: var(--muted);
            font-size: 11px;
        }

        .signature-table td + td {
            padding-left: 28px;
        }

        .footer {
            padding: 12px 22px 18px;
            border-top: 1px dashed var(--line);
            color: var(--muted);
            font-size: 11px;
            text-align: center;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .receipt-page {
                max-width: none;
                padding: 0;
            }

            .toolbar {
                display: none !important;
            }

            .receipt {
                border: 0;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
@php
    $appName = config('app.name', 'Glamics');
    $configuredLogo = trim((string) setting('header_logo'));
    $logoUrl = $configuredLogo !== '' ? api_asset($configuredLogo) : asset('logo.svg');

    $shippingAddress = $order->shippingAddress;
    $shippingParts = array_filter([
        $shippingAddress?->address,
        $shippingAddress?->deliveryArea?->name,
        $shippingAddress?->deliveryArea?->district_name,
        $shippingAddress?->postal_code ?: $shippingAddress?->deliveryArea?->post_code,
    ]);
    $fullAddress = $shippingParts ? implode(', ', $shippingParts) : 'N/A';
    $paymentMethods = [
        'cash_on_delivery' => 'Cash on Delivery',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'rocket' => 'Rocket',
        'ssl_commerce' => 'SSL Commerce',
    ];
    $statusLabels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Packaging',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];
    $paymentMethod = $paymentMethods[$order->payment_method] ?? ucwords(str_replace('_', ' ', (string) $order->payment_method));
    $paymentStatus = ucwords(str_replace('_', ' ', (string) $order->payment_status));
    $orderStatus = $statusLabels[$order->order_status] ?? ucwords(str_replace('_', ' ', (string) $order->order_status));
    $itemScope = $itemScope ?? 'active';
    $displayItems = $order->displayItems($itemScope);
    $displaySubtotal = $order->displaySubtotal($itemScope);
    $displayTax = $order->displayTax($itemScope);
    $displayDiscount = $order->displayDiscount($itemScope);
    $displayShippingCost = $order->displayShippingCost($itemScope);
    $displayTotal = $order->displayTotal($itemScope);
@endphp

<div class="receipt-page">
    <div class="toolbar">
        <p class="toolbar-title">{{ $toolbarTitle ?? ('Invoice preview - '.$order->order_number) }}</p>
        <div class="toolbar-actions">
            <a href="javascript:void(0)" class="toolbar-btn primary" onclick="window.print(); return false;">Print / Download</a>
            @unless($hideBack ?? false)
                <a href="{{ $backUrl ?? route('orders.index') }}" class="toolbar-btn ghost">Back</a>
            @endunless
        </div>
    </div>

    <div class="receipt">
        <div class="header">
            <div class="brand">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" class="brand-logo">
                @else
                    <div class="brand-fallback">{{ $appName }}</div>
                @endif
                <div class="meta-line">{{ config('app.url') }}</div>
            </div>
            <div class="receipt-title">
                <h1>Invoice Receipt</h1>
                <div><strong>Order Number:</strong> {{ $order->order_number }}</div>
                <div><strong>Date:</strong> {{ $order->created_at?->format('d M, Y h:i A') ?? now()->format('d M, Y h:i A') }}</div>
            </div>
        </div>

        <div class="content">
            <div class="info-grid">
                <div class="info-card">
                    <h2 class="card-title">Customer Address</h2>
                    <p class="info-line"><strong>{{ $shippingAddress?->name ?: $order->customer_name }}</strong></p>
                    <p class="info-line">{{ $shippingAddress?->phone ?: $order->customer_phone ?: 'N/A' }}</p>
                    <p class="info-line">{{ $shippingAddress?->email ?: $order->customer_email ?: 'N/A' }}</p>
                    <p class="info-line">{{ $fullAddress }}</p>
                </div>
                <div class="info-card">
                    <h2 class="card-title">Delivery Info</h2>
                    <p class="info-line"><strong>Method:</strong> {{ $order->shipping_method ?: 'Standard Shipping' }}</p>
                    <p class="info-line"><strong>Delivery Charge:</strong> Tk {{ number_format((float) $displayShippingCost, 2) }}</p>
                    @if($order->steadfast_consignment_id)
                        <p class="info-line"><strong>Consignment:</strong> {{ $order->steadfast_consignment_id }}</p>
                    @endif
                    @if($order->steadfast_tracking_code)
                        <p class="info-line"><strong>Tracking:</strong> {{ $order->steadfast_tracking_code }}</p>
                    @endif
                    @if($order->steadfast_status)
                        <p class="info-line"><strong>Courier Status:</strong> {{ ucwords(str_replace('_', ' ', $order->steadfast_status)) }}</p>
                    @endif
                </div>
                <div class="info-card">
                    <h2 class="card-title">Payment Info</h2>
                    <p class="info-line"><strong>Method:</strong> {{ $paymentMethod ?: 'N/A' }}</p>
                    <p class="info-line"><strong>Status:</strong> {{ $paymentStatus ?: 'N/A' }}</p>
                    @if($order->coupon_code)
                        <p class="info-line"><strong>Coupon:</strong> {{ $order->coupon_code }}</p>
                    @endif
                    <p class="info-line"><strong>Receivable:</strong> Tk {{ number_format((float) $displayTotal, 2) }}</p>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 34%;">Product</th>
                        <th style="width: 18%;">SKU</th>
                        <th style="width: 18%;">Variant</th>
                        <th style="width: 8%;" class="text-right">Qty</th>
                        <th style="width: 11%;" class="text-right">Price</th>
                        <th style="width: 11%;" class="text-right">Sub Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($displayItems as $item)
                        @php
                            $variantLabel = $item->productVariant?->name ?: $item->variant_name;
                            $displayQuantity = $order->displayQuantityForItem($item, $itemScope);
                            $displayItemSubtotal = $order->displaySubtotalForItem($item, $itemScope);
                        @endphp
                        <tr>
                            <td>
                                <p class="product-name">{{ $item->product_name }}</p>
                                @if($item->product_slug)
                                    <div class="muted">{{ $item->product_slug }}</div>
                                @endif
                            </td>
                            <td>{{ $item->product_sku ?: '-' }}</td>
                            <td>{{ $variantLabel ?: '-' }}</td>
                            <td class="text-right">{{ $displayQuantity }}</td>
                            <td class="text-right">Tk {{ number_format((float) $item->price, 2) }}</td>
                            <td class="text-right">Tk {{ number_format((float) $displayItemSubtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-right">No product items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary">
                <div class="notes">
                    <h2 class="card-title">Other Info</h2>
                    <p class="info-line"><strong>Customer:</strong> {{ $order->customer_name }}</p>
                    <p class="info-line"><strong>Phone:</strong> {{ $order->customer_phone ?: 'N/A' }}</p>
                    @if($order->order_notes)
                        <p class="info-line"><strong>Notes:</strong> {{ $order->order_notes }}</p>
                    @else
                        <p class="info-line muted">No order notes recorded.</p>
                    @endif
                </div>
                <div class="totals">
                    <table class="totals-table">
                        <tr>
                            <td>Sub Total</td>
                            <td>Tk {{ number_format((float) $displaySubtotal, 2) }}</td>
                        </tr>
                        @if((float) $displayTax > 0)
                            <tr>
                                <td>Tax</td>
                                <td>Tk {{ number_format((float) $displayTax, 2) }}</td>
                            </tr>
                        @endif
                        @if((float) $displayDiscount > 0)
                            <tr>
                                <td>Discount</td>
                                <td>-Tk {{ number_format((float) $displayDiscount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Delivery Charge</td>
                            <td>Tk {{ number_format((float) $displayShippingCost, 2) }}</td>
                        </tr>
                        <tr class="total">
                            <td>Total</td>
                            <td>Tk {{ number_format((float) $displayTotal, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <table class="signature-table">
                <tr>
                    <td>Prepared By</td>
                    <td>Courier Signature</td>
                    <td>Customer Signature</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Please verify customer address and product quantity before handover.
        </div>
    </div>
</div>
</body>
</html>
