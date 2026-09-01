@php
    $isEdit = $brand?->exists;
    $submitLabel = $isEdit ? 'Update Brand' : 'Create Brand';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Brand details' : 'Create brand' }}</h5>
                    <p class="text-muted mb-0">Highlight the partners shoppers see across the store.</p>
                </div>
                @if($isEdit && $brand->updated_at)
                    <span class="text-muted small">Updated {{ $brand->updated_at->diffForHumans() }}</span>
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
                        value="{{ old('name', $brand->name ?? '') }}"
                        placeholder="e.g. Acme Co."
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-0">
                    <label for="description" class="form-label">Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="What makes this brand unique?"
                    >{{ old('description', $brand->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Visible in listings and marketing blocks.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Visibility & order</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="status"
                        name="status"
                        value="1"
                        @checked(old('status', $brand->status ?? true))
                    >
                    <label class="form-check-label d-flex flex-column gap-1" for="status">
                        <span>Active on storefront</span>
                        <span class="text-muted small">Uncheck to hide this brand from customers.</span>
                    </label>
                </div>

                <div class="form-check form-switch mb-3">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_featured"
                        name="is_featured"
                        value="1"
                        @checked(old('is_featured', $brand->is_featured ?? false))
                    >
                    <label class="form-check-label d-flex flex-column gap-1" for="is_featured">
                        <span>Show in featured carousel</span>
                        <span class="text-muted small">Use for campaigns or homepage highlights.</span>
                    </label>
                </div>

                <div class="mb-0">
                    <label for="sort_order" class="form-label">Display order</label>
                    <input
                        type="number"
                        class="form-control @error('sort_order') is-invalid @enderror"
                        id="sort_order"
                        name="sort_order"
                        min="0"
                        max="100000"
                        value="{{ old('sort_order', $brand->sort_order ?? 0) }}"
                        placeholder="0"
                    >
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Lower numbers appear first.</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Brand logo</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="logo" class="form-label">Upload logo</label>
                    <input
                        type="file"
                        class="form-control @error('logo') is-invalid @enderror"
                        id="logo"
                        name="logo"
                        accept="image/*"
                    >
                    @error('logo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">High contrast square images work best.</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="logo-preview-placeholder"
                        class="{{ $brand->logo ? 'd-none' : '' }} text-muted small"
                    >
                        No logo selected
                    </div>
                    <img
                        src="{{ $brand->logo ? api_asset($brand->logo) : '' }}"
                        alt="Logo preview"
                        id="logo-preview-img"
                        class="img-thumbnail {{ $brand->logo ? '' : 'd-none' }}"
                        style="max-height: 90px;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Add a short elevator pitch.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use featured brands for campaigns.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Logos convert best at 512×512px.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Need to make changes later?</div>
                <p class="text-muted mb-0 small">You can update brand visibility or logos any time.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('brands.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>

