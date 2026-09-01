@php
    $isEdit = $videoPromotion?->exists;
    $submitLabel = $isEdit ? 'Update Video Promotion' : 'Create Video Promotion';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Video Promotion Details' : 'Create Video Promotion' }}</h5>
                    <p class="text-muted mb-0">Manage promotional videos for your products.</p>
                </div>
                @if($isEdit && $videoPromotion->updated_at)
                    <span class="text-muted small">Updated {{ $videoPromotion->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control form-control-lg @error('title') is-invalid @enderror"
                        id="title"
                        name="title"
                        value="{{ old('title', $videoPromotion->title ?? '') }}"
                        placeholder="e.g. Product Demo Video"
                        required
                    >
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="video_url" class="form-label">Video URL <span class="text-danger">*</span></label>
                    <input
                        type="url"
                        class="form-control @error('video_url') is-invalid @enderror"
                        id="video_url"
                        name="video_url"
                        value="{{ old('video_url', $videoPromotion->video_url ?? '') }}"
                        placeholder="https://www.youtube.com/watch?v=..."
                        required
                    >
                    @error('video_url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Enter the full URL to the video (YouTube, Vimeo, etc.)</div>
                </div>

                <div class="mb-0">
                    <label for="product_id" class="form-label">Product</label>
                    <select
                        class="form-select @error('product_id') is-invalid @enderror"
                        id="product_id"
                        name="product_id"
                    >
                        <option value="">Select a product (optional)</option>
                        @foreach($products as $product)
                            <option
                                value="{{ $product->id }}"
                                @selected(old('product_id', $videoPromotion->product_id ?? null) == $product->id)
                            >
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('product_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Link this video to a specific product (optional).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Settings</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="order_number" class="form-label">Order Number</label>
                    <input
                        type="number"
                        class="form-control @error('order_number') is-invalid @enderror"
                        id="order_number"
                        name="order_number"
                        min="0"
                        value="{{ old('order_number', $videoPromotion->order_number ?? 0) }}"
                        placeholder="0"
                    >
                    @error('order_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Lower numbers appear first. Used for sorting.</div>
                </div>

                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $videoPromotion->is_active ?? true))
                    >
                    <label class="form-check-label" for="is_active">Active</label>
                    <div class="form-text">Inactive videos won't be displayed.</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4" id="video-preview-card" style="{{ ($isEdit && $videoPromotion->video_url) || old('video_url') ? '' : 'display: none;' }}">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Video Preview</h5>
            </div>
            <div class="card-body">
                <div class="ratio ratio-16x9">
                    <iframe
                        src="{{ $isEdit && $videoPromotion->video_url ? $videoPromotion->video_url : '' }}"
                        allowfullscreen
                        id="video-preview"
                    ></iframe>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Quick tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use descriptive titles for better SEO.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Link to products for better conversion.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Order numbers control display sequence.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Need a reminder?</div>
                <p class="text-muted mb-0 small">You can edit video promotions at any time.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('video-promotions.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
