@extends('layouts.master')
@section('title', 'Total Order Report')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1', 'Reports')
        @slot('title', 'Total Order Report')
    @endcomponent

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('total-order-report.index') }}" class="row g-3 align-items-end">
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
                <div class="col-md-6 d-flex gap-2">
                    <button class="btn btn-primary" type="submit"><i class="ri-filter-3-line me-1"></i>Apply</button>
                    <a class="btn btn-light" href="{{ route('total-order-report.index') }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        @foreach([
            ['Total Orders', number_format($summary->orders), 'ri-shopping-bag-3-line', 'primary'],
            ['Delivered', number_format($summary->delivered), 'ri-checkbox-circle-line', 'success'],
            ['Cancelled', number_format($summary->cancelled), 'ri-close-circle-line', 'danger'],
            ['Returned Qty', number_format($summary->returned), 'ri-arrow-go-back-line', 'warning'],
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
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h4 class="card-title mb-1">Day wise total order report</h4>
                <span class="text-muted">{{ $from->format('d M Y') }} - {{ $to->format('d M Y') }}</span>
            </div>
            <div class="text-muted small text-end">
                Units {{ number_format($summary->units) }}<br>
                Order value ৳{{ number_format($summary->order_value, 2) }}
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Total Orders</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Delivered</th>
                            <th class="text-end">Cancelled</th>
                            <th class="text-end">Returned Qty</th>
                            <th class="text-end">Active Orders</th>
                            <th class="text-end">Order Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paginatedDailyReport as $row)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $row->date->format('d M Y') }}</div>
                                    <span class="text-muted fs-12">{{ $row->date->format('l') }}</span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($row->orders) }}</td>
                                <td class="text-end">{{ number_format($row->units) }}</td>
                                <td class="text-end text-success">{{ number_format($row->delivered) }}</td>
                                <td class="text-end text-danger">{{ number_format($row->cancelled) }}</td>
                                <td class="text-end text-warning">{{ number_format($row->returned) }}</td>
                                <td class="text-end">{{ number_format($row->active_orders) }}</td>
                                <td class="text-end fw-semibold">৳{{ number_format($row->order_value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-5">No orders found for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($paginatedDailyReport->hasPages())
            <div class="card-footer">{{ $paginatedDailyReport->links() }}</div>
        @endif
    </div>
@endsection
