@extends('layouts.master')
@section('title', $pageTitle ?? 'Orders')
@section('css')
    <link href="{{ URL::asset('build/libs/choices.js/public/assets/styles/choices.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('build/libs/flatpickr/flatpickr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@php
    $statusLabels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'processing' => 'Packaging',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'delivered_only' => 'Delivered',
        'partial_delivered' => 'Partial Delivered',
        'cancelled' => 'Cancelled',
    ];
    $statusTabs = [
        [
            'status' => 'all',
            'label' => 'All Orders',
            'icon' => 'ri-store-2-fill',
            'badge' => 'primary',
            'count' => $statusCounts['all'] ?? 0,
            'url' => route('orders.index', request()->except('status', 'page')),
            'permission' => 'orders.all',
        ],
        [
            'status' => 'pending',
            'label' => 'Pending',
            'icon' => 'ri-time-line',
            'badge' => 'warning',
            'count' => $statusCounts['pending'] ?? 0,
            'url' => route('orders.pending', request()->except('status', 'page')),
            'permission' => 'orders.pending',
        ],
        [
            'status' => 'confirmed',
            'label' => 'Confirmed',
            'icon' => 'ri-checkbox-circle-line',
            'badge' => 'info',
            'count' => $statusCounts['confirmed'] ?? 0,
            'url' => route('orders.confirmed', request()->except('status', 'page')),
            'permission' => 'orders.confirmed',
        ],
        [
            'status' => 'processing',
            'label' => 'Packaging',
            'icon' => 'ri-archive-line',
            'badge' => 'primary',
            'count' => $statusCounts['processing'] ?? 0,
            'url' => route('orders.packaging', request()->except('status', 'page')),
            'permission' => 'orders.packaging',
        ],
        [
            'status' => 'shipped',
            'label' => 'In Courier',
            'icon' => 'ri-truck-line',
            'badge' => 'info',
            'count' => $statusCounts['shipped'] ?? 0,
            'url' => route('orders.shipped', request()->except('status', 'page')),
            'permission' => 'orders.shipped',
        ],
        [
            'status' => 'delivered',
            'label' => 'Delivered',
            'icon' => 'ri-checkbox-circle-line',
            'badge' => 'success',
            'count' => $statusCounts['delivered'] ?? 0,
            'url' => route('orders.delivered', request()->except('status', 'page')),
            'permission' => 'orders.delivered',
        ],
        [
            'status' => 'cancelled',
            'label' => 'Cancelled',
            'icon' => 'ri-close-circle-line',
            'badge' => 'danger',
            'count' => $statusCounts['cancelled'] ?? 0,
            'url' => route('orders.cancelled', request()->except('status', 'page')),
            'permission' => 'orders.cancelled',
        ],
    ];
    $currentStatus = $currentStatus ?? ((!request('status') || request('status') === 'all') ? 'all' : request('status'));
    $activeTabStatus = in_array($currentStatus, ['delivered_only', 'partial_delivered'], true) ? 'delivered' : $currentStatus;
    $listItemScope = $currentStatus === 'cancelled' ? 'cancelled' : (in_array($currentStatus, ['delivered', 'delivered_only', 'partial_delivered'], true) ? 'active' : null);
    $canBulkUpdateOrders = auth()->user()?->can('orders.update');
    $isPendingBulkList = ($currentStatus ?? 'all') === 'pending' && $canBulkUpdateOrders;
    $isConfirmedBulkList = ($currentStatus ?? 'all') === 'confirmed' && $canBulkUpdateOrders;
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="card" id="orderList">
            <div class="card-header border-0">
                <div class="row align-items-center gy-3">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">{{ $pageTitle ?? 'Order History' }}</h5>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex gap-1 flex-wrap">
                            @canany(['orders.create', 'moderator-order-management.create'])
                                <a href="{{ $createOrderRoute ?? route('orders.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Create Order
                                </a>
                            @endcanany
                            <button type="button" class="btn btn-info"><i class="ri-file-download-line align-bottom me-1"></i> Export</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body border border-dashed border-end-0 border-start-0">
                <form method="GET" action="{{ $formRoute ?? route('orders.index') }}">
                    <div class="row g-3">
                        <div class="col-xxl-5 col-sm-6">
                            <div class="search-box">
                                <input type="text" class="form-control search" name="search" placeholder="Search order ID, customer, product name, or SKU..." value="{{ request('search') }}">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                        </div>
                        <!--end col-->
                        <div class="col-xxl-2 col-sm-6">
                            <div>
                                <input type="text" class="form-control" data-provider="flatpickr" data-date-format="d M, Y" data-range-date="true" id="demo-datepicker" name="date_range" placeholder="Select date range" value="{{ request('date_range') }}">
                            </div>
                        </div>
                        <!--end col-->
                        @if(!($isStatusList ?? false) || ($showStatusFilter ?? false))
                        <div class="col-xxl-2 col-sm-4">
                            <div>
                                <select class="form-control" data-choices data-choices-search-false name="status" id="idStatus">
                                    @if($showStatusFilter ?? false)
                                    <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Delivered</option>
                                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="partial_delivered" {{ request('status') == 'partial_delivered' ? 'selected' : '' }}>Partial Delivered</option>
                                    @else
                                    <option value="">Status</option>
                                    <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Packaging</option>
                                    <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>In Courier</option>
                                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="partial_delivered" {{ request('status') == 'partial_delivered' ? 'selected' : '' }}>Partial Delivered</option>
                                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <!--end col-->
                        @endif
                        <div class="col-xxl-2 col-sm-4">
                            <div>
                                <select class="form-control" data-choices data-choices-search-false name="payment" id="idPayment">
                                    <option value="">Select Payment</option>
                                    <option value="all" {{ request('payment') == 'all' || !request('payment') ? 'selected' : '' }}>All</option>
                                    <option value="cash_on_delivery" {{ request('payment') == 'cash_on_delivery' ? 'selected' : '' }}>Cash on Delivery</option>
                                    <option value="bkash" {{ request('payment') == 'bkash' ? 'selected' : '' }}>bKash</option>
                                    <option value="nagad" {{ request('payment') == 'nagad' ? 'selected' : '' }}>Nagad</option>
                                    <option value="rocket" {{ request('payment') == 'rocket' ? 'selected' : '' }}>Rocket</option>
                                    <option value="ssl_commerce" {{ request('payment') == 'ssl_commerce' ? 'selected' : '' }}>SSL Commerce</option>
                                </select>
                            </div>
                        </div>
                        <!--end col-->
                        <div class="col-xxl-1 col-sm-4">
                            <div>
                                <button type="submit" class="btn btn-primary w-100"> <i class="ri-equalizer-fill me-1 align-bottom"></i>
                                    Filters
                                </button>
                            </div>
                        </div>
                        <!--end col-->
                    </div>
                    <!--end row-->
                </form>
            </div>
            <div class="card-body pt-0">
                <div>
                    @unless($isModeratorOrderManagement ?? false)
                    <ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist">
                        @foreach($statusTabs as $tab)
                            @can($tab['permission'])
                                <li class="nav-item">
                                    <a class="nav-link py-3 {{ $activeTabStatus === $tab['status'] ? 'active' : '' }}" href="{{ $tab['url'] }}" role="tab">
                                        <i class="{{ $tab['icon'] }} me-1 align-bottom"></i> {{ $tab['label'] }} <span class="badge bg-{{ $tab['badge'] }} align-middle ms-1">{{ $tab['count'] }}</span>
                                    </a>
                                </li>
                            @endcan
                        @endforeach
                    </ul>
                    @endunless

                    @if(($currentStatus ?? 'all') === 'pending')
                        @can('orders.update')
                            <form method="POST" action="{{ route('orders.update-bulk-confirm') }}" id="bulk-confirm-form" class="mb-3">
                                @csrf
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary" id="bulk-confirm-button" disabled>
                                        <i class="ri-checkbox-circle-line align-bottom me-1"></i>
                                        Move to Confirmed (<span id="bulk-selected-count">0</span>)
                                    </button>
                                    <span class="text-muted fs-13">Select pending orders from this page.</span>
                                </div>
                            </form>
                        @endcan
                    @endif

                    @if(($currentStatus ?? 'all') === 'confirmed')
                        @can('orders.update')
                            <form method="POST" action="{{ route('orders.update-bulk-packaging') }}" id="bulk-packaging-form" class="mb-3">
                                @csrf
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary" id="bulk-packaging-button" disabled>
                                        <i class="ri-archive-line align-bottom me-1"></i>
                                        Move to Packaging (<span id="bulk-packaging-selected-count">0</span>)
                                    </button>
                                    <span class="text-muted fs-13">Select confirmed orders from this page. Steadfast bulk order will be created first.</span>
                                </div>
                            </form>
                        @endcan
                    @endif

                    <div class="table-responsive table-card mb-1">
                        <table class="table table-nowrap align-middle" id="orderTable">
                            <thead class="text-muted table-light">
                                <tr class="text-uppercase">
                                    <th scope="col" style="width: 25px;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="{{ $isPendingBulkList ? 'pending-check-all' : ($isConfirmedBulkList ? 'confirmed-check-all' : 'checkAll') }}" value="option">
                                        </div>
                                    </th>
                                    <th class="sort" data-sort="id">Order ID</th>
                                    <th class="sort" data-sort="customer_name">Customer</th>
                                    <th class="sort" data-sort="product_name">Product</th>
                                    <th class="sort" data-sort="date">Date</th>
                                    <th class="sort" data-sort="amount">Amount</th>
                                    <th class="sort" data-sort="payment">Payment</th>
                                    <th class="sort" data-sort="source">Source</th>
                                    <th class="sort" data-sort="created_by">Created By</th>
                                    <th class="sort" data-sort="status">Status</th>
                                    @if($isCancelledList ?? false)
                                        <th class="sort" data-sort="cancelled_by">Cancelled By</th>
                                    @endif
                                    <th class="sort" data-sort="city">Action</th>
                                </tr>
                            </thead>
                            <tbody class="list form-check-all">
                                @forelse($orders as $order)
                                @php
                                    $displayItems = $order->displayItems($listItemScope);
                                    $firstItem = $displayItems->first();
                                    $variantLabel = $firstItem?->productVariant?->name ?: $firstItem?->variant_name;
                                    $productSku = $firstItem?->product_sku ?: $firstItem?->productVariant?->sku;
                                    $detailsRouteParams = [
                                        'order' => $order->order_number,
                                        'return_url' => request()->fullUrl(),
                                    ];
                                    if($listItemScope) {
                                        $detailsRouteParams['item_scope'] = $listItemScope;
                                    }
                                @endphp
                                <tr>
                                    <th scope="row">
                                        <div class="form-check">
                                            @if($isPendingBulkList)
                                                <input class="form-check-input pending-order-checkbox" type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulk-confirm-form" aria-label="Select order {{ $order->order_number }}">
                                            @elseif($isConfirmedBulkList)
                                                <input class="form-check-input confirmed-order-checkbox" type="checkbox" name="order_ids[]" value="{{ $order->id }}" form="bulk-packaging-form" aria-label="Select order {{ $order->order_number }}">
                                            @else
                                                <input class="form-check-input" type="checkbox" name="checkAll" value="{{ $order->id }}">
                                            @endif
                                        </div>
                                    </th>
                                    <td class="id">
                                        @can('orders.details')
                                            <a href="{{ route('orders.view', $detailsRouteParams) }}" class="fw-medium link-primary">{{ $order->order_number }}</a>
                                        @else
                                            <span class="fw-medium">{{ $order->order_number }}</span>
                                        @endcan
                                    </td>
                                    <td class="customer_name">{{ $order->customer_name }}</td>
                                    <td class="product_name">
                                        @if($displayItems->count() > 0)
                                            <div class="fw-medium">{{ $firstItem->product_name }}</div>
                                            @if($productSku)
                                                <small class="text-muted d-block">SKU: {{ $productSku }}</small>
                                            @endif
                                            @if($variantLabel)
                                                <small class="text-muted d-block">Variant: {{ $variantLabel }}</small>
                                            @endif
                                            <small class="text-muted d-block">
                                                Qty: {{ $order->displayQuantityForItem($firstItem, $listItemScope) }}
                                                @if($listItemScope === 'cancelled')
                                                    cancelled
                                                @elseif($firstItem->hasCancelledQuantity())
                                                    delivered / {{ $firstItem->cancelledQuantity() }} cancelled
                                                @endif
                                            </small>
                                            @if($displayItems->count() > 1)
                                                <small class="text-muted d-block">+{{ $displayItems->count() - 1 }} more</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No items</span>
                                        @endif
                                    </td>
                                    <td class="date">
                                        {{ $order->created_at->format('d M, Y') }},
                                        <small class="text-muted">{{ $order->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td class="amount">৳{{ number_format($order->displayTotal($listItemScope), 2) }}</td>
                                    <td class="payment">
                                        @php
                                            $paymentMethods = [
                                                'cash_on_delivery' => 'COD',
                                                'bkash' => 'bKash',
                                                'nagad' => 'Nagad',
                                                'rocket' => 'Rocket',
                                                'ssl_commerce' => 'SSL Commerce'
                                            ];
                                        @endphp
                                        {{ $paymentMethods[$order->payment_method] ?? ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                                    </td>
                                    <td class="source">
                                        @if($order->order_source)
                                            {{ ucfirst(str_replace('_', ' ', $order->order_source)) }}
                                        @else
                                            <span class="text-muted">Website</span>
                                        @endif
                                    </td>
                                    <td class="created_by">
                                        @if($order->creator)
                                            {{ $order->creator->name }} <span class="text-muted fs-11">(ID: {{ $order->creator->id }})</span>
                                        @else
                                            <span class="text-muted">Customer</span>
                                        @endif
                                    </td>
                                    <td class="status">
                                        @php
                                        if(in_array($order->order_status, ['shipped', 'all'])){
                                            $color = 'secondary';
                                             $statusLabel = $order->steadfast_status ? ucfirst(str_replace('_', ' ', $order->steadfast_status)) : "In Courier (Pending)";
                                        }
                                        else{
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'confirmed' => 'info',
                                                'processing' => 'primary',
                                                'shipped' => 'info',
                                                'delivered' => 'success',
                                                'delivered_only' => 'success',
                                                'partial_delivered' => 'success',
                                                'cancelled' => 'danger'
                                            ];
                                            $color = $statusColors[$order->order_status] ?? 'secondary';
                                            $statusLabel = $statusLabels[$order->order_status] ?? ucfirst(str_replace('_', ' ', $order->order_status));
                                        }

                                        @endphp
                                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }} text-uppercase">{{ $statusLabel }}</span>
                                    </td>
                                    @if($isCancelledList ?? false)
                                        @php
                                            $cancelledItem = $order->displayItems('cancelled')->first();
                                            $cancellationReason = $cancelledItem?->cancellation_reason ?: $order->cancellationReasonText();
                                            $cancelledAt = $cancelledItem?->cancelled_at ?: $order->cancelled_at;
                                            $cancelledByType = $cancelledItem?->cancelled_by_type ?: $order->cancelled_by_type;
                                            $cancelledActorName = $cancelledItem?->cancellationActor?->name ?: $order->cancellationActor?->name;
                                            $cancelledByLabel = match ($cancelledByType) {
                                                'staff' => $cancelledActorName ? 'Staff: '.$cancelledActorName : 'Staff',
                                                'customer' => 'Customer',
                                                'courier' => 'Courier',
                                                default => $order->cancelledByLabel(),
                                            };
                                        @endphp
                                        <td class="cancelled_by" style="min-width: 220px;">
                                            <div class="fw-semibold">{{ $cancelledByLabel }}</div>
                                            @if($cancellationReason)
                                                <small class="text-muted d-block" title="{{ $cancellationReason }}">
                                                    Reason: {{ \Illuminate\Support\Str::limit($cancellationReason, 90) }}
                                                </small>
                                            @else
                                                <small class="text-muted d-block">Reason not recorded</small>
                                            @endif
                                            @if($cancelledAt)
                                                <small class="text-muted d-block">{{ $cancelledAt->format('d M, Y h:i A') }}</small>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <ul class="list-inline hstack gap-2 mb-0">
                                            @can('orders.details')
                                                <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="View">
                                                    <a href="{{ route('orders.view', $detailsRouteParams) }}" class="text-primary d-inline-block">
                                                        <i class="ri-eye-fill fs-16"></i>
                                                    </a>
                                                </li>
                                                <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Print Thermal Invoice">
                                                    <a href="{{ route('orders.thermal-invoice.print', $order) }}" target="_blank" class="text-info d-inline-block">
                                                        <i class="ri-printer-line fs-16"></i>
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('orders.update')
                                                @if($order->order_status !== 'cancelled')
                                                    <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Edit">
                                                        <a href="{{ route('orders.edit', $order->order_number) }}" class="text-secondary d-inline-block">
                                                            <i class="ri-pencil-fill fs-16"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                            @endcan
                                            @if($order->order_status === 'cancelled')
                                                @if($order->stock_restocked_at)
                                                    <li class="list-inline-item">
                                                        <span class="badge bg-success-subtle text-success">Restocked</span>
                                                    </li>
                                                @else
                                                    @can('orders.update')
                                                        <li class="list-inline-item" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-placement="top" title="Restock">
                                                            <button type="button"
                                                                class="btn btn-sm btn-soft-success restock-order-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#restockOrderModal"
                                                                data-action="{{ route('orders.update-restock', $order->id) }}"
                                                                data-order-number="{{ $order->order_number }}">
                                                                <i class="ri-arrow-go-back-line align-middle me-1"></i> Restock
                                                            </button>
                                                        </li>
                                                    @endcan
                                                @endif
                                            @endif
                                        </ul>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ ($isCancelledList ?? false) ? 12 : 11 }}" class="text-center py-4">
                                        <div class="text-muted">No {{ ($currentStatus ?? 'all') !== 'all' ? strtolower($statusLabels[$currentStatus] ?? $currentStatus).' ' : '' }}orders found</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="noresult" style="display: none">
                            <div class="text-center">
                                <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px">
                                </lord-icon>
                                <h5 class="mt-2">Sorry! No Result Found</h5>
                                <p class="text-muted">We've searched more than 150+ Orders We did
                                    not find any
                                    orders for you search.</p>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <div class="pagination-wrap hstack gap-2">
                            {{ $orders->links() }}
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="restockOrderModal" tabindex="-1" aria-labelledby="restockOrderModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body p-5 text-center">
                                <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop" colors="primary:#0ab39c,secondary:#405189" style="width:90px;height:90px">
                                </lord-icon>
                                <div class="mt-4 text-center">
                                    <h4 id="restockOrderModalLabel">Restock cancelled order?</h4>
                                    <p class="text-muted fs-15 mb-4">
                                        This will add the ordered product quantities back to inventory for <span class="fw-semibold" id="restock-order-number"></span>.
                                    </p>
                                    <form method="POST" id="restock-order-form">
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
                <div class="modal fade" id="showModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-light p-3">
                                <h5 class="modal-title" id="exampleModalLabel">&nbsp;</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
                            </div>
                            <form class="tablelist-form" autocomplete="off">
                                <div class="modal-body">
                                    <input type="hidden" id="id-field" />

                                    <div class="mb-3" id="modal-id">
                                        <label for="orderId" class="form-label">ID</label>
                                        <input type="text" id="orderId" class="form-control" placeholder="ID" readonly />
                                    </div>

                                    <div class="mb-3">
                                        <label for="customername-field" class="form-label">Customer Name</label>
                                        <input type="text" id="customername-field" class="form-control" placeholder="Enter name" required />
                                    </div>

                                    <div class="mb-3">
                                        <label for="productname-field" class="form-label">Product</label>
                                        <select class="form-control" data-trigger name="productname-field" id="productname-field" required />
                                        <option value="">Product</option>
                                        <option value="Puma Tshirt">Puma Tshirt</option>
                                        <option value="Adidas Sneakers">Adidas Sneakers</option>
                                        <option value="350 ml Glass Grocery Container">350 ml Glass Grocery Container</option>
                                        <option value="American egale outfitters Shirt">American egale outfitters Shirt</option>
                                        <option value="Galaxy Watch4">Galaxy Watch4</option>
                                        <option value="Apple iPhone 12">Apple iPhone 12</option>
                                        <option value="Funky Prints T-shirt">Funky Prints T-shirt</option>
                                        <option value="USB Flash Drive Personalized with 3D Print">USB Flash Drive Personalized with 3D Print</option>
                                        <option value="Oxford Button-Down Shirt">Oxford Button-Down Shirt</option>
                                        <option value="Classic Short Sleeve Shirt">Classic Short Sleeve Shirt</option>
                                        <option value="Half Sleeve T-Shirts (Blue)">Half Sleeve T-Shirts (Blue)</option>
                                        <option value="Noise Evolve Smartwatch">Noise Evolve Smartwatch</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="date-field" class="form-label">Order Date</label>
                                        <input type="date" id="date-field" class="form-control" data-provider="flatpickr" required data-date-format="d M, Y" data-enable-time required placeholder="Select date" />
                                    </div>

                                    <div class="row gy-4 mb-3">
                                        <div class="col-md-6">
                                            <div>
                                                <label for="amount-field" class="form-label">Amount</label>
                                                <input type="text" id="amount-field" class="form-control" placeholder="Total amount" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div>
                                                <label for="payment-field" class="form-label">Payment Method</label>
                                                <select class="form-control" data-trigger name="payment-method" required id="payment-field">
                                                    <option value="">Payment Method</option>
                                                    <option value="Mastercard">Mastercard</option>
                                                    <option value="Visa">Visa</option>
                                                    <option value="COD">COD</option>
                                                    <option value="Paypal">Paypal</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="delivered-status" class="form-label">Delivery Status</label>
                                        <select class="form-control" data-trigger name="delivered-status" required id="delivered-status">
                                            <option value="">Delivery Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Inprogress">Inprogress</option>
                                            <option value="Cancelled">Cancelled</option>
                                            <option value="Pickups">Pickups</option>
                                            <option value="Delivered">Delivered</option>
                                            <option value="Returns">Returns</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="hstack gap-2 justify-content-end">
                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success" id="add-btn">Add Order</button>
                                        <!-- <button type="button" class="btn btn-success" id="edit-btn">Update</button> -->
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal -->
                <div class="modal fade flip" id="deleteOrder" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body p-5 text-center">
                                <lord-icon src="https://cdn.lordicon.com/gsqxdxog.json" trigger="loop" colors="primary:#405189,secondary:#f06548" style="width:90px;height:90px">
                                </lord-icon>
                                <div class="mt-4 text-center">
                                    <h4>You are about to delete a order ?</h4>
                                    <p class="text-muted fs-15 mb-4">Deleting your order will remove
                                        all of
                                        your information from our database.</p>
                                    <div class="hstack gap-2 justify-content-center remove">
                                        <button class="btn btn-link link-success fw-medium text-decoration-none" data-bs-dismiss="modal" id="deleteRecord-close"><i class="ri-close-line me-1 align-middle"></i>
                                            Close</button>
                                        <button class="btn btn-danger" id="delete-record">Yes,
                                            Delete It</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end modal -->
            </div>
        </div>

    </div>
    <!--end col-->
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateRangePicker = document.getElementById('demo-datepicker');

    if (dateRangePicker && window.flatpickr && !dateRangePicker._flatpickr) {
        flatpickr(dateRangePicker, {
            mode: 'range',
            dateFormat: 'd M, Y',
            disableMobile: true,
        });
    }

    try {
        const rawStatusMessage = sessionStorage.getItem('order_status_message');

        if (rawStatusMessage) {
            sessionStorage.removeItem('order_status_message');
            const statusMessage = JSON.parse(rawStatusMessage);
            const type = statusMessage.type || 'success';
            const message = statusMessage.message || 'Order status updated successfully!';

            if (typeof flasher !== 'undefined' && typeof flasher[type] === 'function') {
                flasher[type](message);
            }
        }
    } catch (error) {
        sessionStorage.removeItem('order_status_message');
    }

    const restockModal = document.getElementById('restockOrderModal');
    const restockForm = document.getElementById('restock-order-form');
    const orderNumber = document.getElementById('restock-order-number');

    if (restockModal && restockForm) {
        restockModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            restockForm.action = button.getAttribute('data-action') || '#';

            if (orderNumber) {
                orderNumber.textContent = button.getAttribute('data-order-number') || '';
            }
        });
    }

    const setupBulkOrderForm = function (options) {
        const form = document.getElementById(options.formId);
        const checkAll = document.getElementById(options.checkAllId);
        const checkboxes = Array.from(document.querySelectorAll(options.checkboxSelector));
        const button = document.getElementById(options.buttonId);
        const selectedCount = document.getElementById(options.selectedCountId);

        const checkedCount = function () {
            return checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            }).length;
        };

        const updateState = function () {
            const count = checkedCount();

            if (selectedCount) {
                selectedCount.textContent = count;
            }

            if (button) {
                button.disabled = count === 0;
            }

            if (checkAll) {
                checkAll.checked = checkboxes.length > 0 && count === checkboxes.length;
                checkAll.indeterminate = count > 0 && count < checkboxes.length;
            }
        };

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = checkAll.checked;
                });
                updateState();
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateState);
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                const count = checkedCount();

                if (count === 0 || !window.confirm(options.confirmMessage.replace(':count', count))) {
                    event.preventDefault();
                }
            });
        }

        updateState();
    };

    setupBulkOrderForm({
        formId: 'bulk-confirm-form',
        checkAllId: 'pending-check-all',
        checkboxSelector: '.pending-order-checkbox',
        buttonId: 'bulk-confirm-button',
        selectedCountId: 'bulk-selected-count',
        confirmMessage: 'Move :count selected order(s) to Confirmed?',
    });

    setupBulkOrderForm({
        formId: 'bulk-packaging-form',
        checkAllId: 'confirmed-check-all',
        checkboxSelector: '.confirmed-order-checkbox',
        buttonId: 'bulk-packaging-button',
        selectedCountId: 'bulk-packaging-selected-count',
        confirmMessage: 'Create Steadfast bulk order and move :count selected order(s) to Packaging?',
    });
});
</script>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/flatpickr/flatpickr.min.js') }}"></script>
@endsection
