@php
    $isEdit = $coupon?->exists;
    $submitLabel = $isEdit ? 'Update Coupon' : 'Create Coupon';
@endphp

@if(!$isEdit)
    @csrf
@endif

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent">
                <h5 class="mb-1">{{ $isEdit ? 'Coupon details' : 'Create coupon' }}</h5>
                <p class="text-muted mb-0">Set up discount codes for products or orders.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Coupon Code <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control form-control-lg @error('code') is-invalid @enderror"
                            id="code"
                            name="code"
                            value="{{ old('code', $coupon->code ?? '') }}"
                            placeholder="e.g. SAVE20"
                            required
                            style="text-transform: uppercase;"
                        >
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Only uppercase letters, numbers, hyphens, and underscores.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Coupon Name <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control form-control-lg @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name', $coupon->name ?? '') }}"
                            placeholder="e.g. Summer Sale 2024"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Describe this coupon..."
                    >{{ old('description', $coupon->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Coupon Type <span class="text-danger">*</span></label>
                        <select
                            class="form-select @error('type') is-invalid @enderror"
                            id="type"
                            name="type"
                            required
                        >
                            <option value="">Select Type</option>
                            <option value="product_wise" {{ old('type', $coupon->type ?? '') == 'product_wise' ? 'selected' : '' }}>
                                Product Wise
                            </option>
                            <option value="order_based" {{ old('type', $coupon->type ?? 'order_based') == 'order_based' ? 'selected' : '' }}>
                                Order Based
                            </option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Product Wise: Apply to specific products. Order Based: Apply based on order amount.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="discount_type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                        <select
                            class="form-select @error('discount_type') is-invalid @enderror"
                            id="discount_type"
                            name="discount_type"
                            required
                        >
                            <option value="percentage" {{ old('discount_type', $coupon->discount_type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>
                                Percentage (%)
                            </option>
                            <option value="fixed" {{ old('discount_type', $coupon->discount_type ?? '') == 'fixed' ? 'selected' : '' }}>
                                Fixed Amount ($)
                            </option>
                        </select>
                        @error('discount_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="discount_value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text" id="discount_prefix">
                                <span id="discount_symbol">%</span>
                            </span>
                            <input
                                type="number"
                                step="0.01"
                                class="form-control @error('discount_value') is-invalid @enderror"
                                id="discount_value"
                                name="discount_value"
                                value="{{ old('discount_value', $coupon->discount_value ?? '') }}"
                                placeholder="0.00"
                                required
                                min="0"
                            >
                            @error('discount_value')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text" id="discount_help">Enter percentage (0-100) or fixed amount.</div>
                    </div>

                    <div class="col-md-6 mb-3" id="max_discount_wrapper">
                        <label for="maximum_discount_amount" class="form-label">Maximum Discount Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input
                                type="number"
                                step="0.01"
                                class="form-control @error('maximum_discount_amount') is-invalid @enderror"
                                id="maximum_discount_amount"
                                name="maximum_discount_amount"
                                value="{{ old('maximum_discount_amount', $coupon->maximum_discount_amount ?? '') }}"
                                placeholder="0.00"
                                min="0"
                            >
                            @error('maximum_discount_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-text">Limit the maximum discount for percentage discounts.</div>
                    </div>
                </div>

                <!-- Product Selection (for product_wise type) -->
                <div class="mb-3" id="product_selection_wrapper" style="display: none;">
                    <label for="product_search" class="form-label">Select Products <span class="text-danger">*</span></label>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <input
                                type="text"
                                id="product_search"
                                class="form-control"
                                placeholder="Search product by name..."
                            >
                        </div>
                        <div class="col-md-6 d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="select_visible_products">
                                Select All Visible
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="clear_products">
                                Clear
                            </button>
                        </div>
                    </div>

                    <div
                        id="product_checkbox_list"
                        class="border rounded p-2 @error('product_ids') border-danger @enderror"
                        style="max-height: 260px; overflow-y: auto;"
                    >
                        @foreach($products as $product)
                            <div class="form-check product-item py-1" data-product-name="{{ strtolower($product->name) }}">
                                <input
                                    class="form-check-input product-checkbox"
                                    type="checkbox"
                                    value="{{ $product->id }}"
                                    id="product_{{ $product->id }}"
                                    name="product_ids[]"
                                    @checked(in_array($product->id, old('product_ids', $coupon->product_ids ?? [])))
                                >
                                <label class="form-check-label w-100" for="product_{{ $product->id }}">
                                    {{ $product->name }} - ৳{{ number_format($product->price, 2) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('product_ids')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-text" id="selected_product_count"></div>
                </div>

                <!-- Minimum Order Amount (for order_based type) -->
                <div class="mb-3" id="min_order_wrapper">
                    <label for="minimum_order_amount" class="form-label">Minimum Order Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">৳</span>
                        <input
                            type="number"
                            step="0.01"
                            class="form-control @error('minimum_order_amount') is-invalid @enderror"
                            id="minimum_order_amount"
                            name="minimum_order_amount"
                            value="{{ old('minimum_order_amount', $coupon->minimum_order_amount ?? 0) }}"
                            placeholder="0.00"
                            min="0"
                        >
                        @error('minimum_order_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Minimum order amount required to apply this coupon.</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="valid_from" class="form-label">Valid From <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            class="form-control @error('valid_from') is-invalid @enderror"
                            id="valid_from"
                            name="valid_from"
                            value="{{ old('valid_from', $coupon->valid_from ? $coupon->valid_from->format('Y-m-d') : '') }}"
                            required
                        >
                        @error('valid_from')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="valid_to" class="form-label">Valid To <span class="text-danger">*</span></label>
                        <input
                            type="date"
                            class="form-control @error('valid_to') is-invalid @enderror"
                            id="valid_to"
                            name="valid_to"
                            value="{{ old('valid_to', $coupon->valid_to ? $coupon->valid_to->format('Y-m-d') : '') }}"
                            required
                        >
                        @error('valid_to')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="usage_limit" class="form-label">Total Usage Limit</label>
                        <input
                            type="number"
                            class="form-control @error('usage_limit') is-invalid @enderror"
                            id="usage_limit"
                            name="usage_limit"
                            value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}"
                            placeholder="Leave empty for unlimited"
                            min="1"
                        >
                        @error('usage_limit')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Maximum number of times this coupon can be used.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="usage_limit_per_user" class="form-label">Usage Limit Per User</label>
                        <input
                            type="number"
                            class="form-control @error('usage_limit_per_user') is-invalid @enderror"
                            id="usage_limit_per_user"
                            name="usage_limit_per_user"
                            value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? '') }}"
                            placeholder="Leave empty for unlimited"
                            min="1"
                        >
                        @error('usage_limit_per_user')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Maximum times a single user can use this coupon.</div>
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
                        @checked(old('is_active', $coupon->is_active ?? true))
                    >
                    <label class="form-check-label d-flex flex-column gap-1" for="is_active">
                        <span>Active</span>
                        <span class="text-muted small">Uncheck to disable this coupon.</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Coupon Info</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Product Wise: Apply to specific products only.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Order Based: Apply when order meets minimum amount.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Set usage limits to control coupon distribution.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Ready to save?</div>
                <p class="text-muted mb-0 small">You can update coupon details anytime.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('coupons.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
