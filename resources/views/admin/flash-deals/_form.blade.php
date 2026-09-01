@php
    $isEdit = $flashDeal?->exists;
    $submitLabel = $isEdit ? 'Update Flash Deal' : 'Create Flash Deal';
@endphp

@csrf
<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-1">{{ $isEdit ? 'Flash deal details' : 'Create flash deal' }}</h5>
                <p class="text-muted mb-0">Set up time-limited flash deals for products.</p>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control form-control-lg @error('title') is-invalid @enderror"
                        id="title"
                        name="title"
                        value="{{ old('title', $flashDeal->title ?? '') }}"
                        placeholder="e.g. Summer Flash Sale"
                        required
                    >
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug</label>
                    <input
                        type="text"
                        class="form-control @error('slug') is-invalid @enderror"
                        id="slug"
                        name="slug"
                        value="{{ old('slug', $flashDeal->slug ?? '') }}"
                        placeholder="Auto-generated from title"
                    >
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Leave empty to auto-generate from title. Only lowercase letters, numbers, and hyphens.</div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Describe this flash deal..."
                    >{{ old('description', $flashDeal->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="banner_image" class="form-label">Banner Image</label>
                    <input
                        type="file"
                        class="form-control @error('banner_image') is-invalid @enderror"
                        id="banner_image"
                        name="banner_image"
                        accept="image/*"
                    >
                    @error('banner_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Recommended size: 1200x400px. Max size: 2MB. Formats: JPG, PNG, GIF, WebP.</div>
                    @if($isEdit && $flashDeal->banner_image)
                        <div class="mt-2">
                            <img src="{{ api_asset($flashDeal->banner_image) }}" alt="Current banner" class="img-thumbnail" style="max-width: 300px;">
                        </div>
                    @endif
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="background_color" class="form-label">Background Color</label>
                        <input
                            type="color"
                            class="form-control form-control-color @error('background_color') is-invalid @enderror"
                            id="background_color"
                            name="background_color"
                            value="{{ old('background_color', $flashDeal->background_color ?? '#ff6b6b') }}"
                        >
                        @error('background_color')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="text_color" class="form-label">Text Color</label>
                        <input
                            type="color"
                            class="form-control form-control-color @error('text_color') is-invalid @enderror"
                            id="text_color"
                            name="text_color"
                            value="{{ old('text_color', $flashDeal->text_color ?? '#ffffff') }}"
                        >
                        @error('text_color')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            class="form-control @error('start_date') is-invalid @enderror"
                            id="start_date"
                            name="start_date"
                            value="{{ old('start_date', $flashDeal->start_date ? $flashDeal->start_date->format('Y-m-d') : '') }}"
                            required
                        >
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            class="form-control @error('end_date') is-invalid @enderror"
                            id="end_date"
                            name="end_date"
                            value="{{ old('end_date', $flashDeal->end_date ? $flashDeal->end_date->format('Y-m-d') : '') }}"
                            required
                        >
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="discount_percentage" class="form-label">Discount Percentage <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input
                                type="number"
                                class="form-control @error('discount_percentage') is-invalid @enderror"
                                id="discount_percentage"
                                name="discount_percentage"
                                value="{{ old('discount_percentage', $flashDeal->discount_percentage ?? 0) }}"
                                min="0"
                                max="100"
                                step="0.01"
                                required
                            >
                            <span class="input-group-text">%</span>
                        </div>
                        @error('discount_percentage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input
                            type="number"
                            class="form-control @error('sort_order') is-invalid @enderror"
                            id="sort_order"
                            name="sort_order"
                            value="{{ old('sort_order', $flashDeal->sort_order ?? 0) }}"
                            min="0"
                        >
                        @error('sort_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Lower numbers appear first.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Products <span class="text-danger">*</span></label>
                    
                    <!-- Selected Products Display -->
                    <div class="mb-3">
                        <div id="selected-products-container" class="border rounded p-3 bg-light" style="min-height: 80px;">
                            <div class="d-flex flex-wrap gap-2" id="selected-products-list">
                                @php
                                    $selectedProductIds = old('product_ids', $flashDeal->product_ids ?? []);
                                    $selectedProducts = $products->whereIn('id', $selectedProductIds);
                                @endphp
                                @foreach($selectedProducts as $product)
                                    <div class="badge bg-primary d-flex align-items-center gap-2 p-2" data-product-id="{{ $product->id }}">
                                        <span>{{ $product->name }}</span>
                                        <button type="button" class="btn-close btn-close-white" style="font-size: 0.7rem;" onclick="removeProduct({{ $product->id }})"></button>
                                        <input type="hidden" name="product_ids[]" value="{{ $product->id }}">
                                    </div>
                                @endforeach
                                @if($selectedProducts->isEmpty())
                                    <span class="text-muted">No products selected. Search and click to add products.</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-danger small mt-1" id="product-error" style="display: none;">
                            Please select at least one product.
                        </div>
                        @error('product_ids')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Product Search -->
                    <div class="mb-3">
                        <div class="input-group">
                            <input
                                type="text"
                                class="form-control"
                                id="product-search"
                                placeholder="Search products by name or SKU..."
                                autocomplete="off"
                            >
                            <button class="btn btn-outline-secondary" type="button" id="search-btn">
                                <i class="ri-search-line"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Product Results -->
                    <div class="border rounded" style="max-height: 400px; overflow-y: auto;">
                        <div id="product-results" class="p-2">
                            <div class="text-center text-muted py-4">
                                <i class="ri-search-line fs-3 d-block mb-2"></i>
                                <p class="mb-0">Start typing to search for products</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Status</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $flashDeal->is_active ?? true))
                    >
                    <label class="form-check-label d-flex flex-column gap-1" for="is_active">
                        <span>Active</span>
                        <span class="text-muted small">Uncheck to disable this flash deal.</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Flash Deal Info</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Flash deals are time-limited promotions.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Select products to include in the deal.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Set discount percentage for all selected products.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Ready to save?</div>
                <p class="text-muted mb-0 small">You can update flash deal details anytime.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('flash-deals.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
