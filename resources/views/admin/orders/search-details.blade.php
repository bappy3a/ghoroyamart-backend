@extends('layouts.master')
@section('title', 'Order Details')

@section('content')
@php
    $statusColors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'processing' => 'primary',
        'shipped' => 'info',
        'delivered' => 'success',
        'cancelled' => 'danger',
    ];
    $statusColor = $statusColors[$order->order_status] ?? 'secondary';
@endphp

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="d-flex align-items-center mb-3">
            <div class="flex-grow-1">
                <h4 class="mb-1">Order {{ $order->order_number }}</h4>
                <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }} text-uppercase">
                    {{ str_replace('_', ' ', $order->order_status) }}
                </span>
            </div>
            <a href="{{ route('orders.search') }}" class="btn btn-light">
                <i class="ri-search-line align-middle me-1"></i> Search Another Order
            </a>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="card-title flex-grow-1 mb-0">Order Details</h5>
                @if($order->order_status === 'pending')
                    <form method="POST" action="{{ route('orders.search.cancel', $order) }}" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="ri-close-circle-line align-middle me-1"></i> Cancel Order
                        </button>
                    </form>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase">Customer</h6>
                        <p class="mb-1"><strong>{{ $order->customer_name }}</strong></p>
                        <p class="mb-1">{{ $order->customer_phone }}</p>
                        @if($order->customer_email)<p class="mb-0">{{ $order->customer_email }}</p>@endif
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase">Shipping Address</h6>
                        <p class="mb-1">{{ $order->shippingAddress?->address ?? 'Not provided' }}</p>
                        @if($order->shippingAddress?->postal_code)<p class="mb-0">Postal code: {{ $order->shippingAddress->postal_code }}</p>@endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-nowrap align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if($item->productVariant?->name || $item->variant_name)
                                            <small class="text-muted d-block">{{ $item->productVariant?->name ?: $item->variant_name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->product_sku ?: '—' }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end">৳{{ number_format((float) $item->price, 2) }}</td>
                                    <td class="text-end">৳{{ number_format((float) $item->subtotal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="4" class="text-end">Subtotal</th><td class="text-end">৳{{ number_format((float) $order->subtotal, 2) }}</td></tr>
                            <tr><th colspan="4" class="text-end">Shipping</th><td class="text-end">৳{{ number_format((float) $order->shipping_cost, 2) }}</td></tr>
                            @if($order->discount > 0)<tr><th colspan="4" class="text-end">Discount</th><td class="text-end">-৳{{ number_format((float) $order->discount, 2) }}</td></tr>@endif
                            <tr class="table-light"><th colspan="4" class="text-end">Total</th><th class="text-end">৳{{ number_format((float) $order->total, 2) }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Order Timeline</h5>
            </div>
            <div class="card-body">
                @forelse($order->timelines as $timeline)
                    <div class="d-flex {{ ! $loop->last ? 'border-bottom mb-3 pb-3' : '' }}">
                        <div class="flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle" style="width: 36px; height: 36px;">
                                <i class="ri-history-line"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <h6 class="mb-1">{{ $timeline->status }}</h6>
                                <small class="text-muted">
                                    {{ $timeline->date ?: $timeline->created_at?->format('d M Y, h:i A') }}
                                </small>
                            </div>
                            @if($timeline->description)
                                <p class="text-muted mb-1">{{ $timeline->description }}</p>
                            @endif
                            @if($timeline->updater)
                                <small class="text-muted">Updated by: {{ $timeline->updater->name }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No order timeline entries found.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
