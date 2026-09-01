@extends('layouts.master')

@section('title', ($isEdit ?? false) ? 'Edit Order' : 'Create Order')

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container {
            width: 100% !important;
        }

        .select2-container--default .select2-selection--single {
            min-height: 38px;
            border: 1px solid var(--vz-border-color);
            border-radius: 0.25rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
            padding-left: 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .product-image-modal__image {
            display: block;
            width: 100%;
            max-height: 70vh;
            border-radius: 8px;
            object-fit: contain;
        }

        /* POS layout */
        .pos-search-wrap {
            position: relative;
            width: 280px;
            max-width: 100%;
        }

        .pos-search-wrap i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--vz-secondary-color);
        }

        .pos-search-wrap input {
            padding-left: 30px;
        }

        .pos-catalog-body {
            max-height: 74vh;
            overflow-y: auto;
        }

        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 14px;
        }

        .pos-product-card {
            position: relative;
            border: 1px solid var(--vz-border-color);
            border-radius: 10px;
            background: var(--vz-card-bg);
            cursor: pointer;
            overflow: hidden;
            transition: box-shadow .15s ease, border-color .15s ease, transform .1s ease;
        }

        .pos-product-card:hover {
            border-color: var(--vz-primary);
            box-shadow: 0 4px 14px rgba(0, 0, 0, .08);
            transform: translateY(-2px);
        }

        .pos-product-card:active {
            transform: translateY(0);
        }

        .pos-product-card__img-wrap {
            position: relative;
            background: var(--vz-light);
        }

        .pos-product-card__img {
            display: block;
            width: 100%;
            height: 110px;
            object-fit: cover;
        }

        .pos-product-card__zoom {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 26px;
            height: 26px;
            border: none;
            border-radius: 50%;
            background: rgba(0, 0, 0, .55);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            padding: 0;
        }

        .pos-product-card__stock {
            position: absolute;
            left: 6px;
            bottom: 6px;
            font-size: 10px;
        }

        .pos-product-card__body {
            padding: 8px 10px 10px;
        }

        .pos-product-card__name {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: 13px;
            font-weight: 500;
            min-height: 34px;
            margin-bottom: 4px;
        }

        .pos-product-card__price {
            font-size: 13px;
            font-weight: 600;
            color: var(--vz-primary);
        }

        .pos-product-card__variants {
            display: block;
            font-size: 11px;
            color: var(--vz-secondary-color);
            margin-top: 2px;
        }

        .pos-cart-sticky {
            position: sticky;
            top: 88px;
        }

        @media (min-width: 1200px) {
            .pos-cart-sticky {
                max-height: calc(100vh - 100px);
                overflow-y: auto;
                padding-right: 2px;
            }
        }

        .pos-cart-items {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 260px;
            overflow-y: auto;
        }

        .pos-cart-item {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--vz-border-color);
            border-radius: 8px;
            padding: 8px;
            background: var(--vz-light);
        }

        .pos-cart-item__img {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            cursor: zoom-in;
            flex: 0 0 44px;
        }

        .pos-cart-item__meta {
            min-width: 0;
            flex: 1 1 auto;
        }

        .pos-cart-item__name {
            font-size: 13px;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pos-cart-item__price {
            font-size: 11px;
        }

        .pos-cart-item__qty {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 0 0 auto;
        }

        .pos-cart-item__qty .cart-quantity {
            width: 46px;
            padding: 2px;
            text-align: center;
        }

        .pos-cart-item__total {
            min-width: 64px;
            text-align: right;
            font-size: 13px;
            flex: 0 0 auto;
        }

        #cart-empty i {
            color: var(--vz-secondary-color);
        }

        .pos-variant-option {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            border: 1px solid var(--vz-border-color);
            border-radius: 8px;
            padding: 8px 10px;
            margin-bottom: 8px;
            background: var(--vz-card-bg);
            text-align: left;
        }

        .pos-variant-option:hover:not(:disabled) {
            border-color: var(--vz-primary);
            background: var(--vz-light);
        }

        .pos-variant-option:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .pos-variant-option img {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            flex: 0 0 40px;
        }

        .pos-variant-option__meta {
            flex: 1 1 auto;
            min-width: 0;
        }

        .pos-variant-option__name {
            display: block;
            font-weight: 500;
            font-size: 13px;
        }
    </style>
