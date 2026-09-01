@php
    $isEdit = $category?->exists;
    $submitLabel = $isEdit ? 'Update Category' : 'Create Category';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Category Details' : 'Create Category' }}</h5>
                    <p class="text-muted mb-0">Manage how this category appears to customers.</p>
                </div>
                @if($isEdit && $category->updated_at)
                    <span class="text-muted small">Updated {{ $category->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $category->name ?? '') }}"
                        placeholder="e.g. Electronics"
                        required
                    >
                    @error('name')
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
                        value="{{ old('slug', $category->slug ?? '') }}"
                        placeholder="auto-generated-from-name"
                    >
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Used in URLs. Leave blank to auto-generate.</div>
                </div>

                <div class="mb-0">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Add short marketing copy or SEO description"
                    >{{ old('description', $category->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">SEO Meta</h5>
                <p class="text-muted mb-0 small">Used for category page search listings and social shares.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input
                                type="text"
                                class="form-control @error('meta_title') is-invalid @enderror"
                                id="meta_title"
                                name="meta_title"
                                value="{{ old('meta_title', $category->meta_title ?? '') }}"
                                placeholder="Enter meta title"
                            >
                            @error('meta_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input
                                type="text"
                                class="form-control @error('meta_keywords') is-invalid @enderror"
                                id="meta_keywords"
                                name="meta_keywords"
                                value="{{ old('meta_keywords', $category->meta_keywords ?? '') }}"
                                placeholder="electronics, gadgets, phones"
                            >
                            @error('meta_keywords')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <textarea
                        class="form-control @error('meta_description') is-invalid @enderror"
                        id="meta_description"
                        name="meta_description"
                        rows="3"
                        placeholder="Enter meta description"
                    >{{ old('meta_description', $category->meta_description ?? '') }}</textarea>
                    @error('meta_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label for="meta_image" class="form-label">Meta Image</label>
                    <input
                        type="file"
                        class="form-control @error('meta_image') is-invalid @enderror"
                        id="meta_image"
                        name="meta_image"
                        accept="image/*"
                    >
                    @error('meta_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Optional OG/share image. Defaults to the category image if left empty.</div>

                    <div class="d-flex align-items-center gap-3 mt-3">
                        <div
                            id="meta-image-preview-placeholder"
                            class="{{ $category->meta_image ? 'd-none' : '' }} text-muted small"
                        >
                            No meta image selected
                        </div>
                        <img
                            src="{{ $category->meta_image ? api_asset($category->meta_image) : '' }}"
                            alt="Meta image preview"
                            id="meta-image-preview-img"
                            class="img-thumbnail {{ $category->meta_image ? '' : 'd-none' }}"
                            style="max-height: 100px;"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Hierarchy & Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="parent_id" class="form-label">Parent Category</label>
                    <select
                        class="form-select @error('parent_id') is-invalid @enderror"
                        id="parent_id"
                        name="parent_id"
                    >
                        <option value="">None</option>
                        @foreach($parentOptions as $option)
                            <option
                                value="{{ $option['id'] }}"
                                @selected(old('parent_id', $category->parent_id ?? null) == $option['id'])
                            >
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Choose a parent to nest this category.</div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $category->is_active ?? true))
                    >
                    <label class="form-check-label" for="is_active">Visible to customers</label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_featured"
                        name="is_featured"
                        value="1"
                        @checked(old('is_featured', $category->is_featured ?? false))
                    >
                    <label class="form-check-label" for="is_featured">Featured category</label>
                </div>

                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_popular"
                        name="is_popular"
                        value="1"
                        @checked(old('is_popular', $category->is_popular ?? false))
                    >
                    <label class="form-check-label" for="is_popular">Popular category</label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Category Image</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image" class="form-label">Upload image</label>
                    <input
                        type="file"
                        class="form-control @error('image') is-invalid @enderror"
                        id="image"
                        name="image"
                        accept="image/*"
                    >
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Banner/listing image. Max 5MB.</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="image-preview-placeholder"
                        class="{{ $category->image ? 'd-none' : '' }} text-muted small"
                    >
                        No image selected
                    </div>
                    <img
                        src="{{ $category->image ? api_asset($category->image) : '' }}"
                        alt="Category image preview"
                        id="image-preview-img"
                        class="img-thumbnail {{ $category->image ? '' : 'd-none' }}"
                        style="max-height: 120px;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Category Icon</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="icon_class" class="form-label">Icon class name</label>
                    <div class="input-group">
                        <span class="input-group-text" id="icon-class-preview">
                            <i class="{{ old('icon_class', $category->icon_class ?? 'ri-image-line') }}"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control @error('icon_class') is-invalid @enderror"
                            id="icon_class"
                            name="icon_class"
                            value="{{ old('icon_class', $category->icon_class ?? '') }}"
                            placeholder="e.g. ri-smartphone-line"
                        >
                        @error('icon_class')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-text">Remix Icon class (e.g. <code>ri-smartphone-line</code>).</div>
                </div>

                <div class="mb-3">
                    <label for="icon" class="form-label">Upload icon image</label>
                    <input
                        type="file"
                        class="form-control @error('icon') is-invalid @enderror"
                        id="icon"
                        name="icon"
                        accept="image/*"
                    >
                    @error('icon')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Optional. Recommended 100×100px.</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="icon-preview-placeholder"
                        class="{{ $category->icon ? 'd-none' : '' }} text-muted small"
                    >
                        No icon image selected
                    </div>
                    <img
                        src="{{ $category->icon ? api_asset($category->icon) : '' }}"
                        alt="Icon preview"
                        id="icon-preview-img"
                        class="img-thumbnail {{ $category->icon ? '' : 'd-none' }}"
                        style="max-height: 80px;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Quick tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Keep names short & descriptive.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use an icon class or image for menus.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Fill SEO meta for better search visibility.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Need a reminder?</div>
                <p class="text-muted mb-0 small">You can edit or nest categories at any time.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('categories.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
