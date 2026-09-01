@extends('layouts.master')

@section('title', 'Edit Product')

@section('css')
    <link href="{{ URL::asset('build/libs/dropzone/dropzone.css') }}" rel="stylesheet">
@endsection

@section('content')
@php
    // Map status from database to form values
    $statusMap = [
        'published' => 'Published',
        'draft' => 'Draft',
        'archived' => 'Archived',
        'scheduled' => 'Scheduled',
    ];
    $formStatus = $statusMap[$product->status] ?? 'Published';

    // Map visibility from database to form values
    $visibilityMap = [
        'public' => 'Public',
        'hidden' => 'Hidden',
    ];
    $formVisibility = $visibilityMap[$product->visibility] ?? 'Public';

    // Get gallery images
    $galleryImages = $product->gallery_images ?? [];
@endphp
<form id="editproduct-form" action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off" class="needs-validation" novalidate>
    @csrf
    @method('PUT')
    <div class="row">
        <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Product Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="product-title-input">Product Title <span class="text-danger">*</span></label>
                            <input type="hidden" class="form-control" id="formAction" name="formAction" value="edit">
                            <input type="text" class="form-control d-none" id="product-id-input" name="product_id" value="{{ $product->id }}">
                            <input type="text" class="form-control" id="product-title-input" name="name" value="{{ old('name', $product->name) }}" placeholder="Enter product title" required>
                            <div class="invalid-feedback">Please Enter a product title.</div>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="sku-input">SKU</label>
                            <input type="text" class="form-control" id="sku-input" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Enter product SKU">
                            @error('sku')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- <div class="mb-3">
                            <label class="form-label" for="landing-page-slug-input">Landing Page Slug</label>
                            <input type="text" class="form-control" id="landing-page-slug-input" name="landing_page_slug" value="{{ old('landing_page_slug', $product->landing_page_slug) }}" placeholder="e.g. beauty-serum-offer">
                            <small class="text-muted">
                                Landing URL:
                                @if(old('landing_page_slug', $product->landing_page_slug))
                                    <a href="{{ route('product.landing', old('landing_page_slug', $product->landing_page_slug)) }}" target="_blank">{{ route('product.landing', old('landing_page_slug', $product->landing_page_slug)) }}</a>
                                @else
                                    {{ url('/landing') }}/your-slug
                                @endif
                            </small>
                            @error('landing_page_slug')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div> --}}
                        <div>
                            <label class="form-label">Product Description</label>
                            <textarea name="description" id="ckeditor-classic">{{ old('description', $product->description) }}</textarea>
                            @error('description')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Additional Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3 mb-lg-0">
                                    <label class="form-label" for="how-to-use-input">How to Use</label>
                                    <textarea class="form-control" id="how-to-use-input" name="how_to_use" rows="6" placeholder="Enter how to use instructions">{{ old('how_to_use', $product->how_to_use) }}</textarea>
                                    @error('how_to_use')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="mb-3 mb-lg-0">
                                    <label class="form-label" for="good-to-know-input">Good to Know</label>
                                    <textarea class="form-control" id="good-to-know-input" name="good_to_know" rows="6" placeholder="Enter good to know information">{{ old('good_to_know', $product->good_to_know) }}</textarea>
                                    @error('good_to_know')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-lg-8">
                                <div class="mb-3 mb-lg-0">
                                    <label class="form-label" for="video-media-input">Video URL</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-video-line"></i></span>
                                        <input type="url" class="form-control" id="video-media-input" name="video_media" value="{{ old('video_media', $product->video_media) }}" placeholder="Enter YouTube, Vimeo, or other video URL">
                                    </div>
                                    @error('video_media')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-0">
                                    <label class="form-label" for="warranty-input">Warranty</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ri-shield-check-line"></i></span>
                                        <input type="text" class="form-control" id="warranty-input" name="warranty" value="{{ old('warranty', $product->warranty) }}" placeholder="e.g. 1 Year">
                                    </div>
                                    @error('warranty')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Product Gallery</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h5 class="fs-14 mb-1">Product Thumbnail Image</h5>
                            <p class="text-muted">Add Product main Image.</p>
                            <div class="text-center">
                                <div class="position-relative d-inline-block">
                                    <div class="position-absolute top-100 start-100 translate-middle">
                                        <label for="product-image-input" class="mb-0"  data-bs-toggle="tooltip" data-bs-placement="right" title="Select Image">
                                            <div class="avatar-xs">
                                                <div class="avatar-title bg-light border rounded-circle text-muted cursor-pointer">
                                                    <i class="ri-image-fill"></i>
                                                </div>
                                            </div>
                                        </label>
                                        <input class="form-control d-none" value="" id="product-image-input" name="thumbnail_image" type="file"
                                            accept="image/png, image/gif, image/jpeg">
                                    </div>
                                    <div class="avatar-lg">
                                        <div class="avatar-title bg-light rounded">
                                            <img src="{{ $product->thumbnail_image ? api_asset($product->thumbnail_image) : asset('build/images/products/img-1.png') }}" id="product-img" class="avatar-md h-auto" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h5 class="fs-14 mb-1">Product Gallery</h5>
                            <p class="text-muted">Add Product Gallery Images.</p>

                            <div class="dropzone" id="gallery-dropzone">
                                <div class="fallback">
                                    <input name="gallery_images[]" type="file" multiple="multiple" accept="image/*">
                                </div>
                                <div class="dz-message needsclick">
                                    <div class="mb-3">
                                        <i class="display-4 text-muted ri-upload-cloud-2-fill"></i>
                                    </div>

                                    <h5>Drop files here or click to upload.</h5>
                                </div>
                            </div>

                            @if(count($galleryImages) > 0)
                            <div class="mt-3">
                                <h6 class="fs-14 mb-2">Existing Gallery Images</h6>
                                <div id="existing-gallery-inputs">
                                    @foreach($galleryImages as $image)
                                        <input type="hidden" name="existing_gallery_images[]" value="{{ $image }}" data-existing-image="{{ $image }}">
                                    @endforeach
                                </div>
                                <div class="row g-2" id="existing-gallery-images">
                                    @foreach($galleryImages as $image)
                                    <div class="col-auto">
                                        <div class="position-relative">
                                            <img src="{{ api_asset($image) }}" alt="Gallery Image" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-existing-image" data-image="{{ $image }}" style="padding: 2px 6px;">
                                                <i class="ri-close-line"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <ul class="list-unstyled mb-0" id="dropzone-preview">
                                <li class="mt-2" id="dropzone-preview-list">
                                    <!-- This is used as the file preview template -->
                                    <div class="border rounded">
                                        <div class="d-flex p-2">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-sm bg-light rounded">
                                                    <img data-dz-thumbnail class="img-fluid rounded d-block" src="#" alt="Product-Image" />
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="pt-1">
                                                    <h5 class="fs-14 mb-1" data-dz-name>&nbsp;</h5>
                                                    <p class="fs-13 text-muted mb-0" data-dz-size></p>
                                                    <strong class="error text-danger" data-dz-errormessage></strong>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0 ms-3">
                                                <button data-dz-remove class="btn btn-sm btn-danger">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <!-- end dropzon-preview -->
                        </div>
                    </div>
                </div>
                <!-- end card -->

                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#editproduct-general-info" role="tab">
                                    General Info
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#editproduct-metadata" role="tab">
                                    Meta Data
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="editproduct-general-info" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="stocks-input">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="stocks-input" name="quantity" value="{{ old('quantity', $product->quantity) }}" placeholder="Quantity" min="0" required>
                                            <div class="invalid-feedback">Please Enter a product quantity.</div>
                                            @error('quantity')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="product-price-input">Selling Price <span class="text-danger">*</span></label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="product-price-addon">৳</span>
                                                <input type="number" step="0.01" class="form-control" id="product-price-input" name="regular_price" value="{{ old('regular_price', $product->regular_price) }}" placeholder="Enter price" aria-label="Price" aria-describedby="product-price-addon" min="0" required>
                                                <div class="invalid-feedback">Please Enter a product price.</div>
                                            </div>
                                            @error('regular_price')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="purchase-price-input">Purchase Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">৳</span>
                                                <input type="number" step="0.01" class="form-control" id="purchase-price-input" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" placeholder="Enter purchase price">
                                            </div>
                                            @error('purchase_price')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="product-location-input">Product Location</label>
                                            <select class="form-select" id="product-location-input" name="product_location">
                                                <option value="store" {{ old('product_location', $product->product_location ?? 'store') == 'store' ? 'selected' : '' }}>Store</option>
                                                <option value="warehouse" {{ old('product_location', $product->product_location ?? 'store') == 'warehouse' ? 'selected' : '' }}>Warehouse</option>
                                            </select>
                                            @error('product_location')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="low-stock-input">Low Stock Alert</label>
                                            <input type="number" class="form-control" id="low-stock-input" name="low_stock_alert" value="{{ old('low_stock_alert', $product->low_stock_alert ?? 0) }}" placeholder="Alert at" min="0">
                                            @error('low_stock_alert')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="stock-status-input">Stock Status</label>
                                            <select class="form-select" id="stock-status-input" name="stock_status">
                                                <option value="in_stock" {{ old('stock_status', $product->stock_status) == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                                                <option value="out_of_stock" {{ old('stock_status', $product->stock_status) == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                                                <option value="pre_order" {{ old('stock_status', $product->stock_status) == 'pre_order' ? 'selected' : '' }}>Pre Order</option>
                                            </select>
                                            @error('stock_status')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label" for="product-discount-input">Discount</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="product-discount-addon">%</span>
                                                <input type="number" step="any" class="form-control" id="product-discount-input" name="discount_percentage" value="{{ old('discount_percentage', $product->discount_percentage) }}" placeholder="Enter discount" aria-label="discount" aria-describedby="product-discount-addon">
                                            </div>
                                            @error('discount_percentage')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label" for="discount-start-date">Discount Start Date</label>
                                            <input type="date" class="form-control" id="discount-start-date" name="discount_start_date" value="{{ old('discount_start_date', $product->discount_start_date ? $product->discount_start_date->format('Y-m-d') : '') }}">
                                            @error('discount_start_date')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label" for="discount-end-date">Discount End Date</label>
                                            <input type="date" class="form-control" id="discount-end-date" name="discount_end_date" value="{{ old('discount_end_date', $product->discount_end_date ? $product->discount_end_date->format('Y-m-d') : '') }}">
                                            @error('discount_end_date')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end tab-pane -->

                            <div class="tab-pane" id="editproduct-metadata" role="tabpanel">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="meta-title-input">Meta Title</label>
                                            <input type="text" class="form-control" placeholder="Enter meta title" id="meta-title-input" name="meta_title" value="{{ old('meta_title', $product->meta_title) }}">
                                            @error('meta_title')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="meta-keywords-input">Meta Keywords</label>
                                            <input type="text" class="form-control" placeholder="Enter meta keywords" id="meta-keywords-input" name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}">
                                            @error('meta_keywords')
                                                <div class="text-danger small">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label" for="meta-description-input">Meta Description</label>
                                    <textarea class="form-control" id="meta-description-input" name="meta_description" placeholder="Enter meta description" rows="3">{{ old('meta_description', $product->meta_description) }}</textarea>
                                    @error('meta_description')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <!-- end tab pane -->
                        </div>
                        <!-- end tab content -->
                    </div>
                    <!-- end card body -->
                </div>
                <!-- end card -->
        </div>
        <!-- end col -->

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Publish</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="choices-publish-status-input" class="form-label">Status</label>

                        <select class="form-select" id="choices-publish-status-input" name="status" data-choices data-choices-search-false>
                            <option value="Published" {{ old('status', $formStatus) == 'Published' ? 'selected' : '' }}>Published</option>
                            <option value="Scheduled" {{ old('status', $formStatus) == 'Scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="Draft" {{ old('status', $formStatus) == 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Archived" {{ old('status', $formStatus) == 'Archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="choices-publish-visibility-input" class="form-label">Visibility</label>
                        <select class="form-select" id="choices-publish-visibility-input" name="visibility" data-choices data-choices-search-false>
                            <option value="Public" {{ old('visibility', $formVisibility) == 'Public' ? 'selected' : '' }}>Public</option>
                            <option value="Hidden" {{ old('visibility', $formVisibility) == 'Hidden' ? 'selected' : '' }}>Hidden</option>
                        </select>
                        <small class="text-muted d-block mt-1">Warehouse products are saved as hidden on the website.</small>
                        @error('visibility')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Publish Schedule</h5>
                </div>
                <!-- end card body -->
                <div class="card-body">
                    <div>
                        <label for="datepicker-publish-input" class="form-label">Publish Date & Time</label>
                        <input type="date" id="datepicker-publish-input" name="published_at" class="form-control"
                            value="{{ old('published_at', $product->published_at ? $product->published_at->format('Y-m-d') : '') }}" placeholder="Enter publish date" data-provider="flatpickr" data-date-format="d.m.y"
                            data-enable-time>
                        @error('published_at')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <!-- end card -->

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Frequently Bought</h5>
                </div>
                <div class="card-body">
                    <div class="md-3">
                        <label for="choices-category-input" class="form-label">Product Category</label>
                        <select class="form-select" id="choices-category-input" name="category_id" data-choices data-choices-search-false>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-3">
                        <label for="brand-input" class="form-label">Product Brand</label>
                        <select class="form-select" id="brand-input" name="brand_id" data-choices data-choices-search-false>
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mt-3">
                        <label for="unit-input" class="form-label">Product Unit</label>
                        <select class="form-select" id="unit-input" name="unit" data-choices data-choices-search-false>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->name }}" {{ old('unit', $unit->name) == $unit->name ? 'selected' : '' }}>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        @error('unit')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Short Description</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">Add short description for product</p>
                    <textarea class="form-control" name="short_description" placeholder="Must enter minimum of a 100 characters" rows="3">{{ old('short_description', $product->short_description) }}</textarea>
                    @error('short_description')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Product Features</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-featured" name="is_featured" value="1" {{ old('is_featured', $product->is_featured) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is-featured">Featured Product</label>
                        </div>
                        @error('is_featured')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-new" name="is_new" value="1" {{ old('is_new', $product->is_new) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is-new">New Product</label>
                        </div>
                        @error('is_new')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-best-seller" name="is_best_seller" value="1" {{ old('is_best_seller', $product->is_best_seller) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is-best-seller">Best Seller</label>
                        </div>
                        @error('is_best_seller')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->

        </div>
        <div class="col-12">
            @include('admin.products._variant-inline')

            <div class="text-end mb-3">
                <button type="submit" class="btn btn-success w-sm">Update Product</button>
            </div>
        </div>
    </div>
    <!-- end row -->
</form>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/@ckeditor/ckeditor5-build-classic/build/ckeditor.js') }}"></script>

<script src="{{ URL::asset('build/libs/dropzone/dropzone-min.js') }}"></script>

<script>
    (function() {
        const locationSelect = document.getElementById('product-location-input');
        const visibilitySelect = document.getElementById('choices-publish-visibility-input');

        function syncWarehouseVisibility() {
            if (!locationSelect || !visibilitySelect || locationSelect.value !== 'warehouse') {
                return;
            }

            visibilitySelect.value = 'Hidden';
            visibilitySelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        locationSelect?.addEventListener('change', syncWarehouseVisibility);
        syncWarehouseVisibility();
    })();
</script>

@include('admin.products._description-editor-scripts')

<script src="{{ URL::asset('build/js/pages/ecommerce-product-create.init.js') }}"></script>

<script>
    // Configure Dropzone to work with form submission
    // Wait for dropzone to be initialized by the init file
    (function() {
        function setupDropzoneFormSubmission() {
            const form = document.getElementById('editproduct-form');
            const dropzoneElement = document.querySelector('#gallery-dropzone, .dropzone');

            if (!form || !dropzoneElement) {
                return;
            }

            // Get dropzone instance (it's stored on the element)
            const dropzone = dropzoneElement.dropzone;

            if (dropzone) {
                // Disable auto-upload
                dropzone.options.autoProcessQueue = false;
                dropzone.options.url = '#';

                // On form submit, add dropzone files to form
                form.addEventListener('submit', function(e) {
                    const files = dropzone.getAcceptedFiles();

                    if (files.length > 0) {
                        // Remove existing gallery_images file inputs (except fallback)
                        const existingInputs = form.querySelectorAll('input[name="gallery_images[]"]');
                        existingInputs.forEach(input => {
                            if (input.type === 'file' && !input.closest('.fallback')) {
                                input.remove();
                            }
                        });

                        // Create file input with DataTransfer
                        const fileInput = document.createElement('input');
                        fileInput.type = 'file';
                        fileInput.name = 'gallery_images[]';
                        fileInput.multiple = true;
                        fileInput.style.display = 'none';

                        try {
                            const dataTransfer = new DataTransfer();
                            files.forEach(file => {
                                dataTransfer.items.add(file);
                            });
                            fileInput.files = dataTransfer.files;
                            form.appendChild(fileInput);
                        } catch (error) {
                            console.error('Error adding files to form:', error);
                            // Fallback: try to use the fallback input
                            const fallbackInput = form.querySelector('.fallback input[name="gallery_images[]"]');
                            if (fallbackInput && files.length > 0) {
                                try {
                                    const dataTransfer = new DataTransfer();
                                    files.forEach(file => {
                                        dataTransfer.items.add(file);
                                    });
                                    fallbackInput.files = dataTransfer.files;
                                } catch (err) {
                                    console.error('Fallback also failed:', err);
                                }
                            }
                        }
                    }
                });
            } else {
                // If dropzone not initialized yet, try again
                setTimeout(setupDropzoneFormSubmission, 100);
            }
        }

        // Try immediately and also after a delay
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(setupDropzoneFormSubmission, 300);
            });
        } else {
            setTimeout(setupDropzoneFormSubmission, 300);
        }
    })();
</script>

<script>
    // Handle thumbnail image preview
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('product-image-input');
        const imagePreview = document.getElementById('product-img');

        if (imageInput && imagePreview) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Handle remove existing gallery image
        document.querySelectorAll('.remove-existing-image').forEach(function(button) {
            button.addEventListener('click', function() {
                const imagePath = this.getAttribute('data-image');
                const imageCard = this.closest('.col-auto');
                const hiddenInput = document.querySelector('input[data-existing-image="' + imagePath + '"]');

                if (hiddenInput) {
                    hiddenInput.remove();
                }

                if (imageCard) {
                    imageCard.remove();
                }
            });
        });
    });
</script>

@include('admin.products._sku-space-sanitizer')
@include('admin.products._variant-inline-scripts')

<script src="{{ URL::asset('build/js/app.js') }}"></script>
@endsection
