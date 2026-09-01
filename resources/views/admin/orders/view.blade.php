@extends('layouts.master')
@section('title','Order Details')
@section('content')
@php
    $itemScope = $itemScope ?? null;
    $displayItems = $itemScope === 'active'
        ? $order->items->filter(fn ($item) => $item->activeQuantity() > 0 || (int) $item->restocked_quantity > 0)->values()
        : $order->displayItems($itemScope);
    $displaySubtotal = $order->displaySubtotal($itemScope);
    $displayTax = $order->displayTax($itemScope);
    $displayDiscount = $order->displayDiscount($itemScope);
    $displayShippingCost = $order->displayShippingCost($itemScope);
    $displayTotal = $order->displayTotal($itemScope);
    $itemsTitle = match ($itemScope) {
        'cancelled' => 'Cancelled Items',
        'active' => 'Delivered Items',
        default => 'Order Items',
    };
    $isStockRestockableStatus = $order->isStockRestockableStatus();
    $canRestockOrder = $isStockRestockableStatus && ! $order->stock_restocked_at;
@endphp
<div class="row">
    <div class="col-xl-9">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Order {{ $order->order_number }} - {{ $itemsTitle }}</h5>
                    <div class="flex-shrink-0 d-flex gap-2">
                        @can('orders.update')
                            @if($order->order_status !== 'cancelled')
                                <a href="{{ route('orders.edit', $order->order_number) }}" class="btn btn-secondary btn-sm">
                                    <i class="ri-pencil-line align-middle me-1"></i> Edit Order
                                </a>
                            @endif
                        @endcan
                        <a href="{{ route('orders.invoice.print', $order) }}" target="_blank" class="btn btn-primary btn-sm"><i class="ri-printer-line align-middle me-1"></i> Print Invoice</a>
                        <a href="{{ route('orders.thermal-invoice.print', $order) }}" target="_blank" class="btn btn-info btn-sm"><i class="ri-printer-line align-middle me-1"></i> Thermal Printer Invoice</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive table-card">
                    <table class="table table-nowrap align-middle table-borderless mb-0">
                        <thead class="table-light text-muted">
                            <tr>
                                <th scope="col">Product Details</th>
                                <th scope="col">Item Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Rating</th>
                                <th scope="col" class="text-end">Total Amount</th>
                                @if($isStockRestockableStatus)
                                <th scope="col" class="text-end">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($displayItems as $item)
                            @php
                                $variantLabel = $item->productVariant?->name ?: $item->variant_name;
                                $displayQuantity = $order->displayQuantityForItem($item, $itemScope);
                                $displayItemSubtotal = $order->displaySubtotalForItem($item, $itemScope);
                                $itemRestockableQuantity = $item->restockableQuantity();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 avatar-md bg-light rounded p-1">
                                            <img src="{{ $item->product_image ? api_asset($item->product_image) : URL::asset('build/images/products/img-8.png') }}" alt="{{ $item->product_name }}"
                                                class="img-fluid d-block" style="max-width: 60px; max-height: 60px; object-fit: cover;">
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="fs-15">
                                                <a href="#" class="link-primary">{{ $item->product_name }}</a>
                                            </h5>
                                            @if($variantLabel)
                                            <p class="text-muted mb-0">Variant: <span class="fw-medium">{{ $variantLabel }}</span></p>
                                            @endif
                                            @if($item->product_sku)
                                            <p class="text-muted mb-0">SKU: <span class="fw-medium">{{ $item->product_sku }}</span></p>
                                            @endif
                                            @if($item->hasCancelledQuantity())
                                            <p class="text-muted mb-0">
                                                Cancelled: <span class="fw-medium">{{ $item->cancelledQuantity() }}</span>
                                                @if($item->cancellation_reason)
                                                    <span title="{{ $item->cancellation_reason }}">({{ \Illuminate\Support\Str::limit($item->cancellation_reason, 60) }})</span>
                                                @endif
                                            </p>
                                            @endif
                                            @if((int) $item->restocked_quantity > 0)
                                            <p class="text-muted mb-0">Restocked: <span class="fw-medium">{{ $item->restocked_quantity }}</span></p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>৳{{ number_format($item->price, 2) }}</td>
                                <td>
                                    {{ $displayQuantity }}
                                    @if($itemScope === 'active' && $item->hasCancelledQuantity())
                                        <small class="text-muted d-block">from {{ $item->quantity }} ordered</small>
                                    @elseif(!$itemScope && $item->hasCancelledQuantity())
                                        <small class="text-muted d-block">{{ $item->activeQuantity() }} active</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $review = $order->reviews->where('product_id', $item->product_id)->first();
                                        $rating = $review ? $review->rating : 0;
                                    @endphp
                                    <div class="text-warning fs-15">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="ri-star{{ $i <= $rating ? '-fill' : '-line' }}"></i>
                                        @endfor
                                    </div>
                                </td>
                                <td class="fw-medium text-end">
                                    ৳{{ number_format($displayItemSubtotal, 2) }}
                                </td>
                                @if($isStockRestockableStatus)
                                <td class="text-end">
                                    @if($order->stock_restocked_at || $itemRestockableQuantity <= 0)
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="ri-check-line align-middle me-1"></i> Restocked
                                        </span>
                                    @else
                                        @can('orders.update')
                                            <button type="button"
                                                class="btn btn-success btn-sm restock-item-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#restockItemModal"
                                                data-action="{{ route('orders.update-item-restock', [$order, $item]) }}"
                                                data-product="{{ $item->product_name }}"
                                                data-quantity="{{ $itemRestockableQuantity }}">
                                                <i class="ri-arrow-go-back-line align-middle me-1"></i> Restock
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endcan
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ $isStockRestockableStatus ? 6 : 5 }}" class="text-center text-muted py-4">No items found.</td>
                            </tr>
                            @endforelse
                            <tr class="border-top border-top-dashed">
                                <td colspan="{{ $isStockRestockableStatus ? 4 : 3 }}"></td>
                                <td colspan="2" class="fw-medium p-0">
                                    <table class="table table-borderless mb-0">
                                        <tbody>
                                            <tr>
                                                <td>Sub Total :</td>
                                                <td class="text-end">৳{{ number_format($displaySubtotal, 2) }}</td>
                                            </tr>
                                            @if($displayTax > 0)
                                            <tr>
                                                <td>Tax :</td>
                                                <td class="text-end">৳{{ number_format($displayTax, 2) }}</td>
                                            </tr>
                                            @endif
                                            @if($displayDiscount > 0)
                                            <tr>
                                                <td>Discount @if($order->coupon_code)<span class="text-muted">({{ $order->coupon_code }})</span>@endif :</td>
                                                <td class="text-end">-৳{{ number_format($displayDiscount, 2) }}</td>
                                            </tr>
                                            @endif
                                            @if($displayShippingCost > 0)
                                            <tr>
                                                <td>Shipping Charge :</td>
                                                <td class="text-end">৳{{ number_format($displayShippingCost, 2) }}</td>
                                            </tr>
                                            @endif
                                            <tr class="border-top border-top-dashed">
                                                <th scope="row">Total (BDT) :</th>
                                                <th class="text-end">৳{{ number_format($displayTotal, 2) }}</th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end card-->
        <div class="card">
            <div class="card-header">
                <div class="d-sm-flex align-items-center">
                    <h5 class="card-title flex-grow-1 mb-0">Order Status</h5>
                    <div class="flex-shrink-0 mt-2 mt-sm-0">
                        @php
                            $statusColors = [
                                'pending' => 'warning',
                                'confirmed' => 'info',
                                'processing' => 'primary',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                'partial_delivered' => 'success',
                                'cancelled' => 'danger'
                            ];
                            $currentStatusColor = $statusColors[$order->order_status] ?? 'secondary';
                            $statusLabels = [
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Packaging',
                                'shipped' => 'In Courier',
                                'delivered' => 'Delivered',
                                'partial_delivered' => 'Partial Delivered',
                                'cancelled' => 'Cancelled'
                            ];
                            $currentStatusLabel = $statusLabels[$order->order_status] ?? ucfirst(str_replace('_', ' ', $order->order_status));
                            $nextStatus = [
                                'pending' => 'confirmed',
                                'confirmed' => 'processing',
                                'processing' => 'shipped',
                                'shipped' => 'delivered',
                            ][$order->order_status] ?? null;
                            $nextStatusLabel = $nextStatus ? ($statusLabels[$nextStatus] ?? ucfirst(str_replace('_', ' ', $nextStatus))) : null;
                            $canCancelOrder = in_array($order->order_status, ['pending', 'confirmed', 'processing', 'shipped'], true);
                        @endphp
                        <span class="badge bg-{{ $currentStatusColor }}-subtle text-{{ $currentStatusColor }} text-uppercase" id="current-status-badge">{{ $currentStatusLabel }}</span>
                    </div>
                </div>
                <div class="mt-3">
                    @if($order->order_status === 'cancelled')
                        <div class="alert alert-danger-subtle text-danger mb-3">
                            Cancelled orders cannot be status updated.
                        </div>
                        @if($order->stock_restocked_at)
                            <span class="badge bg-success-subtle text-success">
                                <i class="ri-check-line align-middle me-1"></i> Restocked
                            </span>
                        @else
                            <div class="text-muted small">
                                Use the product row restock button to add stock back item by item.
                            </div>
                        @endif
                    @elseif($isStockRestockableStatus)
                        <div class="alert alert-warning-subtle text-warning mb-3">
                            This order is partial delivered.
                        </div>
                        @if($order->stock_restocked_at)
                            <span class="badge bg-success-subtle text-success">
                                <i class="ri-check-line align-middle me-1"></i> Restocked
                            </span>
                        @else
                            <div class="text-muted small">
                                Use the product row restock button to add stock back item by item.
                            </div>
                        @endif
                    @elseif($nextStatus || $canCancelOrder)
                        @if (in_array($nextStatus,['pending','confirmed','processing']))
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                @if($nextStatus)
                                    <form id="next-order-status-form" class="update-status-form" method="POST" action="{{ route('orders.update-status', $order->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="order_status" value="{{ $nextStatus }}">
                                        @if(request('return_url'))
                                            <input type="hidden" name="return_url" value="{{ request('return_url') }}">
                                        @endif
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nextOrderStatusModal">
                                            <i class="ri-arrow-right-line align-middle me-1"></i> {{ $nextStatusLabel }}
                                        </button>
                                    </form>
                                @endif
                                @if($canCancelOrder)
                                    <form id="cancel-order-status-form" class="update-status-form" method="POST" action="{{ route('orders.update-status', $order->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="order_status" value="cancelled">
                                        @if(request('return_url'))
                                            <input type="hidden" name="return_url" value="{{ request('return_url') }}">
                                        @endif
                                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelOrderStatusModal">
                                            <i class="ri-close-circle-line align-middle me-1"></i> Cancelled
                                        </button>
                                    </form>
                                @endif
                                <div class="text-muted small">Current status: {{ $currentStatusLabel }}</div>
                            </div>
                        @endif
                    @else
                        <div class="alert alert-success-subtle text-success mb-0">
                            This order is already delivered. No further status update is available.
                        </div>
                    @endif
                    <div id="order-status-message" class="alert d-none mt-3 mb-0" role="alert"></div>
                </div>
            </div>
            <div class="card-body">
                <div class="profile-timeline">
                    <div class="accordion accordion-flush" id="accordionFlushExample">

                        @foreach($order->timelines as $index => $timeline)
                            <div class="accordion-item border-0">
                                <div class="accordion-header" id="headingCancelled">
                                    <a class="accordion-button p-2 shadow-none" data-bs-toggle="collapse"
                                        href="#collapseCancelled" aria-expanded="true">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 avatar-xs">
                                                <div class="avatar-title bg-danger text-white rounded-circle shadow">
                                                    <i class="ri-close-circle-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="fs-15 mb-0 fw-semibold">{{ ucfirst($timeline->status) }} - <span class="fw-normal">{{ $timeline->updated_at->format('D, d M Y') }}</span></h6>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div id="collapseCancelled" class="accordion-collapse collapse show" aria-labelledby="headingCancelled"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body ms-2 ps-5 pt-0">
                                        <h6 class="mb-1">{{ $timeline->description }}</h6>
                                        <p class="text-muted">{{ $timeline->updated_at->format('D, d M Y - h:i A') }}</p>
                                        @if($timeline->updater)
                                            <p class="text-muted mb-0">Updated by: {{ $timeline->updater->name }}</p>
                                        @endif
                                        @if($timeline->notes)
                                        <p class="text-muted mb-0">{{ $timeline->notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($order->order_status == 'cancelled')
                            <div class="accordion-item border-0">
                                <div class="accordion-header" id="headingCancelled">
                                    <a class="accordion-button p-2 shadow-none" data-bs-toggle="collapse"
                                        href="#collapseCancelled" aria-expanded="true">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 avatar-xs">
                                                <div class="avatar-title bg-danger text-white rounded-circle shadow">
                                                    <i class="ri-close-circle-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="fs-15 mb-0 fw-semibold">Order Cancelled - <span
                                                        class="fw-normal">{{ $order->updated_at->format('D, d M Y') }}</span></h6>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div id="collapseCancelled" class="accordion-collapse collapse show" aria-labelledby="headingCancelled"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body ms-2 ps-5 pt-0">
                                        <h6 class="mb-1">This order has been cancelled.</h6>
                                        <p class="text-muted">{{ $order->updated_at->format('D, d M Y - h:i A') }}</p>
                                        @if($order->order_notes)
                                        <p class="text-muted mb-0">{{ $order->order_notes }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <!--end accordion-->
                </div>
            </div>
        </div>
        <!--end card-->
    </div>
    <!--end col-->
    <div class="col-xl-3">
        <div class="card">
            <div class="card-header">
                <div class="d-flex">
                    <h5 class="card-title flex-grow-1 mb-0"><i
                            class="mdi mdi-truck-fast-outline align-middle me-1 text-muted"></i> Logistics Details</h5>
                    <div class="flex-shrink-0">
                        <a href="javascript:void(0);" class="badge bg-primary-subtle text-primary fs-11">Track Order</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop"
                        colors="primary:#4b38b3,secondary:#0ab39c" style="width:80px;height:80px"></lord-icon>
                    @if($order->shipping_method)
                    <h5 class="fs-16 mt-2">{{ $order->shipping_method }}</h5>
                    @else
                    <h5 class="fs-16 mt-2">Standard Shipping</h5>
                    @endif
                    <p class="text-muted mb-0">Order ID: {{ $order->order_number }}</p>
                    <p class="text-muted mb-0">Source: {{ $order->order_source ? ucfirst(str_replace('_', ' ', $order->order_source)) : 'Website' }}</p>
                    <p class="text-muted mb-0">Created By: {{ $order->creator?->name ?? 'Customer' }} @if($order->creator)<span class="text-muted fs-11">(ID: {{ $order->creator->id }})</span>@endif</p>
                    @php
                        $paymentMethods = [
                            'cash_on_delivery' => 'Cash on Delivery',
                            'bkash' => 'bKash',
                            'nagad' => 'Nagad',
                            'rocket' => 'Rocket',
                            'ssl_commerce' => 'SSL Commerce'
                        ];
                    @endphp
                    <p class="text-muted mb-0">Payment Mode: {{ $paymentMethods[$order->payment_method] ?? ucfirst(str_replace('_', ' ', $order->payment_method)) }}</p>
                </div>
                @if($order->steadfast_consignment_id || $order->steadfast_tracking_code)
                <div class="border-top pt-3 mt-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0">
                            <p class="text-muted mb-0">Courier:</p>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <h6 class="mb-0">Steadfast</h6>
                        </div>
                    </div>
                    @if($order->steadfast_consignment_id)
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0">
                            <p class="text-muted mb-0">Consignment ID:</p>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <h6 class="mb-0">{{ $order->steadfast_consignment_id }}</h6>
                        </div>
                    </div>
                    @endif
                    @if($order->steadfast_tracking_code)
                    <div class="d-flex align-items-center mb-2">
                        <div class="flex-shrink-0">
                            <p class="text-muted mb-0">Tracking Code:</p>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <h6 class="mb-0">{{ $order->steadfast_tracking_code }}</h6>
                        </div>
                    </div>
                    @endif
                    @if($order->steadfast_status)
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <p class="text-muted mb-0">Courier Status:</p>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <span class="badge bg-info-subtle text-info text-uppercase">{{ str_replace('_', ' ', $order->steadfast_status) }}</span>
                        </div>
                    </div>
                    @endif
                    @if((float) $order->steadfast_delivery_charges > 0)
                    <div class="d-flex align-items-center mt-2">
                        <div class="flex-shrink-0">
                            <p class="text-muted mb-0">Delivery Charge:</p>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <h6 class="mb-0">৳{{ number_format((float) $order->steadfast_delivery_charges, 2) }}</h6>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        <!--end card-->

        <div class="card">
            <div class="card-header">
                <div class="d-flex">
                    <h5 class="card-title flex-grow-1 mb-0">Customer Details</h5>
                    <div class="flex-shrink-0">
                        <a href="javascript:void(0);" class="link-secondary">View Profile</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 vstack gap-3">
                    <li>
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                @if($order->user && $order->user->avatar)
                                    <img src="{{ api_asset($order->user->avatar) }}" alt="{{ $order->customer_name }}" class="avatar-sm rounded shadow">
                                @else
                                    <img src="{{ URL::asset('build/images/users/avatar-3.jpg') }}" alt="{{ $order->customer_name }}" class="avatar-sm rounded shadow">
                                @endif
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="fs-14 mb-1">{{ $order->customer_name }}</h6>
                                <p class="text-muted mb-0">Customer</p>
                            </div>
                        </div>
                    </li>
                    <li><i class="ri-mail-line me-2 align-middle text-muted fs-16"></i>{{ $order->customer_email }}</li>
                    @if($order->customer_phone)
                    <li><i class="ri-phone-line me-2 align-middle text-muted fs-16"></i>{{ $order->customer_phone }}</li>
                    @endif
                    @if($order->user)
                    <li>
                        <a href="javascript:void(0);" class="link-secondary">
                            <i class="ri-user-line me-2 align-middle text-muted fs-16"></i>View Profile
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
        <!--end card-->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-map-pin-line align-middle me-1 text-muted"></i> Billing
                    Address</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled vstack gap-2 fs-13 mb-0">
                    <li class="fw-medium fs-14">{{ $order->customer_name }}</li>
                    @if($order->customer_phone)
                    <li>{{ $order->customer_phone }}</li>
                    @endif
                    <li>{{ $order->customer_email }}</li>
                    @if($order->shippingAddress)
                        @php
                            $addressParts = [];
                            if($order->shippingAddress->address) $addressParts[] = $order->shippingAddress->address;
                            if($order->shippingAddress->deliveryArea) $addressParts[] = $order->shippingAddress->deliveryArea->name;
                            if($order->shippingAddress->deliveryArea) $addressParts[] = $order->shippingAddress->deliveryArea->district_name;
                        @endphp
                        @foreach($addressParts as $part)
                        <li>{{ $part }}</li>
                        @endforeach
                    @else
                        <li class="text-muted">No billing address available</li>
                    @endif
                </ul>
            </div>
        </div>
        <!--end card-->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-map-pin-line align-middle me-1 text-muted"></i> Shipping
                    Address</h5>
            </div>
            <div class="card-body">
                @if($order->shippingAddress)
                <ul class="list-unstyled vstack gap-2 fs-13 mb-0">
                    <li class="fw-medium fs-14">{{ $order->shippingAddress->name ?? $order->customer_name }}</li>
                    @if($order->shippingAddress->phone)
                    <li>{{ $order->shippingAddress->phone }}</li>
                    @endif
                    @if($order->shippingAddress->email)
                    <li>{{ $order->shippingAddress->email }}</li>
                    @endif
                    @if($order->shippingAddress->address)
                    <li>{{ $order->shippingAddress->address }}</li>
                    @endif
                    @if($order->shippingAddress->deliveryArea)
                    <li>{{ $order->shippingAddress->deliveryArea->name }}</li>
                    <li>{{ $order->shippingAddress->deliveryArea->district_name }}</li>
                    @endif
                </ul>
                @else
                <ul class="list-unstyled vstack gap-2 fs-13 mb-0">
                    <li class="text-muted">No shipping address available</li>
                </ul>
                @endif
            </div>
        </div>
        <!--end card-->

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ri-secure-payment-line align-bottom me-1 text-muted"></i>
                    Payment Details</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-shrink-0">
                        <p class="text-muted mb-0">Transaction ID:</p>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-0">{{ $order->order_number }}</h6>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-shrink-0">
                        <p class="text-muted mb-0">Payment Method:</p>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-0">{{ $paymentMethods[$order->payment_method] ?? ucfirst(str_replace('_', ' ', $order->payment_method)) }}</h6>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-shrink-0">
                        <p class="text-muted mb-0">Payment Status:</p>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        @php
                            $paymentStatusColors = [
                                'pending' => 'warning',
                                'paid' => 'success',
                                'failed' => 'danger',
                                'refunded' => 'info'
                            ];
                            $paymentStatusColor = $paymentStatusColors[$order->payment_status] ?? 'secondary';
                        @endphp
                        <span class="badge bg-{{ $paymentStatusColor }}-subtle text-{{ $paymentStatusColor }} text-uppercase">{{ ucfirst($order->payment_status) }}</span>
                    </div>
                </div>
                @if($order->payment_method != 'cash_on_delivery')
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-shrink-0">
                        <p class="text-muted mb-0">Card Holder Name:</p>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-0">{{ $order->customer_name }}</h6>
                    </div>
                </div>
                @endif
                @if((float) $order->steadfast_cod_charger > 0)
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-shrink-0">
                        <p class="text-muted mb-0">Steadfast COD Charge:</p>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-0">৳{{ number_format((float) $order->steadfast_cod_charger, 2) }}</h6>
                    </div>
                </div>
                @endif
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <p class="text-muted mb-0">Total Amount:</p>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <h6 class="mb-0">৳{{ number_format($displayTotal, 2) }}</h6>
                    </div>
                </div>
            </div>
        </div>
        <!--end card-->
    </div>
    <!--end col-->
</div>

@if(($canRestockOrder ?? false))
<div class="modal fade" id="restockItemModal" tabindex="-1" aria-labelledby="restockItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-5 text-center">
                <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#0ab39c,secondary:#405189" style="width:90px;height:90px">
                </lord-icon>
                <div class="mt-4 text-center">
                    <h4 id="restockItemModalLabel">Restock product quantity?</h4>
                    <p class="text-muted fs-15 mb-4">
                        This will add <span class="fw-semibold" id="restock-item-quantity"></span> unit(s) of
                        <span class="fw-semibold" id="restock-item-name"></span> back to inventory.
                    </p>
                    <form method="POST" id="restock-item-form" action="#">
                        @csrf
                        <div class="hstack gap-2 justify-content-center">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-check-line align-middle me-1"></i> Confirm Restock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($nextStatus ?? false)
<div class="modal fade" id="nextOrderStatusModal" tabindex="-1" aria-labelledby="nextOrderStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-5 text-center">
                <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:90px;height:90px">
                </lord-icon>
                <div class="mt-4 text-center">
                    <h4 id="nextOrderStatusModalLabel">Update order status?</h4>
                    <p class="text-muted fs-15 mb-4">
                        This will move <span class="fw-semibold">{{ $order->order_number }}</span> from {{ $currentStatusLabel }} to {{ $nextStatusLabel }}.
                    </p>
                    <div class="hstack gap-2 justify-content-center">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" form="next-order-status-form">
                            <i class="ri-check-line align-middle me-1"></i> Confirm Update
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($canCancelOrder ?? false)
<div class="modal fade" id="cancelOrderStatusModal" tabindex="-1" aria-labelledby="cancelOrderStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-5 text-center">
                <lord-icon src="https://cdn.lordicon.com/tdrtiskw.json" trigger="loop" colors="primary:#f06548,secondary:#f7b84b" style="width:90px;height:90px">
                </lord-icon>
                <div class="mt-4 text-center">
                    <h4 id="cancelOrderStatusModalLabel">Cancel this order?</h4>
                    <p class="text-muted fs-15 mb-4">
                        This will move <span class="fw-semibold">{{ $order->order_number }}</span> to Cancelled status.
                    </p>
                    <div class="mb-4 text-start">
                        <label for="cancel-order-reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea
                            class="form-control"
                            id="cancel-order-reason"
                            name="cancellation_reason"
                            form="cancel-order-status-form"
                            rows="3"
                            maxlength="1000"
                            required
                            placeholder="Write the reason for cancelling this order"></textarea>
                    </div>
                    <div class="hstack gap-2 justify-content-center">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger" form="cancel-order-status-form">
                            <i class="ri-close-circle-line align-middle me-1"></i> Confirm Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const restockItemModal = document.getElementById('restockItemModal');
    const restockItemForm = document.getElementById('restock-item-form');
    const restockItemName = document.getElementById('restock-item-name');
    const restockItemQuantity = document.getElementById('restock-item-quantity');

    if (restockItemModal && restockItemForm) {
        restockItemModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            restockItemForm.action = button.getAttribute('data-action') || '#';

            if (restockItemName) {
                restockItemName.textContent = button.getAttribute('data-product') || 'this product';
            }

            if (restockItemQuantity) {
                restockItemQuantity.textContent = button.getAttribute('data-quantity') || '0';
            }
        });
    }

    const forms = document.querySelectorAll('.update-status-form');
    const statusBadge = document.getElementById('current-status-badge');
    const statusMessage = document.getElementById('order-status-message');

    function showOrderStatusMessage(type, message) {
        if (statusMessage) {
            statusMessage.className = `alert alert-${type} mt-3 mb-0`;
            statusMessage.textContent = message;
        }

        const flasherType = type === 'danger' ? 'error' : type;
        if (typeof flasher !== 'undefined' && typeof flasher[flasherType] === 'function') {
            flasher[flasherType](message);
        }
    }

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]') || document.querySelector(`button[type="submit"][form="${form.id}"]`);
            const originalText = submitButton ? submitButton.innerHTML : '';
            if (statusMessage) {
                statusMessage.className = 'alert d-none mt-3 mb-0';
                statusMessage.textContent = '';
            }

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="ri-loader-4-line align-middle me-1 spin"></i> Updating...';
            }

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                }
            })
            .then(async response => {
                const text = await response.text();
                let data = {};

                try {
                    data = text ? JSON.parse(text) : {};
                } catch (error) {
                    data = { message: text || 'Failed to update status' };
                }

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to update status');
                }

                return data;
            })
            .then(data => {
                if (data.success) {
                    // Update badge
                    const statusColors = {
                        'pending': 'warning',
                        'confirmed': 'info',
                        'processing': 'primary',
                        'shipped': 'info',
                        'delivered': 'success',
                        'cancelled': 'danger'
                    };
                    const newStatus = data.status;
                    const color = statusColors[newStatus] || 'secondary';

                    statusBadge.className = `badge bg-${color}-subtle text-${color} text-uppercase`;
                    statusBadge.textContent = data.status_label;

                    // Show success message
                    showOrderStatusMessage('success', data.message || 'Order status updated successfully!');

                    // Move to the updated status list after showing the message.
                    setTimeout(() => {
                        try {
                            sessionStorage.setItem('order_status_message', JSON.stringify({
                                type: 'success',
                                message: data.message || 'Order status updated successfully!'
                            }));
                        } catch (error) {
                            // Ignore storage issues and still redirect.
                        }

                        window.location.href = data.redirect_url || '{{ route('orders.index') }}';
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showOrderStatusMessage('danger', error.message || 'Failed to update order status. Please try again.');
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            });
        });
    });
});
</script>
<style>
.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
@endsection
