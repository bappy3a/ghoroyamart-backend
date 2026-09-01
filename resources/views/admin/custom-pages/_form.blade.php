@php
    $isEdit = $customPage?->exists;
    $submitLabel = $isEdit ? 'Update Page' : 'Create Page';
@endphp

@if(!$isEdit)
    @csrf
@endif

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Page details' : 'Create custom page' }}</h5>
                    <p class="text-muted mb-0">Add bilingual content for your custom pages.</p>
                </div>
                @if($isEdit && $customPage->updated_at)
                    <span class="text-muted small">Updated {{ $customPage->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control form-control-lg @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $customPage->name ?? '') }}"
                        placeholder="e.g. About Us"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="slug" class="form-label">Slug <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control @error('slug') is-invalid @enderror"
                        id="slug"
                        name="slug"
                        value="{{ old('slug', $customPage->slug ?? '') }}"
                        placeholder="e.g. about-us"
                        required
                    >
                    @error('slug')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">URL-friendly version of the name. Auto-generated if left empty.</div>
                </div>

                <div class="mb-3">
                    <label for="sub_title" class="form-label">Sub Title</label>
                    <input
                        type="text"
                        class="form-control @error('sub_title') is-invalid @enderror"
                        id="sub_title"
                        name="sub_title"
                        value="{{ old('sub_title', $customPage->sub_title ?? '') }}"
                        placeholder="e.g. Learn more about our company"
                    >
                    @error('sub_title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="en_content" class="form-label">English Content</label>
                    <textarea
                        class="form-control @error('en_content') is-invalid @enderror"
                        id="ckeditor-en"
                        name="en_content"
                        rows="10"
                        placeholder="Enter English content here..."
                    >{{ old('en_content', $customPage->en_content ?? '') }}</textarea>
                    @error('en_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label for="bn_content" class="form-label">Bengali Content</label>
                    <textarea
                        class="form-control @error('bn_content') is-invalid @enderror"
                        id="ckeditor-bn"
                        name="bn_content"
                        rows="10"
                        placeholder="Enter Bengali content here..."
                    >{{ old('bn_content', $customPage->bn_content ?? '') }}</textarea>
                    @error('bn_content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                @if($isEdit)
                    <h6 class="fw-semibold mb-2">Public page</h6>
                    <p class="text-muted small">This page is published automatically. Share or open this storefront link.</p>
                    <div class="input-group mb-3">
                        <input
                            type="text"
                            id="custom-page-public-url"
                            class="form-control"
                            value="{{ $customPage->publicUrl() }}"
                            readonly
                        >
                        <button type="button" id="copy-custom-page-url" class="btn btn-outline-secondary">
                            Copy
                        </button>
                    </div>
                    <a
                        href="{{ $customPage->publicUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-info w-100 mb-4"
                    >
                        <i class="ri-external-link-line me-1"></i>
                        View Public Page
                    </a>
                @else
                    <div class="alert alert-info small">
                        Save this page to publish it and receive its public storefront link.
                    </div>
                @endif

                <h6 class="fw-semibold mb-3">Tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use descriptive names for easy identification.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Slugs should be URL-friendly (lowercase, hyphens).</li>
                    <li><i class="ri-check-line text-success me-1"></i>Both English and Bengali content are optional.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Need to make changes later?</div>
                <p class="text-muted mb-0 small">You can update page content any time.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('custom-pages.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
