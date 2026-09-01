@extends('layouts.master')
@section('title', 'Moderator Order Report')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1', 'Reports')
        @slot('title', 'Moderator Order Report')
    @endcomponent

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('moderator-order-report.index') }}" class="row g-3 align-items-end">
                <div class="col-xl-2 col-md-4">
                    <label class="form-label" for="from">From</label>
                    <input type="date" class="form-control @error('from') is-invalid @enderror" id="from" name="from" value="{{ old('from', $from->format('Y-m-d')) }}">
                    @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label" for="to">To</label>
                    <input type="date" class="form-control @error('to') is-invalid @enderror" id="to" name="to" value="{{ old('to', $to->format('Y-m-d')) }}">
                    @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-xl-3 col-md-4">
                    <label class="form-label" for="moderator_id">Moderator</label>
                    <select class="form-select @error('moderator_id') is-invalid @enderror" id="moderator_id" name="moderator_id">
                        <option value="">All moderators</option>
                        @foreach($moderators as $moderator)
                            <option value="{{ $moderator->id }}" @selected($moderatorId === $moderator->id)>{{ $moderator->name }} (ID: {{ $moderator->id }})</option>
                        @endforeach
                    </select>
                    @error('moderator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-xl-2 col-md-4">
                    <label class="form-label" for="status">Order status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" @selected($status === 'all')>All statuses</option>
                        @foreach(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $orderStatus)
                            <option value="{{ $orderStatus }}" @selected($status === $orderStatus)>{{ ucfirst($orderStatus) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-xl-3 col-md-8 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="ri-filter-3-line me-1"></i>Apply</button>
                    <a class="btn btn-light" href="{{ route('moderator-order-report.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach([
            ['Moderators', number_format($summary->moderators), 'ri-user-star-line', 'primary'],
            ['Orders Created', number_format($summary->orders), 'ri-shopping-bag-3-line', 'info'],
            ['Units Sold', number_format($summary->units), 'ri-stack-line', 'warning'],
            ['Order Value', '৳'.number_format($summary->order_value, 2), 'ri-money-dollar-circle-line', 'success'],
        ] as [$label, $value, $icon, $color])
            <div class="col-xl-3 col-md-6">
                <div class="card card-animate">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-2">{{ $label }}</p>
                            <h4 class="mb-0">{{ $value }}</h4>
                        </div>
                        <span class="avatar-title bg-{{ $color }}-subtle text-{{ $color }} rounded fs-3" style="width: 44px; height: 44px"><i class="{{ $icon }}"></i></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-1">Performance by moderator</h4>
            <span class="text-muted">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Moderator</th>
                            <th class="text-end">Orders</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Pending</th>
                            <th class="text-end">Confirmed</th>
                            <th class="text-end">Processing</th>
                            <th class="text-end">Shipped</th>
                            <th class="text-end">Delivered</th>
                            <th class="text-end">Cancelled</th>
                            <th class="text-end">Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($moderatorReport as $row)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $row->moderator?->name ?? 'Deleted user' }}</div>
                                    <span class="text-muted fs-12">ID: {{ $row->moderator?->id ?? 'N/A' }}@if($row->moderator?->email) · {{ $row->moderator->email }}@endif</span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ number_format($row->units) }}</td>
                                @foreach(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $orderStatus)
                                    <td class="text-end">{{ number_format($row->status_counts[$orderStatus]) }}</td>
                                @endforeach
                                <td class="text-end fw-semibold">৳{{ number_format($row->order_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center text-muted py-5">No moderator-created orders found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Order details</h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Moderator</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedOrders as $order)
                            <tr>
                                <td>
                                    @can('orders.details')
                                        <a href="{{ route('orders.view', $order->order_number) }}" class="fw-medium">{{ $order->order_number }}</a>
                                    @else
                                        <span class="fw-medium">{{ $order->order_number }}</span>
                                    @endcan
                                </td>
                                <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $order->creator?->name ?? 'Deleted user' }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td><span class="badge bg-light text-dark text-capitalize">{{ $order->order_status }}</span></td>
                                <td class="text-end">{{ number_format($order->report_units) }}</td>
                                <td class="text-end fw-semibold">৳{{ number_format((float) $order->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No orders found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paginatedOrders->hasPages())
            <div class="card-footer">{{ $paginatedOrders->links() }}</div>
        @endif
    </div>
@endsection
