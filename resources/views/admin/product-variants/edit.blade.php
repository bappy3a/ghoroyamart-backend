@extends('layouts.master')

@section('title', 'Edit Product Variant')

@section('content')
    <form action="{{ route('product-variants.update', $productVariant) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Variant Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" value="{{ $productVariant->product?->name }}" disabled>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror" id="sku" name="sku" value="{{ old('sku', $productVariant->sku) }}" required>
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" value="{{ old('quantity', $productVariant->quantity) }}" min="0" required>
                                    @error('quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="selling_price" class="form-label">Selling Price <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('selling_price') is-invalid @enderror" id="selling_price" name="selling_price" value="{{ old('selling_price', $productVariant->selling_price) }}" min="0" required>
                                    @error('selling_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <input type="number" step="0.01" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $productVariant->purchase_price) }}" min="0">
                                    @error('purchase_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $productVariant->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <div class="mt-3">
                            <label for="image" class="form-label">Variant Image</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                            <div class="form-text">Optional. Leave empty to keep the current image.</div>
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($productVariant->image)
                                <div class="mt-2">
                                    <img src="{{ api_asset($productVariant->image) }}" alt="{{ $productVariant->sku }}" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Attribute Values</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $selected = collect(old('attribute_value_ids', $selectedValueIds))->map(fn ($id) => (int) $id)->all();
                        @endphp

                        @error('attribute_value_ids')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <div class="row">
                            @forelse($attributes as $attribute)
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">{{ $attribute->name }}</label>
                                        <select class="form-select" name="attribute_value_ids[]">
                                            <option value="">No {{ $attribute->name }}</option>
                                            @foreach($attribute->values as $value)
                                                <option value="{{ $value->id }}" {{ in_array((int) $value->id, $selected, true) ? 'selected' : '' }}>
                                                    {{ $value->value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <p class="text-muted mb-0">No variant attributes available.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="text-end mb-3">
                    <a href="{{ route('product-variants.index', ['product_id' => $productVariant->product_id]) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-success">Update Variant</button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Current Combination</h5>
                    </div>
                    <div class="card-body">
                        @foreach($productVariant->values->sortBy('variant_attribute_id') as $variantValue)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">{{ $variantValue->attribute?->name }}</span>
                                <span class="fw-semibold">{{ $variantValue->value?->value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
    @include('admin.products._sku-space-sanitizer')
@endsection