@endsection

@section('content')
@php
    $isEdit = $isEdit ?? false;
    $order = $order ?? null;
    $shippingAddress = $order?->shippingAddress;
    $pageTitle = $isEdit ? 'Order Edit' : 'Order Create';
    $submitLabel = $isEdit ? 'Update Order' : 'Store Order';
    $requiresOrderSource = ! $isEdit;
    $selectedOrderSource = old('order_source');
    $selectedOrderSource = filled($selectedOrderSource) ? $selectedOrderSource : ($order?->order_source ?: ($isEdit ? 'website' : 'facebook'));
    $selectedShippingMethod = old('shipping_method', $order?->shipping_method ?: 'inside_dhaka');
    if (! array_key_exists($selectedShippingMethod, $deliveryCharges)) {
        $selectedShippingMethod = 'inside_dhaka';
    }
    $productCatalog = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'image' => $product->thumbnail_image ?: asset('build/images/products/img-1.png'),
            'quantity' => (int) $product->quantity,
            'price' => (float) $product->price,
            'regular_price' => (float) $product->regular_price,
            'variants' => $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'name' => $variant->name ?: $variant->sku,
                'sku' => $variant->sku,
                'image' => $variant->image ? api_asset($variant->image) : ($product->thumbnail_image ?: asset('build/images/products/img-1.png')),
                'quantity' => (int) $variant->quantity,
                'price' => (float) ($variant->selling_price ?: $product->price),
            ])->values(),
        ];
    })->values();

    $selectedDeliveryAreaId = old('shipping_delivery_area_id', $shippingAddress?->delivery_area_id);

    $oldOrderItems = old('items', $defaultOrderItems ?? [
        [
            'product_id' => '',
            'product_variant_id' => '',
            'quantity' => 1,
        ],
    ]);
@endphp

<div class="d-flex align-items-center mb-3">
    <h4 class="mb-0 flex-grow-1">{{ $pageTitle }}</h4>
    <a href="{{ $backRoute ?? route('orders.index') }}" class="btn btn-light btn-sm">
        <i class="ri-arrow-left-line align-middle me-1"></i> Back
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        Please check the form and try again.
    </div>
@endif

