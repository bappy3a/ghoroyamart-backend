@extends('layouts.master')

@section('title', 'Brands')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Brands</h4>
                    <p class="text-muted mb-0">Manage partner logos, featured status, and display order.</p>
                </div>
                <a href="{{ route('brands.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Brand
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
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Order</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                            <tr>
                                <td style="width: 70px;">
                                    @if($brand->logo)
                                        <img
                                            src="{{ api_asset($brand->logo) }}"
                                            alt="{{ $brand->name }} logo"
                                            class="rounded border"
                                            style="height: 48px; width: 48px; object-fit: cover;"
                                        >
                                    @else
                                        <div
                                            class="bg-light border rounded d-flex align-items-center justify-content-center text-muted"
                                            style="height: 48px; width: 48px;"
                                        >
                                            <i class="ri-image-line"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $brand->name }}</strong>
                                    @if($brand->description)
                                        <div class="text-muted small text-truncate" style="max-width: 260px;">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($brand->description), 80) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($brand->status)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    @if($brand->is_featured)
                                        <span class="badge bg-warning-subtle text-warning">
                                            <i class="ri-star-fill align-middle me-1"></i>Featured
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $brand->sort_order }}</td>
                                <td>{{ $brand->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this brand?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No brands yet. <a href="{{ route('brands.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $brands->links() }}
            </div>
        </div>
    </div>
@endsection

