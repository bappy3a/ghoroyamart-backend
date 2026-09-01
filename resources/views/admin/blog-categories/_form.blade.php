@php
    $isEdit = $blog_category?->exists;
    $submitLabel = $isEdit ? 'Update Category' : 'Create Category';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Blog Category Details' : 'Create Blog Category' }}</h5>
                    <p class="text-muted mb-0">Manage how this category appears on your blog.</p>
                </div>
                @if($isEdit && $blog_category->updated_at)
                    <span class="text-muted small">Updated {{ $blog_category->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $blog_category->name ?? '') }}"
                        placeholder="e.g. Technology, Lifestyle, News"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <input
                    type="hidden"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $blog_category->slug ?? '') }}"
                >
                @error('slug')
                    <div class="alert alert-danger py-2 px-3 mb-3">{{ $message }}</div>
                @enderror

                <div class="mb-0">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="4"
                        placeholder="Add category description for SEO and clarity"
                    >{{ old('description', $blog_category->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $blog_category->is_active ?? true))
                    >
                    <label class="form-check-label" for="is_active">
                        <span>Visible on blog</span>
                        <span class="d-block text-muted small">Uncheck to hide this category.</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Category Icon</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="icon" class="form-label">Upload icon</label>
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
                    <div class="form-text">Max 5MB. Recommended size: 100×100px</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="icon-preview-placeholder"
                        class="{{ $blog_category->icon ? 'd-none' : '' }} text-muted small"
                    >
                        No icon selected
                    </div>
                    <img
                        src="{{ $blog_category->icon ? api_asset($blog_category->icon) : '' }}"
                        alt="Icon preview"
                        id="icon-preview-img"
                        class="img-thumbnail {{ $blog_category->icon ? '' : 'd-none' }}"
                        style="max-height: 80px;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Quick tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Keep names short & clear.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use relevant category icons.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Inactive categories won't show in blog.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Organize your content</div>
                <p class="text-muted mb-0 small">Create and manage categories to keep your blog organized.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('blog-categories.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