<form action="{{ $storeRoute ?? route('orders.store') }}" method="POST" id="backend-order-form" autocomplete="off">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-xxl-8">
            <div class="card pos-catalog-card">
                <div class="card-header">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1">Select Products</h5>
                        <div class="pos-search-wrap">
                            <i class="ri-search-line"></i>
                            <input type="search" id="product-search" class="form-control form-control-sm" placeholder="Search product name or SKU...">
                        </div>
                    </div>
                </div>
                <div class="card-body pos-catalog-body">
                    <div id="product-grid" class="pos-product-grid"></div>
                    <div id="product-empty" class="text-center text-muted py-5 d-none">No products found.</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Notes</h5>
                </div>
                <div class="card-body">
                    <textarea class="form-control @error('order_notes') is-invalid @enderror" name="order_notes" rows="3">{{ old('order_notes', $order?->order_notes) }}</textarea>
                    @error('order_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="col-xxl-4">
            <div class="pos-cart-sticky">
                <div class="card pos-cart-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Cart <span class="badge bg-primary-subtle text-primary ms-1" id="cart-count">0</span></h5>
                    </div>
                    <div class="card-body">
                        <div id="order-items" class="pos-cart-items"></div>
                        <div id="cart-empty" class="text-center text-muted py-4">
                            <i class="ri-shopping-cart-2-line fs-2 d-block mb-2"></i>
                            Cart is empty. Click a product to add it.
                        </div>
                        @error('items')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('items.*.product_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('items.*.product_variant_id')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        @error('items.*.quantity')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-4">
                                <label for="order_source" class="form-label">Source @if($requiresOrderSource)<span class="text-danger">*</span>@endif</label>
                                <select class="form-select form-select-sm @error('order_source') is-invalid @enderror" id="order_source" name="order_source" @if($requiresOrderSource) required @endif>
                                    <option value="">Select</option>
                                    @foreach($sourceOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedOrderSource === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('order_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-4">
                                <label for="payment_method" class="form-label">Payment <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('payment_method') is-invalid @enderror" id="payment_method" name="payment_method" required>
                                    <option value="cash_on_delivery" @selected(old('payment_method', $order?->payment_method ?? 'cash_on_delivery') === 'cash_on_delivery')>COD</option>
                                    <option value="bkash" @selected(old('payment_method', $order?->payment_method) === 'bkash')>bKash</option>
                                    <option value="nagad" @selected(old('payment_method', $order?->payment_method) === 'nagad')>Nagad</option>
                                    <option value="rocket" @selected(old('payment_method', $order?->payment_method) === 'rocket')>Rocket</option>
                                    <option value="ssl_commerce" @selected(old('payment_method', $order?->payment_method) === 'ssl_commerce')>SSL Commerce</option>
                                </select>
                                @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-4">
                                <label for="payment_status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('payment_status') is-invalid @enderror" id="payment_status" name="payment_status" required>
                                    <option value="pending" @selected(old('payment_status', $order?->payment_status ?? 'pending') === 'pending')>Pending</option>
                                    <option value="paid" @selected(old('payment_status', $order?->payment_status) === 'paid')>Paid</option>
                                    <option value="failed" @selected(old('payment_status', $order?->payment_status) === 'failed')>Failed</option>
                                    <option value="refunded" @selected(old('payment_status', $order?->payment_status) === 'refunded')>Refunded</option>
                                </select>
                                @error('payment_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="customer_name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm @error('customer_name') is-invalid @enderror" id="customer_name" name="customer_name" value="{{ old('customer_name', $order?->customer_name) }}" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="customer_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm @error('customer_phone') is-invalid @enderror" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $order?->customer_phone) }}" inputmode="numeric" pattern="^01[3-9][0-9]{8}$" maxlength="11" placeholder="01712345678" title="Enter a valid 11 digit Bangladesh mobile number." required>
                                @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="customer_email" class="form-label">Email</label>
                                <input type="email" class="form-control form-control-sm @error('customer_email') is-invalid @enderror" id="customer_email" name="customer_email" value="{{ old('customer_email', $order?->customer_email) }}">
                                @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shipping Info</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-12">
                                <label for="shipping_address" class="form-label">Address <span class="text-danger">*</span></label>
                                <textarea class="form-control form-control-sm @error('shipping_address') is-invalid @enderror" id="shipping_address" name="shipping_address" rows="2" required>{{ old('shipping_address', $shippingAddress?->address) }}</textarea>
                                @error('shipping_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="shipping_address_type" class="form-label">Address Type</label>
                                <select class="form-select form-select-sm @error('shipping_address_type') is-invalid @enderror" id="shipping_address_type" name="shipping_address_type">
                                    <option value="home" @selected(old('shipping_address_type', $shippingAddress?->address_type ?? 'home') === 'home')>Home</option>
                                    <option value="office" @selected(old('shipping_address_type', $shippingAddress?->address_type) === 'office')>Office</option>
                                    <option value="hometown" @selected(old('shipping_address_type', $shippingAddress?->address_type) === 'hometown')>Hometown</option>
                                </select>
                                @error('shipping_address_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="shipping_postal_code" class="form-label">Postal Code</label>
                                <input type="number" step="any" class="form-control form-control-sm @error('shipping_postal_code') is-invalid @enderror" id="shipping_postal_code" name="shipping_postal_code" value="{{ old('shipping_postal_code', $shippingAddress?->postal_code) }}">
                                @error('shipping_postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="shipping_district_group" class="form-label">District <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm searchable-select" id="shipping_district_group">
                                    <option value="">Select district</option>
                                    @foreach($deliveryAreaGroups as $group)
                                        <option value="{{ $group['district_id'] }}">{{ $group['district_name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="shipping_delivery_area_id" class="form-label">Area / Thana <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm searchable-select @error('shipping_delivery_area_id') is-invalid @enderror" id="shipping_delivery_area_id" name="shipping_delivery_area_id" data-selected="{{ $selectedDeliveryAreaId }}" required>
                                    <option value="">Select area</option>
                                </select>
                                @error('shipping_delivery_area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="shipping_method" class="form-label">Delivery Area <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('shipping_method') is-invalid @enderror" id="shipping_method" name="shipping_method" required>
                                    <option value="inside_dhaka" @selected($selectedShippingMethod === 'inside_dhaka')>Inside Dhaka - ৳{{ number_format($deliveryCharges['inside_dhaka'], 2) }}</option>
                                    <option value="outside_dhaka" @selected($selectedShippingMethod === 'outside_dhaka')>Outside Dhaka - ৳{{ number_format($deliveryCharges['outside_dhaka'], 2) }}</option>
                                </select>
                                @error('shipping_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card pos-summary-card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td>Subtotal</td>
                                        <td class="text-end" id="summary-subtotal">৳0.00</td>
                                    </tr>
                                    <tr>
                                        <td>Shipping</td>
                                        <td class="text-end" id="summary-shipping">৳0.00</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <label for="discount" class="form-label mb-0">Discount</label>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end @error('discount') is-invalid @enderror" id="discount" name="discount" value="{{ old('discount', $order?->discount ?? 0) }}">
                                        </td>
                                    </tr>
                                    <tr class="border-top">
                                        <th>Total</th>
                                        <th class="text-end" id="summary-total">৳0.00</th>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        @error('discount')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="ri-save-line align-middle me-1"></i> {{ $submitLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="modal fade" id="variant-select-modal" tabindex="-1" aria-labelledby="variant-select-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variant-select-modal-title">Select Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="variant-select-modal-body"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="product-image-modal" tabindex="-1" aria-labelledby="product-image-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="product-image-modal-title">Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img src="{{ asset('build/images/products/img-1.png') }}" alt="Product preview" class="product-image-modal__image" id="product-image-modal-img">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const products = @json($productCatalog);
    const deliveryAreaGroups = @json($deliveryAreaGroups);
    const deliveryCharges = @json($deliveryCharges);
    const oldItems = @json($oldOrderItems);
    const fallbackImage = @json(asset('build/images/products/img-1.png'));
    const currency = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const productGridEl = document.getElementById('product-grid');
    const productEmptyEl = document.getElementById('product-empty');
    const productSearchEl = document.getElementById('product-search');
    const orderItemsEl = document.getElementById('order-items');
    const cartCountEl = document.getElementById('cart-count');
    const cartEmptyEl = document.getElementById('cart-empty');

    const imageModal = document.getElementById('product-image-modal');
    const imageModalImg = document.getElementById('product-image-modal-img');
    const imageModalTitle = document.getElementById('product-image-modal-title');

    const variantModal = document.getElementById('variant-select-modal');
    const variantModalTitle = document.getElementById('variant-select-modal-title');
    const variantModalBody = document.getElementById('variant-select-modal-body');

    let cart = [];
    let uidCounter = 0;

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function initSelect2(element) {
        if (!hasSelect2() || !element) {
            return;
        }

        const $element = jQuery(element);

        if ($element.hasClass('select2-hidden-accessible')) {
            return;
        }

        $element.select2({
            width: '100%',
            placeholder: element.querySelector('option[value=""]')?.textContent || 'Select',
            allowClear: !element.required,
        });
    }

    function destroySelect2(element) {
        if (!hasSelect2() || !element) {
            return;
        }

        const $element = jQuery(element);

        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }
    }

    function initSelects(scope = document) {
        scope.querySelectorAll('select.searchable-select').forEach(initSelect2);
    }

    function money(value) {
        return '৳' + currency.format(Number(value || 0));
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function findProduct(productId) {
        return products.find(product => String(product.id) === String(productId));
    }

    function findVariant(product, variantId) {
        if (!product || !variantId) {
            return null;
        }
        return product.variants.find(variant => String(variant.id) === String(variantId)) || null;
    }

    function totalStock(product) {
        if (product.variants.length > 0) {
            return product.variants.reduce((sum, variant) => sum + variant.quantity, 0);
        }
        return product.quantity;
    }

    function displayPrice(product) {
        if (product.variants.length > 0) {
            const prices = product.variants.map(variant => variant.price);
            const min = Math.min(...prices);
            const max = Math.max(...prices);
            return min === max ? money(min) : `${money(min)} - ${money(max)}`;
        }
        return money(product.price);
    }

    function openImagePreview(src, title) {
        imageModalImg.src = src || fallbackImage;
        imageModalImg.alt = title || 'Product Image';
        imageModalTitle.textContent = title || 'Product Image';
        imageModalImg.onerror = function () { this.src = fallbackImage; };

        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(imageModal).show();
        }
    }

    function productCardHtml(product) {
        const hasVariants = product.variants.length > 0;
        const stock = totalStock(product);
        const searchText = [product.name, product.sku, ...product.variants.map(v => v.name)].filter(Boolean).join(' ').toLowerCase();

        let priceHtml;
        if (hasVariants) {
            priceHtml = `<span class="pos-product-card__price">${displayPrice(product)}</span>`;
        } else if (product.regular_price > product.price) {
            priceHtml = `<span class="pos-product-card__price">${money(product.price)} <del class="text-muted small ms-1">${money(product.regular_price)}</del></span>`;
        } else {
            priceHtml = `<span class="pos-product-card__price">${money(product.price)}</span>`;
        }

        return `
            <div class="pos-product-card" data-product-id="${product.id}" data-search="${escapeHtml(searchText)}">
                <div class="pos-product-card__img-wrap">
                    <img src="${escapeHtml(product.image || fallbackImage)}" class="pos-product-card__img" alt="${escapeHtml(product.name)}" loading="lazy">
                    <button type="button" class="pos-product-card__zoom" title="Preview image"><i class="ri-zoom-in-line"></i></button>
                    <span class="badge pos-product-card__stock ${stock > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${stock > 0 ? 'Stock: ' + stock : 'Out of stock'}</span>
                </div>
                <div class="pos-product-card__body">
                    <div class="pos-product-card__name" title="${escapeHtml(product.name)}">${escapeHtml(product.name)}</div>
                    ${priceHtml}
                    ${hasVariants ? `<span class="pos-product-card__variants">${product.variants.length} variant${product.variants.length > 1 ? 's' : ''}</span>` : ''}
                </div>
            </div>
        `;
    }

    function renderProductGrid() {
        productGridEl.innerHTML = products.map(productCardHtml).join('');
    }

    function openVariantModal(product) {
        variantModalTitle.textContent = product.name;
        variantModalBody.innerHTML = '';

        product.variants.forEach(function (variant) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pos-variant-option';
            btn.innerHTML = `
                <img src="${escapeHtml(variant.image || product.image || fallbackImage)}" alt="${escapeHtml(variant.name)}">
                <span class="pos-variant-option__meta">
                    <span class="pos-variant-option__name">${escapeHtml(variant.name)}</span>
                    <span class="pos-variant-option__details text-muted small">${variant.sku ? 'SKU: ' + escapeHtml(variant.sku) + ' · ' : ''}Stock: ${variant.quantity}</span>
                </span>
                <span class="pos-variant-option__price fw-semibold">${money(variant.price)}</span>
            `;
            btn.addEventListener('click', function () {
                addToCart(product.id, variant.id, 1);
                bootstrap.Modal.getInstance(variantModal)?.hide();
            });
            variantModalBody.appendChild(btn);
        });

        if (window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(variantModal).show();
        }
    }

    function addToCart(productId, variantId, quantity, existingId) {
        const product = findProduct(productId);
        if (!product) {
            return;
        }

        const variant = findVariant(product, variantId);
        const stock = variant ? variant.quantity : product.quantity;
        const line = cart.find(item => String(item.product_id) === String(productId) && String(item.product_variant_id || '') === String(variantId || ''));

        if (line) {
            let nextQuantity = line.quantity + quantity;
            if (stock > 0) {
                nextQuantity = Math.min(nextQuantity, stock);
            }
            line.quantity = nextQuantity;
        } else {
            cart.push({
                uid: ++uidCounter,
                id: existingId || '',
                product_id: productId,
                product_variant_id: variantId || '',
                quantity: Math.max(1, quantity),
            });
        }

        renderCart();
    }

    function buildCartRow(line, index) {
        const product = findProduct(line.product_id);
        const variant = findVariant(product, line.product_variant_id);
        const selectedItem = variant || product;
        const stock = selectedItem ? selectedItem.quantity : 0;
        const price = selectedItem ? selectedItem.price : 0;
        const image = selectedItem?.image || fallbackImage;
        const title = product ? `${product.name}${variant ? ' — ' + variant.name : ''}` : 'Unknown product';

        const row = document.createElement('div');
        row.className = 'pos-cart-item';
        row.dataset.uid = line.uid;
        row.innerHTML = `
            <input type="hidden" class="cart-id" name="items[${index}][id]" value="${escapeHtml(line.id)}">
            <input type="hidden" class="cart-product-id" name="items[${index}][product_id]" value="${escapeHtml(line.product_id)}">
            <input type="hidden" class="cart-variant-id" name="items[${index}][product_variant_id]" value="${escapeHtml(line.product_variant_id)}">
            <img class="pos-cart-item__img" src="${escapeHtml(image)}" alt="${escapeHtml(title)}">
            <div class="pos-cart-item__meta">
                <div class="pos-cart-item__name" title="${escapeHtml(title)}">${escapeHtml(title)}</div>
                <div class="pos-cart-item__price text-muted">${money(price)} each · Stock: ${stock}</div>
            </div>
            <div class="pos-cart-item__qty">
                <button type="button" class="btn btn-sm btn-soft-secondary qty-decrease" tabindex="-1">−</button>
                <input type="number" min="1" ${stock > 0 ? `max="${stock}"` : ''} class="form-control form-control-sm cart-quantity" name="items[${index}][quantity]" value="${line.quantity}">
                <button type="button" class="btn btn-sm btn-soft-secondary qty-increase" tabindex="-1">+</button>
            </div>
            <div class="pos-cart-item__total fw-semibold">${money(price * line.quantity)}</div>
            <button type="button" class="btn btn-sm btn-soft-danger remove-cart-item" title="Remove"><i class="ri-delete-bin-line"></i></button>
        `;

        const qtyInput = row.querySelector('.cart-quantity');
        const totalEl = row.querySelector('.pos-cart-item__total');

        function applyQuantity(value) {
            let next = Math.max(1, parseInt(value, 10) || 1);
            if (stock > 0) {
                next = Math.min(next, stock);
            }
            qtyInput.value = next;
            line.quantity = next;
            totalEl.textContent = money(price * next);
            refreshSummary();
        }

        qtyInput.addEventListener('input', function () {
            applyQuantity(this.value);
        });
        row.querySelector('.qty-decrease').addEventListener('click', function () {
            applyQuantity(Number(qtyInput.value || 1) - 1);
        });
        row.querySelector('.qty-increase').addEventListener('click', function () {
            applyQuantity(Number(qtyInput.value || 1) + 1);
        });
        row.querySelector('.pos-cart-item__img').addEventListener('click', function () {
            openImagePreview(image, title);
        });
        row.querySelector('.remove-cart-item').addEventListener('click', function () {
            cart = cart.filter(item => item.uid !== line.uid);
            renderCart();
        });

        return row;
    }

    function renderCart() {
        orderItemsEl.innerHTML = '';
        cart.forEach(function (line, index) {
            orderItemsEl.appendChild(buildCartRow(line, index));
        });
        cartCountEl.textContent = cart.length;
        cartEmptyEl.classList.toggle('d-none', cart.length > 0);
        refreshSummary();
    }

    function refreshSummary() {
        let subtotal = 0;
        cart.forEach(function (line) {
            const product = findProduct(line.product_id);
            const variant = findVariant(product, line.product_variant_id);
            const price = variant ? variant.price : (product ? product.price : 0);
            subtotal += price * line.quantity;
        });

        const shippingMethod = document.getElementById('shipping_method').value || 'inside_dhaka';
        const shipping = Number(deliveryCharges[shippingMethod] || 0);
        const discount = Number(document.getElementById('discount').value || 0);
        const total = Math.max(0, subtotal + shipping - discount);

        document.getElementById('summary-subtotal').textContent = money(subtotal);
        document.getElementById('summary-shipping').textContent = money(shipping);
        document.getElementById('summary-total').textContent = money(total);
    }

    function findGroupByDistrictId(districtId) {
        return deliveryAreaGroups.find(group => String(group.district_id) === String(districtId));
    }

    function findGroupByAreaId(areaId) {
        return deliveryAreaGroups.find(group => group.areas.some(area => String(area.id) === String(areaId)));
    }

    function refreshAreas() {
        const districtGroupSelect = document.getElementById('shipping_district_group');
        const areaSelect = document.getElementById('shipping_delivery_area_id');
        destroySelect2(areaSelect);
        const selected = areaSelect.dataset.selected || areaSelect.value;
        const group = findGroupByDistrictId(districtGroupSelect.value);
        let html = '<option value="">Select area</option>';

        if (group) {
            group.areas.forEach(function (area) {
                html += `<option value="${area.id}" ${String(selected) === String(area.id) ? 'selected' : ''}>${area.name}</option>`;
            });
        }

        areaSelect.innerHTML = html;
        areaSelect.dataset.selected = '';
        initSelect2(areaSelect);

        if (group) {
            updateDeliveryMethod(group);
        }
    }

    function updateDeliveryMethod(group) {
        const districtName = String(group?.district_name || '').trim().toLowerCase();
        const isDhaka = districtName === 'dhaka' || districtName === 'ঢাকা';
        document.getElementById('shipping_method').value = isDhaka ? 'inside_dhaka' : 'outside_dhaka';
        refreshSummary();
    }

    function initializeShippingArea() {
        const districtGroupSelect = document.getElementById('shipping_district_group');
        const areaSelect = document.getElementById('shipping_delivery_area_id');
        const preselectedAreaId = areaSelect.dataset.selected;
        const group = preselectedAreaId ? findGroupByAreaId(preselectedAreaId) : null;

        if (group) {
            districtGroupSelect.value = group.district_id;
        }

        refreshAreas();
    }

    productGridEl.addEventListener('click', function (event) {
        const zoomBtn = event.target.closest('.pos-product-card__zoom');
        const card = event.target.closest('.pos-product-card');
        if (!card) {
            return;
        }

        const product = findProduct(card.dataset.productId);
        if (!product) {
            return;
        }

        if (zoomBtn) {
            event.stopPropagation();
            openImagePreview(product.image, product.name);
            return;
        }

        if (product.variants.length > 0) {
            openVariantModal(product);
        } else {
            addToCart(product.id, null, 1);
        }
    });

    productSearchEl.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        let visibleCount = 0;

        productGridEl.querySelectorAll('.pos-product-card').forEach(function (card) {
            const match = !term || card.dataset.search.includes(term);
            card.classList.toggle('d-none', !match);
            if (match) {
                visibleCount++;
            }
        });

        productEmptyEl.classList.toggle('d-none', visibleCount > 0);
    });

    document.getElementById('shipping_method').addEventListener('change', refreshSummary);
    document.getElementById('discount').addEventListener('input', refreshSummary);
    document.getElementById('shipping_district_group').addEventListener('change', refreshAreas);

    document.getElementById('backend-order-form').addEventListener('submit', function (event) {
        if (cart.length === 0) {
            event.preventDefault();
            alert('Please add at least one product to the cart.');
        }
    });

    if (hasSelect2()) {
        jQuery('#shipping_district_group').on('change', refreshAreas);
    }

    renderProductGrid();

    oldItems.forEach(function (item) {
        if (!item.product_id) {
            return;
        }
        cart.push({
            uid: ++uidCounter,
            id: item.id || '',
            product_id: item.product_id,
            product_variant_id: item.product_variant_id || '',
            quantity: Number(item.quantity) || 1,
        });
    });
    renderCart();

    initializeShippingArea();
    initSelects();
});
</script>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection
