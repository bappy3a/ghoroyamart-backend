@extends('layouts.master')
@section('title', 'Inventory Dashboard')

@section('content')
    @component('components.breadcrumb')
        @slot('li_1', 'Backend')
        @slot('title', 'Inventory Dashboard')
    @endcomponent

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('products.index', ['status' => 'published', 'visibility' => 'public', 'product_location' => 'store']) }}" class="card card-animate text-decoration-none">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-1">Published Website</p>
                            <h4 class="mb-0 text-body">{{ number_format($websiteProducts) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                <i class="ri-global-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('products.index', ['product_location' => 'warehouse']) }}" class="card card-animate text-decoration-none">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-1">Warehouse List</p>
                            <h4 class="mb-0 text-body">{{ number_format($warehouseProducts) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                <i class="ri-building-4-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('products.index', ['stock_filter' => 'low_stock']) }}" class="card card-animate text-decoration-none">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-1">Low Stock List</p>
                            <h4 class="mb-0 text-body">{{ number_format($lowStockProducts->count()) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                <i class="ri-alarm-warning-line"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('products.index') }}" class="card card-animate text-decoration-none">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <p class="text-uppercase fw-medium text-muted mb-1">Product List</p>
                            <h4 class="mb-0 text-body">{{ number_format($totalProducts) }}</h4>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                <i class="ri-list-check-2"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Stock</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle text-success rounded fs-3">
                                <i class="ri-stack-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($totalStock) }}</h4>
                            <span class="text-muted">Store {{ number_format($storeStock) }} / Warehouse {{ number_format($warehouseStock) }}</span>
                        </div>
                        <a href="{{ route('products.index') }}" class="text-decoration-underline">Products</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Products</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle text-info rounded fs-3">
                                <i class="ri-shopping-bag-3-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($totalProducts) }}</h4>
                            <span class="text-muted">{{ number_format($publishedProducts) }} published, {{ number_format($hiddenProducts) }} hidden</span>
                        </div>
                        <a href="{{ route('products.create') }}" class="text-decoration-underline">Add</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Low Stock</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle text-warning rounded fs-3">
                                <i class="ri-alert-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-1">{{ number_format($lowStockProducts->count()) }}</h4>
                            <span class="text-muted">{{ number_format($outOfStockCount) }} out of stock</span>
                        </div>
                        <a href="{{ route('products.index', ['stock_filter' => 'low_stock']) }}" class="text-decoration-underline">Review</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                            <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Retail Value</p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded fs-3">
                                <i class="ri-money-dollar-circle-line"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                            <h4 class="fs-22 fw-semibold ff-secondary mb-1">৳{{ number_format($inventoryRetailValue, 2) }}</h4>
                            <span class="text-muted">Low stock value ৳{{ number_format($lowStockRetailValue, 2) }}</span>
                        </div>
                        <span class="badge bg-success-subtle text-success">৳{{ number_format($potentialMargin, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Stock by Category</h4>
                    <div class="flex-shrink-0">
                        <span class="badge bg-light text-muted">{{ number_format($totalVariants) }} variants, {{ number_format($activeVariants) }} active</span>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($categoryStock as $category)
                        @php
                            $percentage = $totalStock > 0 ? min(100, round(($category->stock / $totalStock) * 100)) : 0;
                        @endphp
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-0">{{ $category->name }}</h6>
                                    <small class="text-muted">{{ number_format($category->products_count) }} products</small>
                                </div>
                                <div class="text-end">
                                    <span class="fw-semibold">{{ number_format($category->stock) }} units</span>
                                    <small class="text-muted d-block">৳{{ number_format($category->retail_value, 2) }}</small>
                                </div>
                            </div>
                            <div class="progress progress-sm">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">No inventory data found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Stock by Brand</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Brand</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($brandStock as $brand)
                                    <tr>
                                        <td>{{ $brand->name }}</td>
                                        <td class="text-end">{{ number_format($brand->stock) }}</td>
                                        <td class="text-end">৳{{ number_format($brand->retail_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No brand stock found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="stockAlerts">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">Low Stock Products</h4>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-soft-primary">Manage Products</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Alert</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProducts->take(8) as $product)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $product->name }}</div>
                                            <small class="text-muted">{{ $product->sku ?: 'No SKU' }}</small>
                                        </td>
                                        <td>{{ $product->category->name ?? 'Uncategorized' }}</td>
                                        <td class="text-end">
                                            <span class="badge bg-warning-subtle text-warning">{{ number_format($product->inventory_stock) }}</span>
                                        </td>
                                        <td class="text-end">{{ number_format($product->inventory_alert_level) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-soft-secondary">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">No low stock products right now.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Out of Stock</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($outOfStockProducts->take(8) as $product)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $product->name }}</div>
                                            <small class="text-muted">{{ $product->brand->name ?? 'No Brand' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger">{{ str_replace('_', ' ', $product->stock_status) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-sm btn-soft-secondary">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No out of stock products.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Highest Stock Products</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Brand</th>
                                    <th class="text-end">Stock</th>
                                    <th class="text-end">Retail Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topStockProducts as $product)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $product->name }}</div>
                                            <small class="text-muted">{{ $product->category->name ?? 'Uncategorized' }}</small>
                                        </td>
                                        <td>{{ $product->brand->name ?? 'No Brand' }}</td>
                                        <td class="text-end">{{ number_format($product->inventory_stock) }}</td>
                                        <td class="text-end">৳{{ number_format($product->inventory_retail_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No products found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Recent Stock Movement</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSoldItems as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ $item->product_name }}</div>
                                            @if($item->variant_name)
                                                <small class="text-muted">{{ $item->variant_name }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="badge bg-danger-subtle text-danger">-{{ number_format($item->quantity) }}</span>
                                        </td>
                                        <td class="text-end">{{ $item->created_at?->format('d M') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No recent movement found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
