@extends('layouts.master')
@section('title','Products')
@section('css')
@endsection

@section('content')
    @php
        $activeFilterCount = collect([
            request('search'),
            request('category_id'),
            request('brand_id'),
            request('status'),
            request('product_location'),
            request('stock_filter'),
            request('visibility'),
        ])->filter(fn ($value) => filled($value))->count();
    @endphp

    <div class="row" id="productsFilder">
        <div class="col-12">
            <div class="card" id="productFilters">
                <div class="card-header border-0">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm">
                            <h5 class="card-title mb-1">
                                <i class="ri-filter-3-line align-bottom me-1 text-primary"></i>
                                Product Filters
                            </h5>
                            <p class="text-muted mb-0">Find products by catalog, publishing, stock, and website visibility.</p>
                        </div>
                        <div class="col-sm-auto">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if($activeFilterCount)
                                    <span class="badge bg-primary-subtle text-primary rounded-pill">
                                        {{ $activeFilterCount }} active
                                    </span>
                                @endif
                                <a href="{{ route('products.index') }}" class="btn btn-light">
                                    <i class="ri-refresh-line align-bottom me-1"></i>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body border border-dashed border-end-0 border-start-0">
                    <form method="GET" action="{{ route('products.index') }}" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-xxl-4 col-lg-6">
                                <label class="form-label">Search</label>
                                <div class="search-box">
                                    <input type="text" class="form-control search" name="search" value="{{ request('search') }}" placeholder="Search product name or SKU...">
                                    <i class="ri-search-line search-icon"></i>
                                </div>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }} ({{ $category->products_count ?? 0 }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <label class="form-label">Brand</label>
                                <select class="form-select" name="brand_id" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">All Brands</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">All Status</option>
                                    <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                </select>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <label class="form-label">Location</label>
                                <select class="form-select" name="product_location" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">All Locations</option>
                                    <option value="store" {{ request('product_location') == 'store' ? 'selected' : '' }}>Store</option>
                                    <option value="warehouse" {{ request('product_location') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                </select>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <label class="form-label">Stock</label>
                                <select class="form-select" name="stock_filter" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">All Stock</option>
                                    <option value="in_stock" {{ request('stock_filter') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                    <option value="low_stock" {{ request('stock_filter') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                                    <option value="out_of_stock" {{ request('stock_filter') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                </select>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <label class="form-label">Visibility</label>
                                <select class="form-select" name="visibility" onchange="document.getElementById('filterForm').submit();">
                                    <option value="">All Visibility</option>
                                    <option value="public" {{ request('visibility') == 'public' ? 'selected' : '' }}>Public Website</option>
                                    <option value="hidden" {{ request('visibility') == 'hidden' ? 'selected' : '' }}>Hidden</option>
                                </select>
                            </div>

                            <div class="col-xxl-2 col-lg-3 col-sm-6">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ri-equalizer-fill align-bottom me-1"></i>
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="row g-4">
                        <div class="col-sm-auto">
                            <div>
                                <a href="{{ route('products.create') }}" class="btn btn-success">
                                    <i class="ri-add-line align-bottom me-1"></i> Add Product
                                </a>
                            </div>
                        </div>
                        <div class="col-sm">
                            <div class="d-flex justify-content-sm-end">
                                <div class="search-box ms-2">
                                    <form method="GET" action="{{ route('products.index') }}" class="d-flex">
                                        <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search Products...">
                                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                                        <input type="hidden" name="brand_id" value="{{ request('brand_id') }}">
                                        <input type="hidden" name="status" value="{{ request('status') }}">
                                        <input type="hidden" name="product_location" value="{{ request('product_location') }}">
                                        <input type="hidden" name="stock_filter" value="{{ request('stock_filter') }}">
                                        <input type="hidden" name="visibility" value="{{ request('visibility') }}">
                                        <button type="submit" class="btn btn-link search-icon">
                                            <i class="ri-search-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link {{ !request('status') ? 'active' : '' }} fw-semibold" href="{{ route('products.index', array_merge(request()->except('status'), ['status' => ''])) }}">
                                        All <span class="badge bg-danger-subtle text-danger align-middle rounded-pill ms-1">{{ $totalCount }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request('status') == 'published' ? 'active' : '' }} fw-semibold" href="{{ route('products.index', array_merge(request()->except('status'), ['status' => 'published'])) }}">
                                        Published <span class="badge bg-danger-subtle text-danger align-middle rounded-pill ms-1">{{ $publishedCount }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request('status') == 'draft' ? 'active' : '' }} fw-semibold" href="{{ route('products.index', array_merge(request()->except('status'), ['status' => 'draft'])) }}">
                                        Draft <span class="badge bg-danger-subtle text-danger align-middle rounded-pill ms-1">{{ $draftCount }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request('stock_filter') == 'low_stock' ? 'active' : '' }} fw-semibold" href="{{ route('products.index', array_merge(request()->except('stock_filter'), ['stock_filter' => 'low_stock'])) }}">
                                        Low Stock <span class="badge bg-warning-subtle text-warning align-middle rounded-pill ms-1">{{ $lowStockCount }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request('product_location') == 'warehouse' ? 'active' : '' }} fw-semibold" href="{{ route('products.index', array_merge(request()->except('product_location'), ['product_location' => 'warehouse'])) }}">
                                        Warehouse <span class="badge bg-info-subtle text-info align-middle rounded-pill ms-1">{{ $warehouseCount }}</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request('product_location') == 'store' ? 'active' : '' }} fw-semibold" href="{{ route('products.index', array_merge(request()->except('product_location'), ['product_location' => 'store'])) }}">
                                        Store <span class="badge bg-success-subtle text-success align-middle rounded-pill ms-1">{{ $storeCount }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @error('product_ids')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    @error('location')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <form method="POST" action="{{ route('products.bulk-update-location') }}" id="bulkLocationForm" class="row g-2 align-items-center mb-3">
                        @csrf
                        <div class="col-md-4 col-lg-3">
                            <select class="form-select" name="location" id="bulkLocationSelect">
                                <option value="">Move selected to...</option>
                                <option value="warehouse">Warehouse</option>
                                <option value="store">Store</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary" id="bulkLocationSubmit" disabled>
                                <i class="ri-arrow-left-right-line align-bottom me-1"></i>
                                Move Products
                            </button>
                        </div>
                        <div class="col-auto">
                            <span class="text-muted small" id="selectedProductsCount">0 selected</span>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" style="width: 40px;">
                                        <input class="form-check-input" type="checkbox" id="checkAll" aria-label="Select all products">
                                    </th>
                                    <th scope="col">Product</th>
                                    <th scope="col">Stock</th>
                                    <th scope="col">Location</th>
                                    <th scope="col">Variants</th>
                                    <th scope="col">Price</th>
                                    <th scope="col">Orders</th>
                                    <th scope="col">Published</th>
                                    <th scope="col" style="width: 80px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>
                                            <input
                                                class="form-check-input product-checkbox"
                                                type="checkbox"
                                                name="product_ids[]"
                                                value="{{ $product->id }}"
                                                form="bulkLocationForm"
                                                aria-label="Select {{ $product->name }}"
                                            >
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar-sm bg-light rounded p-1">
                                                        <img src="{{ $product->thumbnail_image ? api_asset($product->thumbnail_image) : asset('build/images/products/img-1.png') }}"
                                                             alt="{{ $product->name }}" class="img-fluid d-block">
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h5 class="fs-14 mb-1">
                                                        <a href="#" class="text-body">{{ $product->name }}</a>
                                                    </h5>
                                                    <p class="text-muted mb-0">
                                                        SKU: <span class="fw-medium">{{ $product->sku ?? 'Uncategorized' }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $inventoryStock = (int) ($product->inventory_stock ?? $product->quantity ?? 0);
                                                $alertLevel = (int) ($product->low_stock_alert ?: 5);
                                            @endphp
                                            @if ($inventoryStock > 0 && $inventoryStock <= $alertLevel)
                                                <span class="badge bg-warning-subtle text-warning">
                                                    {{ number_format($inventoryStock) }} Low
                                                </span>
                                            @elseif ($inventoryStock > 0)
                                                <span class="badge bg-success-subtle text-success">
                                                    {{ number_format($inventoryStock) }}
                                                </span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">
                                                    {{ number_format($inventoryStock) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ ($product->product_location ?? 'store') === 'warehouse' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success' }}">
                                                {{ ucfirst($product->product_location ?? 'store') }}
                                            </span>
                                            @if(($product->product_location ?? 'store') === 'warehouse')
                                                <small class="text-muted d-block">Website hidden</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('products.edit', $product->id) }}#product-variants" class="badge bg-info-subtle text-info">
                                                {{ $product->variants_count ?? 0 }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($product->is_discounted && $product->regular_price)
                                                <span class="text-muted text-decoration-line-through">৳{{ number_format($product->regular_price, 2) }}</span>
                                                <span class="fw-semibold ms-1">৳{{ number_format($product->price, 2) }}</span>
                                            @else
                                                <span class="fw-semibold">৳{{ number_format($product->price ?? 0, 2) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $product->num_of_sale ?? 0 }}</td>
                                        <td>
                                            @if($product->published_at)
                                                <div>
                                                    {{ $product->published_at->format('d M, Y') }}
                                                </div>
                                            @else
                                                <div>
                                                    {{ $product->created_at->format('d M, Y') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="ri-more-fill"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('products.edit', $product->id) }}">
                                                            <i class="ri-pencil-fill align-bottom me-2 text-muted"></i> Edit
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('products.edit', $product->id) }}#product-variants">
                                                            <i class="ri-stack-line align-bottom me-2 text-muted"></i> Edit Variants
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('products.duplicate', $product->id) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item" onclick="return confirm('Duplicate this product?');">
                                                                <i class="ri-file-copy-2-line align-bottom me-2 text-muted"></i> Duplicate
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#"
                                                           data-delete-url="{{ route('products.destroy', $product->id) }}"
                                                           data-delete-title="Are you Sure ?"
                                                           data-delete-message="Are you Sure You want to Remove this Product ?">
                                                            <i class="ri-delete-bin-fill align-bottom me-2 text-muted"></i> Delete
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <lord-icon src="https://cdn.lordicon.com/msoeawqm.json"
                                                trigger="loop" colors="primary:#405189,secondary:#0ab39c"
                                                style="width:72px;height:72px">
                                            </lord-icon>
                                            <h5 class="mt-4">No Products Found</h5>
                                            <p class="text-muted">Try adjusting your filters or add a new product.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($products->hasPages())
                        <div class="d-flex justify-content-end mt-3">
                            {{ $products->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (() => {
            const checkAll = document.getElementById('checkAll');
            const checkboxes = Array.from(document.querySelectorAll('.product-checkbox'));
            const locationSelect = document.getElementById('bulkLocationSelect');
            const submitButton = document.getElementById('bulkLocationSubmit');
            const selectedCount = document.getElementById('selectedProductsCount');
            const form = document.getElementById('bulkLocationForm');

            function selectedProducts() {
                return checkboxes.filter((checkbox) => checkbox.checked);
            }

            function updateBulkState() {
                const selected = selectedProducts().length;

                if (selectedCount) {
                    selectedCount.textContent = `${selected} selected`;
                }

                if (submitButton) {
                    submitButton.disabled = selected === 0 || !locationSelect?.value;
                }

                if (checkAll) {
                    checkAll.checked = selected > 0 && selected === checkboxes.length;
                    checkAll.indeterminate = selected > 0 && selected < checkboxes.length;
                }
            }

            checkAll?.addEventListener('change', function () {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });

                updateBulkState();
            });

            checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateBulkState));
            locationSelect?.addEventListener('change', updateBulkState);

            form?.addEventListener('submit', (event) => {
                const selected = selectedProducts().length;
                const location = locationSelect?.value;

                if (!selected || !location) {
                    event.preventDefault();
                    alert('Please select products and choose a location.');
                    return;
                }

                const label = location === 'warehouse' ? 'warehouse' : 'store';

                if (!confirm(`Move ${selected} selected product${selected === 1 ? '' : 's'} to ${label}?`)) {
                    event.preventDefault();
                }
            });

            updateBulkState();
        })();
    </script>
@endsection
