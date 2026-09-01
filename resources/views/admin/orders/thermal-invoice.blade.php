<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermal Invoice {{ $order->order_number }}</title>
    <style>
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #202020;
            background: #f3f4f6;
            font-family: Arial, "Noto Sans Bengali", "SolaimanLipi", sans-serif;
            font-size: 13px;
            line-height: 1.25;
        }

        .toolbar {
            max-width: 100mm;
            margin: 12px auto;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #d6d6d6;
            border-radius: 6px;
        }

        .toolbar-title {
            margin: 0;
            font-size: 13px;
            font-weight: 700;
        }

        .toolbar-actions {
            display: flex;
            gap: 8px;
        }

        .toolbar-btn {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 5px;
            color: #ffffff;
            background: #111111;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
        }

        .toolbar-btn.ghost {
            color: #111111;
            background: #ffffff;
            border: 1px solid #cfcfcf;
        }

        .thermal-sheet {
            width: 100mm;
            min-height: 150mm;
            margin: 0 auto 20px;
            background: #ffffff;
            overflow: hidden;
        }

        .label {
            width: 100%;
            min-height: 150mm;
            padding: 9mm 9mm 6mm;
        }

        .top {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .brand-block,
        .logo-block {
            display: table-cell;
            vertical-align: top;
        }

        .brand-block {
            width: 67%;
        }

        .logo-block {
            width: 33%;
            padding-top: 3mm;
            text-align: right;
        }

        .brand-name {
            margin: 0 0 2mm;
            font-size: 18px;
            line-height: 1.05;
            font-weight: 800;
        }

        .meta-line {
            margin: 0;
            font-size: 13px;
        }

        .meta-line strong {
            font-weight: 800;
        }

        .logo {
            max-width: 28mm;
            max-height: 20mm;
            object-fit: contain;
        }

        .invoice-to {
            margin-top: 4mm;
        }

        .section-title {
            margin: 0 0 3mm;
            font-size: 16px;
            font-weight: 800;
        }

        .contact-line {
            display: flex;
            align-items: flex-start;
            gap: 2mm;
            margin: 1mm 0;
            font-size: 13px;
            font-weight: 700;
        }

        .contact-icon {
            width: 3.2mm;
            height: 3.2mm;
            margin-top: 0.3mm;
            flex: 0 0 3.2mm;
            border: 1.4px solid #111111;
            border-radius: 50%;
            position: relative;
        }

        .contact-icon.person:before {
            content: "";
            position: absolute;
            top: 0.55mm;
            left: 0.95mm;
            width: 0.95mm;
            height: 0.95mm;
            border-radius: 50%;
            background: #111111;
        }

        .contact-icon.person:after {
            content: "";
            position: absolute;
            left: 0.55mm;
            bottom: 0.55mm;
            width: 1.75mm;
            height: 0.95mm;
            border-radius: 2mm 2mm 0 0;
            background: #111111;
        }

        .contact-icon.phone:before {
            content: "";
            position: absolute;
            top: 0.55mm;
            left: 1.15mm;
            width: 0.85mm;
            height: 1.75mm;
            border: 1px solid #111111;
            border-top-width: 1.4px;
            border-bottom-width: 1.4px;
            transform: rotate(-35deg);
        }

        .contact-icon.pin:before {
            content: "";
            position: absolute;
            top: 0.55mm;
            left: 0.9mm;
            width: 1.1mm;
            height: 1.4mm;
            border: 1.1px solid #111111;
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
        }

        .items-table {
            width: calc(100% + 18mm);
            margin: 6mm -9mm 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table th,
        .items-table td {
            padding: 3.2mm 2mm;
            font-size: 12px;
            font-weight: 800;
            vertical-align: middle;
        }

        .items-table th {
            background: #e8e8e8;
            text-align: left;
        }

        .items-table tbody tr:nth-child(odd) {
            background: #ffffff;
        }

        .items-table tbody tr:nth-child(even) {
            background: #e8e8e8;
        }

        .items-table .product {
            padding-left: 9mm;
            width: 40%;
            word-break: break-word;
        }

        .items-table .sku {
            width: 18%;
            text-align: center;
        }

        .items-table .variant {
            width: 19%;
            text-align: center;
        }

        .items-table .qty {
            width: 9%;
            text-align: center;
        }

        .items-table .price {
            width: 14%;
            padding-right: 8mm;
            text-align: right;
        }

        .summary {
            width: 45%;
            margin: 8mm 0 0 auto;
            border-collapse: collapse;
        }

        .summary td {
            padding: 1.8mm 0;
            font-size: 12.5px;
            font-weight: 800;
        }

        .summary td:last-child {
            text-align: right;
        }

        .note {
            margin-top: 11mm;
            min-height: 18mm;
        }

        .note-title {
            margin: 0 0 3mm;
            font-size: 14px;
            font-weight: 800;
        }

        .note-text {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            word-break: break-word;
        }

        @media print {
            html,
            body {
                width: 100mm;
                min-height: 150mm;
                background: #ffffff;
            }

            .toolbar {
                display: none !important;
            }

            .thermal-sheet {
                margin: 0;
            }
        }
    </style>
</head>
<body>
@php
    $appName = setting('site_name', config('app.name', 'Olive Fashion'));
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
    $parcelId = $order->steadfast_consignment_id ?: $order->steadfast_tracking_code ?: 'N/A';
    $itemScope = $itemScope ?? 'active';
    $displayItems = $order->displayItems($itemScope);
    $displayDiscount = $order->displayDiscount($itemScope);
    $displayShippingCost = $order->displayShippingCost($itemScope);
    $displayTotal = $order->displayTotal($itemScope);
    $formatAmount = fn ($amount) => number_format((float) $amount, fmod((float) $amount, 1.0) === 0.0 ? 0 : 2, '.', '');
@endphp

<div class="toolbar">
    <p class="toolbar-title">Thermal Invoice - {{ $order->order_number }}</p>
    <div class="toolbar-actions">
        <a href="javascript:void(0)" class="toolbar-btn" onclick="window.print(); return false;">Print</a>
        <a href="{{ $backUrl ?? route('orders.index') }}" class="toolbar-btn ghost">Back</a>
    </div>
</div>

<main class="thermal-sheet">
    <section class="label">
        <div class="top">
            <div class="brand-block">
                <h1 class="brand-name">{{ $appName }}</h1>
                <p class="meta-line">Order ID : <strong>{{ $order->order_number }}</strong></p>
                <p class="meta-line">Order Date: <strong>{{ $order->created_at?->format('M d, Y') ?? now()->format('M d, Y') }}</strong></p>
                <p class="meta-line">Parcel ID : <strong>{{ $parcelId }}</strong></p>
            </div>
            <div class="logo-block">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $appName }} logo" class="logo">
                @endif
            </div>
        </div>

        <div class="invoice-to">
            <h2 class="section-title">Invoice To:</h2>
            <div class="contact-line">
                <span class="contact-icon person" aria-hidden="true"></span>
                <span>{{ $shippingAddress?->name ?: $order->customer_name ?: 'N/A' }}</span>
            </div>
            <div class="contact-line">
                <span class="contact-icon phone" aria-hidden="true"></span>
                <span>{{ $shippingAddress?->phone ?: $order->customer_phone ?: 'N/A' }}</span>
            </div>
            <div class="contact-line">
                <span class="contact-icon pin" aria-hidden="true"></span>
                <span>{{ $fullAddress }}</span>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="product">Product Name</th>
                    <th class="sku">SKU</th>
                    <th class="variant">Varient</th>
                    <th class="qty">Qty.</th>
                    <th class="price">Price</th>
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
                        <td class="product">{{ $item->product_name }}</td>
                        <td class="sku">{{ $item->product_sku ?: '-' }}</td>
                        <td class="variant">{{ $variantLabel ?: '-' }}</td>
                        <td class="qty">{{ $displayQuantity }}</td>
                        <td class="price">{{ $formatAmount($displayItemSubtotal) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="product" colspan="5">No product items found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td>Discount</td>
                <td>{{ $formatAmount($displayDiscount) }}</td>
            </tr>
            <tr>
                <td>Delivery Fee</td>
                <td>{{ (float) $displayShippingCost > 0 ? $formatAmount($displayShippingCost) : '*Free' }}</td>
            </tr>
            <tr>
                <td>Sub Total</td>
                <td>{{ $formatAmount($displayTotal) }}</td>
            </tr>
        </table>

        <div class="note">
            <h2 class="note-title">Order Note</h2>
            @if($order->order_notes)
                <p class="note-text">{{ $order->order_notes }}</p>
            @endif
        </div>
    </section>
</main>
</body>
</html>
