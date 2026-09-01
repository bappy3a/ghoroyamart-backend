@extends('layouts.master')

@section('title', 'Flash Deals')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Flash Deals</h4>
                    <p class="text-muted mb-0">Manage time-limited flash deals for products.</p>
                </div>
                <a href="{{ route('flash-deals.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Flash Deal
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
                            <th>Banner</th>
                            <th>Title</th>
                            <th>Products</th>
                            <th>Discount</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($flashDeals as $flashDeal)
                            <tr>
                                <td>
                                    @if($flashDeal->banner_image)
                                        <img src="{{ api_asset($flashDeal->banner_image) }}" alt="{{ $flashDeal->title }}"
                                             class="rounded" style="width: 80px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 50px; background-color: {{ $flashDeal->background_color ?? '#ff6b6b' }};">
                                            <span class="text-white small">{{ $flashDeal->title }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $flashDeal->title }}</strong>
                                    @if($flashDeal->description)
                                        <div class="text-muted small text-truncate" style="max-width: 260px;">
                                            {{ \Illuminate\Support\Str::limit($flashDeal->description, 60) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $productCount = is_array($flashDeal->product_ids) ? count($flashDeal->product_ids) : 0;
                                    @endphp
                                    <span class="badge bg-info-subtle text-info">
                                        {{ $productCount }} {{ $productCount == 1 ? 'Product' : 'Products' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-semibold text-danger">{{ $flashDeal->discount_percentage }}%</span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>From: {{ $flashDeal->start_date->format('d M Y') }}</div>
                                        <div>To: {{ $flashDeal->end_date->format('d M Y') }}</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $flashDeal->status_badge_class }}">
                                        {{ $flashDeal->status_text }}
                                    </span>
                                </td>
                                <td>{{ $flashDeal->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('flash-deals.edit', $flashDeal) }}" class="btn btn-sm btn-warning">
                                        <i class="ri-pencil-line align-middle me-1"></i>Edit
                                    </a>
                                    <a href="#" 
                                       class="btn btn-sm btn-danger"
                                       data-delete-url="{{ route('flash-deals.destroy', $flashDeal) }}"
                                       data-delete-title="Delete Flash Deal?"
                                       data-delete-message="Are you sure you want to delete this flash deal? This action cannot be undone.">
                                        <i class="ri-delete-bin-line align-middle me-1"></i>Delete
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No flash deals yet. <a href="{{ route('flash-deals.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $flashDeals->links() }}
            </div>
        </div>
    </div>
@endsection
