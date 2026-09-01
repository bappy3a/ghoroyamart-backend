@extends('layouts.master')

@section('title', 'Product Variants')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Product Variants</h4>
                    <p class="text-muted mb-0">Manage SKU, stock, selling price, and purchase price for each variant.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('variant-attributes.index') }}" class="btn btn-light">
                        <i class="ri-list-settings-line align-middle me-1"></i>
                        Attributes
                    </a>
                    <a href="{{ route('product-variants.create', request('product_id') ? ['product_id' => request('product_id')] : []) }}" class="btn btn-primary">
                        <i class="ri-add-line align-middle me-1"></i>
                        Generate Variants
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('product-variants.index') }}" class="row g-3 align-items-end mb-4">
                <div class="col-md-5">
                    <label class="form-label">Product</label>
                    <select class="form-select" name="product_id">
                        <option value="">All Products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }}{{ $product->sku ? ' - '.$product->sku : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search product or SKU">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Image</th>
                            <th>Variant</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>Selling Price</th>
                            <th>Purchase Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($variants as $variant)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $variant->product?->name }}</div>
                                    <small class="text-muted">{{ $variant->product?->sku }}</small>
                                </td>
                                <td>
                                    @if($variant->image)
                                        <img src="{{ api_asset($variant->image) }}" alt="{{ $variant->sku }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($variant->values->sortBy('variant_attribute_id') as $variantValue)
                                        <span class="badge bg-light text-body border me-1">
                                            {{ $variantValue->attribute?->name }}: {{ $variantValue->value?->value }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="fw-semibold">{{ $variant->sku }}</td>
                                <td>
                                    <span class="badge {{ $variant->quantity > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        {{ $variant->quantity }}
                                    </span>
                                </td>
                                <td>৳{{ number_format($variant->selling_price, 2) }}</td>
                                <td>{{ $variant->purchase_price !== null ? '$'.number_format($variant->purchase_price, 2) : '-' }}</td>
                                <td>
                                    <span class="badge {{ $variant->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $variant->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('product-variants.edit', $variant) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('product-variants.destroy', $variant) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product variant?');">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No product variants found. Create attributes, then generate combinations for a product.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $variants->links() }}
            </div>
        </div>
    </div>
@endsection
