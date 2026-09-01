@php
    $isEdit = $landingPage?->exists;
    $selectedProductIds = old('product_ids', $isEdit ? $landingPage->products->pluck('id')->toArray() : []);
    $galleryImages = $isEdit ? $landingPage->adminGalleryImages : collect();
    $newGalleryAltText = old('gallery_alt_text', ['']);
    $newGalleryAltText = is_array($newGalleryAltText) && count($newGalleryAltText) ? $newGalleryAltText : [''];
@endphp

@if(!$isEdit)
    @csrf
@endif

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label" for="name">Landing Page Name</label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $landingPage->name ?? '') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="product_ids">Select Products</label>
                    <select id="product_ids" name="product_ids[]" class="form-select @error('product_ids') is-invalid @enderror @error('product_ids.*') is-invalid @enderror" multiple size="10" required>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" {{ in_array($product->id, $selectedProductIds) ? 'selected' : '' }}>
                                {{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Hold Ctrl/Cmd to select multiple products.</div>
                    @error('product_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('product_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="slug">Landing URL Slug</label>
                    <input type="text" id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $landingPage->slug ?? '') }}" required>
                    <div class="form-text">Public URL: {{ url('/promo') }}/your-slug</div>
                    @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="headline">Promo Headline</label>
                    <input type="text" id="headline" name="headline" class="form-control @error('headline') is-invalid @enderror" value="{{ old('headline', $landingPage->headline ?? '') }}">
                    @error('headline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="subheadline">Promo Subheadline</label>
                    <textarea id="subheadline" name="subheadline" rows="3" class="form-control @error('subheadline') is-invalid @enderror">{{ old('subheadline', $landingPage->subheadline ?? '') }}</textarea>
                    @error('subheadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="content">Content</label>
                    <textarea id="ckeditor-content" name="content" rows="8" class="form-control @error('content') is-invalid @enderror">{{ old('content', $landingPage->content ?? '') }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="cta_text">CTA Button Text</label>
                        <input type="text" id="cta_text" name="cta_text" class="form-control @error('cta_text') is-invalid @enderror" value="{{ old('cta_text', $landingPage->cta_text ?? 'Shop Collection') }}">
                        @error('cta_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="cta_url">CTA URL</label>
                        <input type="url" id="cta_url" name="cta_url" class="form-control @error('cta_url') is-invalid @enderror" value="{{ old('cta_url', $landingPage->cta_url ?? '') }}" placeholder="https://...">
                        @error('cta_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $landingPage->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Promotion Gallery Images</h5>

                @if($galleryImages->count())
                    <div class="mb-4">
                        <label class="form-label">Existing Images</label>
                        <div id="existing-gallery-list" class="d-grid gap-3">
                            @foreach($galleryImages as $index => $galleryImage)
                                <div class="border rounded p-2 gallery-existing-item">
                                    <input type="hidden" name="existing_gallery_ids[]" value="{{ $galleryImage->id }}">
                                    <div class="d-flex gap-2">
                                        <img src="{{ api_asset($galleryImage->image_path) }}" alt="{{ $galleryImage->alt_text ?: 'Promotion gallery image' }}" class="rounded border" style="width: 86px; height: 86px; object-fit: cover;">
                                        <div class="flex-grow-1">
                                            <label class="form-label small mb-1" for="existing_gallery_alt_text_{{ $galleryImage->id }}">Image Title</label>
                                            <input type="text" id="existing_gallery_alt_text_{{ $galleryImage->id }}" name="existing_gallery_alt_text[]" class="form-control form-control-sm @error('existing_gallery_alt_text.'.$index) is-invalid @enderror" value="{{ old('existing_gallery_alt_text.'.$index, $galleryImage->alt_text) }}" placeholder="Enter image title">
                                            @error('existing_gallery_alt_text.'.$index)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-existing-gallery-image" data-gallery-id="{{ $galleryImage->id }}">Remove</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div id="deleted-gallery-inputs"></div>

                <div class="mb-3">
                    <label class="form-label">Add New Images</label>
                    <div id="new-gallery-list" class="d-grid gap-3">
                        @foreach($newGalleryAltText as $index => $altText)
                            <div class="border rounded p-2 gallery-new-item">
                                <label class="form-label small mb-1" for="gallery_images_{{ $index }}">Image</label>
                                <input type="file" id="gallery_images_{{ $index }}" name="gallery_images[]" class="form-control form-control-sm @error('gallery_images.'.$index) is-invalid @enderror" accept="image/*">
                                @error('gallery_images.'.$index)<div class="invalid-feedback">{{ $message }}</div>@enderror

                                <label class="form-label small mb-1 mt-2" for="gallery_alt_text_{{ $index }}">Image Title</label>
                                <input type="text" id="gallery_alt_text_{{ $index }}" name="gallery_alt_text[]" class="form-control form-control-sm @error('gallery_alt_text.'.$index) is-invalid @enderror" value="{{ $altText }}" placeholder="Enter image title">
                                @error('gallery_alt_text.'.$index)<div class="invalid-feedback">{{ $message }}</div>@enderror

                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-new-gallery-image {{ $loop->first ? 'd-none' : '' }}">Remove</button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="add-gallery-image" class="btn btn-sm btn-outline-primary mt-3">Add Another Image</button>
                    <div class="form-text">Upload WebP/JPG/PNG images up to 5 MB each. Image title helps SEO and accessibility.</div>
                    @error('gallery_images')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('gallery_alt_text')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('existing_gallery_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('delete_gallery_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('promotion-landing-pages.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update Landing Page' : 'Create Landing Page' }}</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const newGalleryList = document.getElementById('new-gallery-list');
        const addGalleryButton = document.getElementById('add-gallery-image');
        const deletedInputs = document.getElementById('deleted-gallery-inputs');

        function refreshNewGalleryRows() {
            newGalleryList.querySelectorAll('.gallery-new-item').forEach(function (item, index) {
                const imageInput = item.querySelector('input[type="file"]');
                const altInput = item.querySelector('input[name="gallery_alt_text[]"]');
                const imageLabel = item.querySelector('label[for^="gallery_images_"]');
                const altLabel = item.querySelector('label[for^="gallery_alt_text_"]');
                const removeButton = item.querySelector('.remove-new-gallery-image');

                imageInput.id = 'gallery_images_' + index;
                altInput.id = 'gallery_alt_text_' + index;
                imageLabel.setAttribute('for', imageInput.id);
                altLabel.setAttribute('for', altInput.id);
                removeButton.classList.toggle('d-none', newGalleryList.children.length === 1);
            });
        }

        addGalleryButton?.addEventListener('click', function () {
            const firstItem = newGalleryList.querySelector('.gallery-new-item');
            const newItem = firstItem.cloneNode(true);

            newItem.querySelector('input[type="file"]').value = '';
            newItem.querySelector('input[name="gallery_alt_text[]"]').value = '';
            newGalleryList.appendChild(newItem);
            refreshNewGalleryRows();
        });

        newGalleryList?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-new-gallery-image');

            if (! removeButton || newGalleryList.children.length === 1) {
                return;
            }

            removeButton.closest('.gallery-new-item').remove();
            refreshNewGalleryRows();
        });

        document.getElementById('existing-gallery-list')?.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-existing-gallery-image');

            if (! removeButton) {
                return;
            }

            const galleryId = removeButton.getAttribute('data-gallery-id');
            const deleteInput = document.createElement('input');
            deleteInput.type = 'hidden';
            deleteInput.name = 'delete_gallery_ids[]';
            deleteInput.value = galleryId;
            deletedInputs.appendChild(deleteInput);
            removeButton.closest('.gallery-existing-item').remove();
        });

        refreshNewGalleryRows();
    });
</script>

@section('script')
    <script src="{{ URL::asset('build/libs/@ckeditor/ckeditor5-build-classic/build/ckeditor.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const contentField = document.getElementById('ckeditor-content');

            if (! contentField || typeof ClassicEditor === 'undefined') {
                return;
            }

            ClassicEditor.create(contentField)
                .then(function (editor) {
                    editor.ui.view.editable.element.style.minHeight = '250px';

                    contentField.closest('form')?.addEventListener('submit', function () {
                        editor.updateSourceElement();
                    });
                })
                .catch(function (error) {
                    console.error('Unable to initialize CKEditor:', error);
                });
        });
    </script>
@endsection
