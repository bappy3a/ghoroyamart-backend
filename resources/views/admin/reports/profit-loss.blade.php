@extends('layouts.master')
@section('title', 'Profit / Loss Report')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1', 'Reports')
        @slot('title', 'Profit / Loss Report')
    @endcomponent

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('profit-loss-report.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="from">From</label>
                    <input type="date" class="form-control @error('from') is-invalid @enderror" id="from" name="from" value="{{ old('from', $from->format('Y-m-d')) }}">
                    @error('from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="to">To</label>
                    <input type="date" class="form-control @error('to') is-invalid @enderror" id="to" name="to" value="{{ old('to', $to->format('Y-m-d')) }}">
                    @error('to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Order status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="delivered" @selected($status === 'delivered')>Delivered & partial delivered</option>
                        <option value="all" @selected($status === 'all')>All orders</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="ri-filter-3-line me-1"></i>Apply</button>
                    <a class="btn btn-light" href="{{ route('profit-loss-report.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach([
            ['Orders', number_format($summary->orders), 'ri-shopping-bag-line', 'primary'],
            ['Revenue', '৳'.number_format($summary->net_sales, 2), 'ri-money-dollar-circle-line', 'info'],
            ['Purchase Price', '৳'.number_format($summary->cost, 2), 'ri-price-tag-3-line', 'warning'],
            ['Courier Charges', '৳'.number_format($summary->courier_charges, 2), 'ri-truck-line', 'secondary'],
            [$summary->profit >= 0 ? 'Net Profit' : 'Net Loss', '৳'.number_format(abs($summary->profit), 2), $summary->profit >= 0 ? 'ri-line-chart-line' : 'ri-line-chart-fill', $summary->profit >= 0 ? 'success' : 'danger'],
        ] as [$label, $value, $icon, $color])
            <div class="col-xl col-md-6">
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
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="card-title mb-1">Report details</h4>
                <span class="text-muted">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
            </div>
            <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                <div class="text-muted small text-end">
                    Gross sales ৳{{ number_format($summary->gross_sales, 2) }} · Discounts ৳{{ number_format($summary->discounts, 2) }}<br>
                    Shipping collected ৳{{ number_format($summary->shipping_collected, 2) }} · Tax collected ৳{{ number_format($summary->tax_collected, 2) }}<br>
                    Steadfast delivery ৳{{ number_format($summary->steadfast_delivery_charges, 2) }} · Steadfast COD ৳{{ number_format($summary->steadfast_cod_charger, 2) }}
                </div>
                <a
                    class="btn btn-success"
                    href="{{ route('profit-loss-report.index', array_merge(request()->except('page'), ['export' => 'csv'])) }}"
                >
                    <i class="ri-file-download-line me-1"></i>Export CSV
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Purchase Price</th>
                            <th class="text-end">Delivery Charge</th>
                            <th class="text-end">COD Charge</th>
                            <th class="text-end">Profit / Loss</th>
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
                                <td><span class="badge bg-light text-dark text-capitalize">{{ $order->order_status }}</span></td>
                                <td class="text-end">{{ number_format($order->report_units) }}</td>
                                <td class="text-end">৳{{ number_format($order->report_net_sales, 2) }}</td>
                                <td class="text-end">৳{{ number_format($order->report_cost, 2) }}</td>
                                <td class="text-end">৳{{ number_format($order->report_steadfast_delivery_charges, 2) }}</td>
                                <td class="text-end">৳{{ number_format($order->report_steadfast_cod_charger, 2) }}</td>
                                <td class="text-end fw-semibold {{ $order->report_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $order->report_profit < 0 ? '-' : '' }}৳{{ number_format(abs($order->report_profit), 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-5">No orders found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paginatedOrders->hasPages())
            <div class="card-footer">{{ $paginatedOrders->links() }}</div>
        @endif
    </div>

    <div class="alert alert-info">
        <i class="ri-information-line me-1"></i>
        Net profit is product sales plus shipping collected, minus purchase price, Steadfast delivery charges, and Steadfast COD charges. Tax, salaries, and other operating expenses are not included.
    </div>
@endsection
