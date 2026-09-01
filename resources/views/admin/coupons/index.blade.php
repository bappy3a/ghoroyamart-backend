@extends('layouts.master')

@section('title', 'Coupons')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Coupons</h4>
                    <p class="text-muted mb-0">Manage discount coupons for products and orders.</p>
                </div>
                <a href="{{ route('coupons.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Coupon
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Discount</th>
                            <th>Valid Period</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr>
                                <td>
                                    <strong class="text-primary">{{ $coupon->code }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $coupon->name }}</strong>
                                    @if($coupon->description)
                                        <div class="text-muted small text-truncate" style="max-width: 260px;">
                                            {{ \Illuminate\Support\Str::limit($coupon->description, 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($coupon->type === 'product_wise')
                                        <span class="badge bg-info-subtle text-info">
                                            <i class="ri-shopping-bag-line align-middle me-1"></i>Product Wise
                                        </span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary">
                                            <i class="ri-shopping-cart-line align-middle me-1"></i>Order Based
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($coupon->discount_type === 'percentage')
                                        <span class="fw-semibold">{{ $coupon->discount_value }}%</span>
                                    @else
                                        <span class="fw-semibold">৳{{ number_format($coupon->discount_value, 2) }}</span>
                                    @endif
                                    @if($coupon->type === 'order_based' && $coupon->minimum_order_amount > 0)
                                        <div class="text-muted small">Min: ৳{{ number_format($coupon->minimum_order_amount, 2) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small">
                                        <div>From: {{ $coupon->valid_from->format('d M Y') }}</div>
                                        <div>To: {{ $coupon->valid_to->format('d M Y') }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>Used: {{ $coupon->used_count }}</div>
                                        @if($coupon->usage_limit)
                                            <div>Limit: {{ $coupon->usage_limit }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $coupon->status_badge_class }}">
                                        {{ $coupon->status_text }}
                                    </span>
                                </td>
                                <td>{{ $coupon->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('coupons.edit', $coupon) }}" class="btn btn-sm btn-warning">
                                        <i class="ri-pencil-line align-middle me-1"></i>Edit
                                    </a>
                                    <a href="#" 
                                       class="btn btn-sm btn-danger"
                                       data-delete-url="{{ route('coupons.destroy', $coupon) }}"
                                       data-delete-title="Delete Coupon?"
                                       data-delete-message="Are you sure you want to delete this coupon? This action cannot be undone.">
                                        <i class="ri-delete-bin-line align-middle me-1"></i>Delete
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No coupons yet. <a href="{{ route('coupons.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
@endsection
