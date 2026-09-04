@extends('layouts.master')

@section('title', 'Home Page Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Home Page Settings</h4>
                <p class="text-muted mb-0">Simple tab-wise control panel for homepage sections.</p>
            </div>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('home-page-settings.update') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header bg-transparent">
            <ul class="nav nav-tabs card-header-tabs" id="homeSettingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-banners" type="button" role="tab">Category Banners</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-shopping" type="button" role="tab">Shopping Section</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-deals" type="button" role="tab">Deals</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-video" type="button" role="tab">Video & Trending</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button" role="tab">Reviews</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-instagram" type="button" role="tab">Image gallery</button>
                </li>
            </ul>
        </div>

        <div class="card-body tab-content">
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                <h5 class="mb-3">Section Display Counts</h5>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Slider</label><input type="number" min="1" class="form-control" name="home_slider_count" value="{{ old('home_slider_count', $settings['home_slider_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Popular Categories</label><input type="number" min="1" class="form-control" name="home_popular_categories_count" value="{{ old('home_popular_categories_count', $settings['home_popular_categories_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Featured Products</label><input type="number" min="1" class="form-control" name="home_featured_products_count" value="{{ old('home_featured_products_count', $settings['home_featured_products_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Best Selling</label><input type="number" min="1" class="form-control" name="home_best_selling_products_count" value="{{ old('home_best_selling_products_count', $settings['home_best_selling_products_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Trending Products</label><input type="number" min="1" class="form-control" name="home_trending_products_count" value="{{ old('home_trending_products_count', $settings['home_trending_products_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">New Arrival</label><input type="number" min="1" class="form-control" name="home_new_arrival_products_count" value="{{ old('home_new_arrival_products_count', $settings['home_new_arrival_products_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Popular Products</label><input type="number" min="1" class="form-control" name="home_popular_products_count" value="{{ old('home_popular_products_count', $settings['home_popular_products_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Diamond Products</label><input type="number" min="1" class="form-control" name="home_diamond_products_count" value="{{ old('home_diamond_products_count', $settings['home_diamond_products_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Brands</label><input type="number" min="1" class="form-control" name="home_brands_count" value="{{ old('home_brands_count', $settings['home_brands_count']) }}"></div>
                    <div class="col-md-3"><label class="form-label">Video Promotions</label><input type="number" min="1" class="form-control" name="home_video_promotions_count" value="{{ old('home_video_promotions_count', $settings['home_video_promotions_count']) }}"></div>
                </div>

                <hr>
                <h5 class="mb-3">Section Titles</h5>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Popular Categories Title</label><input type="text" class="form-control" name="home_popular_categories_title" value="{{ old('home_popular_categories_title', $settings['home_popular_categories_title']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Featured Subtitle</label><input type="text" class="form-control" name="home_featured_section_subtitle" value="{{ old('home_featured_section_subtitle', $settings['home_featured_section_subtitle']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Featured Title</label><input type="text" class="form-control" name="home_featured_section_title" value="{{ old('home_featured_section_title', $settings['home_featured_section_title']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Top Selling Title</label><input type="text" class="form-control" name="home_top_selling_title" value="{{ old('home_top_selling_title', $settings['home_top_selling_title']) }}"></div>
                </div>

            </div>

            <div class="tab-pane fade" id="tab-banners" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Dynamic Category Banners</h5>
                        <p class="text-muted mb-0">Add category, banner, link, and products limit for each banner card.</p>
                    </div>
                    <button type="button" id="add-category-banner" class="btn btn-primary btn-sm">+ Add Banner</button>
                </div>
                <div id="category-banners-wrapper" class="d-flex flex-column gap-3"></div>
            </div>

            <div class="tab-pane fade" id="tab-shopping" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Shopping Every Day - Multiple Items</h5>
                    <button type="button" id="add-shopping-item" class="btn btn-primary btn-sm">+ Add Item</button>
                </div>
                <p class="text-muted mb-3">Each item: banner image, title, category, and products limit.</p>
                <div id="shopping-items-wrapper" class="d-flex flex-column gap-3"></div>
            </div>

            <div class="tab-pane fade" id="tab-deals" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Price Deals - Multiple Items</h5>
                    <button type="button" id="add-deal-item" class="btn btn-primary btn-sm">+ Add Deal</button>
                </div>
                <p class="text-muted mb-3">Set a title, minimum and maximum price, and banner. For example, 1 Tk to 99 Tk. Each deal shows up to 8 matching products.</p>
                <div id="deal-items-wrapper" class="d-flex flex-column gap-3"></div>
            </div>

            <div class="tab-pane fade" id="tab-video" role="tabpanel">
                <h5 class="mb-3">Video Section</h5>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">YouTube Link</label><input type="url" class="form-control" name="home_video_link" value="{{ old('home_video_link', ($settings['home_video_link'] ?? '') === '#' ? '' : ($settings['home_video_link'] ?? '')) }}"></div>
                    <div class="col-md-6"><label class="form-label">Video Banner Text</label><input type="text" class="form-control" name="home_video_banner_text" value="{{ old('home_video_banner_text', $settings['home_video_banner_text']) }}"></div>
                    <div class="col-md-6"><label class="form-label">Video Banner Image</label><input type="file" class="form-control" name="home_video_banner" accept="image/*">@if(!empty($settings['home_video_banner']))<img src="{{ api_asset($settings['home_video_banner']) }}" class="img-thumbnail mt-2" style="max-height:100px;" alt="Video banner">@endif</div>
                </div>

                <hr>
                <h5 class="mb-3">Trending Banner</h5>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Subtitle</label><input type="text" class="form-control" name="home_trending_banner_subtitle" value="{{ old('home_trending_banner_subtitle', $settings['home_trending_banner_subtitle']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Title/Text</label><input type="text" class="form-control" name="home_trending_banner_text" value="{{ old('home_trending_banner_text', $settings['home_trending_banner_text']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Link</label><input type="url" class="form-control" name="home_trending_banner_link" value="{{ old('home_trending_banner_link', ($settings['home_trending_banner_link'] ?? '') === '#' ? '' : ($settings['home_trending_banner_link'] ?? '')) }}"></div>
                    <div class="col-md-4"><label class="form-label">Link Text</label><input type="text" class="form-control" name="home_trending_banner_link_text" value="{{ old('home_trending_banner_link_text', $settings['home_trending_banner_link_text']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Banner Image</label><input type="file" class="form-control" name="home_trending_banner_image" accept="image/*">@if(!empty($settings['home_trending_banner_image']))<img src="{{ api_asset($settings['home_trending_banner_image']) }}" class="img-thumbnail mt-2" style="max-height:100px;" alt="Trending banner">@endif</div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-reviews" role="tabpanel">
                <h5 class="mb-3">Review Section Content</h5>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Review Subtitle</label><input type="text" class="form-control" name="home_product_review_subtitle" value="{{ old('home_product_review_subtitle', $settings['home_product_review_subtitle']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Review Title</label><input type="text" class="form-control" name="home_product_review_title" value="{{ old('home_product_review_title', $settings['home_product_review_title']) }}"></div>
                    <div class="col-md-4"><label class="form-label">Review Description</label><input type="text" class="form-control" name="home_product_review_description" value="{{ old('home_product_review_description', $settings['home_product_review_description']) }}"></div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-instagram" role="tabpanel">
                <h5 class="mb-3">Image gallery</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Upload Multiple Images</label>
                        <input type="file" class="form-control" name="home_instagram_images[]" accept="image/*" multiple>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    @foreach(($settings['home_instagram_images'] ?? []) as $img)
                        <div class="col-md-3 home-gallery-image">
                            <input type="hidden" name="home_instagram_existing[]" value="{{ $img }}">
                            <div class="border rounded p-2 h-100">
                                <img src="{{ api_asset($img) }}" class="img-thumbnail w-100" style="height:100px; object-fit:cover;" alt="Instagram image">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-2 remove-home-gallery-image">Delete</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Save Home Page Settings</button>
        </div>
    </div>
</form>

<div class="modal fade" id="deleteHomeGalleryImageModal" tabindex="-1" aria-labelledby="deleteHomeGalleryImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteHomeGalleryImageModalLabel">Delete Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this gallery image?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmHomeGalleryImageDelete">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@php
    $homePageImageUrls = collect($settings['home_category_banners'] ?? [])
        ->concat($settings['home_shopping_section_items'] ?? [])
        ->concat($settings['home_deal_section_items'] ?? [])
        ->pluck('banner_image')
        ->filter()
        ->unique()
        ->mapWithKeys(fn ($path) => [$path => api_asset($path)]);
@endphp
<script>
    (function () {
        const categories = @json($categories);
        const existingBanners = @json($settings['home_category_banners'] ?? []);
        const existingShoppingItems = @json($settings['home_shopping_section_items'] ?? []);
        const existingDealItems = @json($settings['home_deal_section_items'] ?? []);
        const imageUrls = @json($homePageImageUrls);
        const imageUrlTemplate = @json(api_asset('__API_ASSET_PATH__'));
        const wrapper = document.getElementById('category-banners-wrapper');
        const addButton = document.getElementById('add-category-banner');
        const shoppingWrapper = document.getElementById('shopping-items-wrapper');
        const addShoppingButton = document.getElementById('add-shopping-item');
        const dealWrapper = document.getElementById('deal-items-wrapper');
        const addDealButton = document.getElementById('add-deal-item');
        const deleteHomeGalleryImageModalElement = document.getElementById('deleteHomeGalleryImageModal');
        const deleteHomeGalleryImageModal = deleteHomeGalleryImageModalElement ? new bootstrap.Modal(deleteHomeGalleryImageModalElement) : null;
        let pendingHomeGalleryImage = null;

        function resolveImageUrl(path) {
            const value = String(path || '');

            if (!value || /^(?:https?:)?\/\//i.test(value) || value.startsWith('data:')) {
                return value;
            }

            return imageUrls[value]
                || imageUrlTemplate.replace('__API_ASSET_PATH__', value.replace(/^\/+/, ''));
        }

        document.querySelectorAll('.remove-home-gallery-image').forEach((button) => {
            button.addEventListener('click', () => {
                pendingHomeGalleryImage = button.closest('.home-gallery-image');
                deleteHomeGalleryImageModal?.show();
            });
        });

        document.getElementById('confirmHomeGalleryImageDelete')?.addEventListener('click', () => {
            pendingHomeGalleryImage?.remove();
            pendingHomeGalleryImage = null;
            deleteHomeGalleryImageModal?.hide();
        });

        function categoryOptions(selectedValue) {
            let html = '<option value="">Select category</option>';
            categories.forEach((c) => {
                const selected = String(selectedValue || '') === String(c.id) ? 'selected' : '';
                html += `<option value="${c.id}" ${selected}>${c.name}</option>`;
            });
            return html;
        }

        function bannerRow(index, data = {}) {
            const card = document.createElement('div');
            card.className = 'card border';
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Banner #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-banner">Remove</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_banners[${index}][category_id]">${categoryOptions(data.category_id)}</select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Products</label>
                            <input type="number" min="1" class="form-control" name="category_banners[${index}][products_limit]" value="${data.products_limit || 8}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Banner Text</label>
                            <input type="text" class="form-control" name="category_banners[${index}][text]" value="${data.text || ''}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Discount Text</label>
                            <input type="text" class="form-control" name="category_banners[${index}][discount_text]" value="${data.discount_text || ''}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Link Text</label>
                            <input type="text" class="form-control" name="category_banners[${index}][link_text]" value="${data.link_text || ''}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link</label>
                            <input type="url" class="form-control" name="category_banners[${index}][link]" value="${(data.link && data.link !== '#') ? data.link : ''}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Banner Image</label>
                            <input type="file" class="form-control" name="category_banners_images[${index}]" accept="image/*">
                            <input type="hidden" name="category_banners[${index}][existing_image]" value="${data.banner_image || ''}">
                            ${data.banner_image ? `<img src="${resolveImageUrl(data.banner_image)}" class="img-thumbnail mt-2" style="max-height:80px;" alt="Banner">` : ''}
                        </div>
                    </div>
                </div>
            `;

            card.querySelector('.remove-banner').addEventListener('click', () => {
                card.remove();
                rebuildIndexes();
            });

            return card;
        }

        function rebuildIndexes() {
            const cards = wrapper.querySelectorAll('.card');
            const data = Array.from(cards).map((card) => ({
                category_id: card.querySelector('select')?.value || '',
                products_limit: card.querySelector('input[name*="[products_limit]"]')?.value || 8,
                text: card.querySelector('input[name*="[text]"]')?.value || '',
                discount_text: card.querySelector('input[name*="[discount_text]"]')?.value || '',
                link: card.querySelector('input[name*="[link]"]')?.value || '',
                link_text: card.querySelector('input[name*="[link_text]"]')?.value || '',
                banner_image: card.querySelector('input[name*="[existing_image]"]')?.value || '',
            }));

            wrapper.innerHTML = '';
            data.forEach((d, i) => wrapper.appendChild(bannerRow(i, d)));
        }

        addButton.addEventListener('click', function () {
            const index = wrapper.querySelectorAll('.card').length;
            wrapper.appendChild(bannerRow(index));
        });

        if (existingBanners.length) {
            existingBanners.forEach((b, i) => wrapper.appendChild(bannerRow(i, b)));
        } else {
            wrapper.appendChild(bannerRow(0));
        }

        function shoppingRow(index, data = {}) {
            const card = document.createElement('div');
            card.className = 'card border';
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Shopping Item #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-shopping-item">Remove</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Banner Title</label>
                            <input type="text" class="form-control" name="shopping_section_items[${index}][title]" value="${data.title || ''}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="shopping_section_items[${index}][category_id]">${categoryOptions(data.category_id)}</select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Products Limit</label>
                            <input type="number" min="1" class="form-control" name="shopping_section_items[${index}][products_limit]" value="${data.products_limit || 8}">
                            <input type="hidden" name="shopping_section_items[${index}][link_text]" value="${data.link_text || 'Shop Now'}">
                            <input type="hidden" name="shopping_section_items[${index}][link]" value="${(data.link && data.link !== '#') ? data.link : ''}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Banner Image</label>
                            <input type="file" class="form-control" name="shopping_section_item_images[${index}]" accept="image/*">
                            <input type="hidden" name="shopping_section_items[${index}][existing_image]" value="${data.banner_image || ''}">
                            ${data.banner_image ? `<img src="${resolveImageUrl(data.banner_image)}" class="img-thumbnail mt-2" style="max-height:80px;" alt="Shopping Banner">` : ''}
                        </div>
                    </div>
                </div>
            `;

            card.querySelector('.remove-shopping-item').addEventListener('click', () => {
                card.remove();
                rebuildShoppingIndexes();
            });

            return card;
        }

        function rebuildShoppingIndexes() {
            const cards = shoppingWrapper.querySelectorAll('.card');
            const data = Array.from(cards).map((card) => ({
                title: card.querySelector('input[name*="[title]"]')?.value || '',
                category_id: card.querySelector('select')?.value || '',
                products_limit: card.querySelector('input[name*="[products_limit]"]')?.value || 8,
                link_text: card.querySelector('input[name*="[link_text]"]')?.value || 'Shop Now',
                link: card.querySelector('input[name*="[link]"]')?.value || '',
                banner_image: card.querySelector('input[name*="[existing_image]"]')?.value || '',
            }));
            shoppingWrapper.innerHTML = '';
            data.forEach((d, i) => shoppingWrapper.appendChild(shoppingRow(i, d)));
        }

        addShoppingButton.addEventListener('click', function () {
            const index = shoppingWrapper.querySelectorAll('.card').length;
            shoppingWrapper.appendChild(shoppingRow(index));
        });

        if (existingShoppingItems.length) {
            existingShoppingItems.forEach((item, i) => shoppingWrapper.appendChild(shoppingRow(i, item)));
        } else {
            shoppingWrapper.appendChild(shoppingRow(0));
        }

        function dealRow(index, data = {}) {
            const card = document.createElement('div');
            card.className = 'card border';
            card.innerHTML = `
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Deal #${index + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-deal-item">Remove</button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Deal Title</label>
                            <input type="text" class="form-control" name="deal_section_items[${index}][title]" value="${data.title || ''}" placeholder="e.g. 1 to 99 Tk Deals">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Minimum Price (Tk)</label>
                            <input type="number" min="0" step="0.01" class="form-control" name="deal_section_items[${index}][min_price]" value="${data.min_price ?? ''}" placeholder="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Maximum Price (Tk)</label>
                            <input type="number" min="0.01" step="0.01" class="form-control" name="deal_section_items[${index}][max_price]" value="${data.max_price || ''}" placeholder="99">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Banner Image</label>
                            <input type="file" class="form-control" name="deal_section_item_images[${index}]" accept="image/*">
                            <input type="hidden" name="deal_section_items[${index}][existing_image]" value="${data.banner_image || ''}">
                            ${data.banner_image ? `<img src="${resolveImageUrl(data.banner_image)}" class="img-thumbnail mt-2" style="max-height:80px;" alt="Deal Banner">` : ''}
                        </div>
                    </div>
                </div>
            `;

            card.querySelector('.remove-deal-item').addEventListener('click', () => {
                card.remove();
                rebuildDealIndexes();
            });

            return card;
        }

        function rebuildDealIndexes() {
            const data = Array.from(dealWrapper.querySelectorAll('.card')).map((card) => ({
                title: card.querySelector('input[name*="[title]"]')?.value || '',
                min_price: card.querySelector('input[name*="[min_price]"]')?.value || '',
                max_price: card.querySelector('input[name*="[max_price]"]')?.value || '',
                banner_image: card.querySelector('input[name*="[existing_image]"]')?.value || '',
            }));
            dealWrapper.innerHTML = '';
            data.forEach((item, index) => dealWrapper.appendChild(dealRow(index, item)));
        }

        addDealButton.addEventListener('click', function () {
            dealWrapper.appendChild(dealRow(dealWrapper.querySelectorAll('.card').length));
        });

        if (existingDealItems.length) {
            existingDealItems.forEach((item, index) => dealWrapper.appendChild(dealRow(index, item)));
        } else {
            dealWrapper.appendChild(dealRow(0));
        }
    })();
</script>
@endsection
