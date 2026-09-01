@php
    $isEdit = $faq?->exists;
    $submitLabel = $isEdit ? 'Update FAQ' : 'Create FAQ';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Edit FAQ' : 'Create FAQ' }}</h5>
                    <p class="text-muted mb-0">Write a clear question and answer for the storefront FAQ section.</p>
                </div>
                @if($isEdit && $faq->updated_at)
                    <span class="text-muted small">Updated {{ $faq->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-lg @error('question') is-invalid @enderror" id="question" name="question" value="{{ old('question', $faq->question ?? '') }}" placeholder="e.g. How do I place an order?" required>
                    @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label for="answer" class="form-label">Answer <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('answer') is-invalid @enderror" id="answer" name="answer" rows="10" placeholder="Write the answer here..." required>{{ old('answer', $faq->answer ?? '') }}</textarea>
                    @error('answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">You can use plain text or paste formatted content from your editor.</div>
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
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <input type="number" class="form-control @error('sort_order') is-invalid @enderror" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $faq->sort_order ?? 0) }}">
                    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Lower numbers appear first on the FAQ page.</div>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $faq->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Active</label>
                    <div class="form-text">Inactive FAQs won't appear on the storefront.</div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Quick tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Keep questions short and direct.</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Answer in a customer-friendly tone.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Use sort order to control display order.</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Need to adjust later?</div>
                <p class="text-muted mb-0 small">You can edit or reorder FAQs anytime.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('faqs.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg">{{ $submitLabel }}</button>
            </div>
        </div>
    </div>
</div>
