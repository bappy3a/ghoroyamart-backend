@php
    $isEdit = $slider?->exists;
    $submitLabel = $isEdit ? 'Update Slide' : 'Create Slide';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Slide content' : 'Create slide' }}</h5>
                    <p class="text-muted mb-0">Matches the homepage hero: eyebrow, title, copy, and CTA.</p>
                </div>
                @if($isEdit && $slider->updated_at)
                    <span class="text-muted small">Updated {{ $slider->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Eyebrow</label>
                            <input
                                type="text"
                                class="form-control @error('subtitle') is-invalid @enderror"
                                id="subtitle"
                                name="subtitle"
                                value="{{ old('subtitle', $slider->subtitle ?? '') }}"
                                placeholder="e.g. টেক উইক"
                            >
                            @error('subtitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Small badge label above the headline.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('title') is-invalid @enderror"
                                id="title"
                                name="title"
                                value="{{ old('title', $slider->title ?? $slider->text ?? '') }}"
                                placeholder="e.g. শব্দ যা মুহূর্তে মিলিয়ে যায়"
                                required
                            >
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Copy</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="e.g. ১,২০০+ যাচাইকৃত বিক্রেতার অডিওতে সর্বোচ্চ ৪৫% ছাড়।"
                    >{{ old('description', $slider->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Short supporting sentence under the title (desktop only on the storefront).</div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="button_text" class="form-label">CTA text</label>
                            <input
                                type="text"
                                class="form-control @error('button_text') is-invalid @enderror"
                                id="button_text"
                                name="button_text"
                                value="{{ old('button_text', $slider->button_text ?? '') }}"
                                placeholder="e.g. ইলেকট্রনিক্স কিনুন"
                            >
                            @error('button_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="button_link" class="form-label">CTA link</label>
                            <input
                                type="text"
                                class="form-control @error('button_link') is-invalid @enderror"
                                id="button_link"
                                name="button_link"
                                value="{{ old('button_link', $slider->button_link ?? '') }}"
                                placeholder="e.g. /products?category=electronics"
                            >
                            @error('button_link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-0">
                    <label for="alt_text" class="form-label">Image alt text</label>
                    <input
                        type="text"
                        class="form-control @error('alt_text') is-invalid @enderror"
                        id="alt_text"
                        name="alt_text"
                        value="{{ old('alt_text', $slider->alt_text ?? '') }}"
                        placeholder="Describe the slide image for accessibility / SEO"
                    >
                    @error('alt_text')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
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
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $slider->is_active ?? true))
                    >
                    <label class="form-check-label d-flex flex-column gap-1" for="is_active">
                        <span>Visible on slider</span>
                        <span class="text-muted small">Uncheck to hide this slide from customers.</span>
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
                        value="{{ old('sort_order', $slider->sort_order ?? 0) }}"
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
                <h5 class="mb-0">Hero image</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="image" class="form-label">
                        Upload image
                        @unless($isEdit)
                            <span class="text-danger">*</span>
                        @endunless
                    </label>
                    <input
                        type="file"
                        class="form-control @error('image') is-invalid @enderror"
                        id="image"
                        name="image"
                        accept="image/*"
                        @unless($isEdit) required @endunless
                    >
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Wide images work best (about 1400×900px). Shown full-bleed behind the text.</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="image-preview-placeholder"
                        class="{{ $slider->image ? 'd-none' : '' }} text-muted small"
                    >
                        No image selected
                    </div>
                    <img
                        src="{{ $slider->image ? api_asset($slider->image) : '' }}"
                        alt="Slide image preview"
                        id="image-preview-img"
                        class="img-thumbnail {{ $slider->image ? '' : 'd-none' }}"
                        style="max-height: 120px;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Hero tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Keep the eyebrow short (1–3 words).</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>One strong headline + one short copy line.</li>
                    <li><i class="ri-check-line text-success me-1"></i>CTA should match the category or offer.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">You can always tweak later</div>
                <p class="text-muted mb-0 small">Reorder or hide slides without affecting past campaigns.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sliders.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
