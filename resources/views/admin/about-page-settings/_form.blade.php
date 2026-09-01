@php
    $isEdit = $aboutPageSetting?->exists;
    $submitLabel = $isEdit ? 'Update About Settings' : 'Create About Settings';
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Page Title</label>
                        <input type="text" class="form-control" name="page_title" value="{{ old('page_title', $aboutPageSetting->page_title ?? 'About Us') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Breadcrumb Title</label>
                        <input type="text" class="form-control" name="breadcrumb_title" value="{{ old('breadcrumb_title', $aboutPageSetting->breadcrumb_title ?? 'About us') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Breadcrumb Subtitle</label>
                        <input type="text" class="form-control" name="breadcrumb_subtitle" value="{{ old('breadcrumb_subtitle', $aboutPageSetting->breadcrumb_subtitle ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Cover Image</label>
                        <input type="file" class="form-control" name="cover_image" accept="image/*">
                        @if(!empty($aboutPageSetting->cover_image))
                            <img src="{{ api_asset($aboutPageSetting->cover_image) }}" class="img-thumbnail mt-2" style="max-height:120px;" alt="Cover">
                        @endif
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">First Section</h5>
                <div class="row g-3">
                    <div class="col-md-4"><input type="text" class="form-control" name="section_one_subtitle" placeholder="Subtitle" value="{{ old('section_one_subtitle', $aboutPageSetting->section_one_subtitle ?? '') }}"></div>
                    <div class="col-md-8"><input type="text" class="form-control" name="section_one_title" placeholder="Title" value="{{ old('section_one_title', $aboutPageSetting->section_one_title ?? '') }}"></div>
                    <div class="col-12"><textarea class="form-control" name="section_one_content" rows="6" placeholder="Content">{{ old('section_one_content', $aboutPageSetting->section_one_content ?? '') }}</textarea></div>
                    <div class="col-12">
                        <label class="form-label">Section Image</label>
                        <input type="file" class="form-control" name="section_one_image" accept="image/*">
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Second Section</h5>
                <div class="row g-3">
                    <div class="col-md-4"><input type="text" class="form-control" name="section_two_subtitle" placeholder="Subtitle" value="{{ old('section_two_subtitle', $aboutPageSetting->section_two_subtitle ?? '') }}"></div>
                    <div class="col-md-8"><input type="text" class="form-control" name="section_two_title" placeholder="Title" value="{{ old('section_two_title', $aboutPageSetting->section_two_title ?? '') }}"></div>
                    <div class="col-12"><textarea class="form-control" name="section_two_content" rows="6" placeholder="Content">{{ old('section_two_content', $aboutPageSetting->section_two_content ?? '') }}</textarea></div>
                    <div class="col-12">
                        <label class="form-label">Section Image</label>
                        <input type="file" class="form-control" name="section_two_image" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">More About Section</h6>
                <div class="mb-3"><input type="text" class="form-control" name="features_subtitle" placeholder="Subtitle" value="{{ old('features_subtitle', $aboutPageSetting->features_subtitle ?? '') }}"></div>
                <div class="mb-3"><input type="text" class="form-control" name="features_title" placeholder="Title" value="{{ old('features_title', $aboutPageSetting->features_title ?? '') }}"></div>
                <div class="mb-3"><textarea class="form-control" name="features_description" rows="4" placeholder="Description">{{ old('features_description', $aboutPageSetting->features_description ?? '') }}</textarea></div>
                <div class="mb-3"><input type="text" class="form-control" name="feature_one_title" placeholder="Feature 1 Title" value="{{ old('feature_one_title', $aboutPageSetting->feature_one_title ?? '') }}"></div>
                <div class="mb-3"><textarea class="form-control" name="feature_one_description" rows="3" placeholder="Feature 1 Description">{{ old('feature_one_description', $aboutPageSetting->feature_one_description ?? '') }}</textarea></div>
                <div class="mb-3"><input type="text" class="form-control" name="feature_two_title" placeholder="Feature 2 Title" value="{{ old('feature_two_title', $aboutPageSetting->feature_two_title ?? '') }}"></div>
                <div class="mb-3"><textarea class="form-control" name="feature_two_description" rows="3" placeholder="Feature 2 Description">{{ old('feature_two_description', $aboutPageSetting->feature_two_description ?? '') }}</textarea></div>
                <div class="mb-3"><input type="text" class="form-control" name="feature_three_title" placeholder="Feature 3 Title" value="{{ old('feature_three_title', $aboutPageSetting->feature_three_title ?? '') }}"></div>
                <div class="mb-0"><textarea class="form-control" name="feature_three_description" rows="3" placeholder="Feature 3 Description">{{ old('feature_three_description', $aboutPageSetting->feature_three_description ?? '') }}</textarea></div>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Reviews Section</h6>
                <div class="mb-3"><input type="text" class="form-control" name="reviews_subtitle" placeholder="Subtitle" value="{{ old('reviews_subtitle', $aboutPageSetting->reviews_subtitle ?? '') }}"></div>
                <div class="mb-3"><input type="text" class="form-control" name="reviews_title" placeholder="Title" value="{{ old('reviews_title', $aboutPageSetting->reviews_title ?? '') }}"></div>
                <div class="mb-0"><textarea class="form-control" name="reviews_description" rows="4" placeholder="Description">{{ old('reviews_description', $aboutPageSetting->reviews_description ?? '') }}</textarea></div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('about-page-settings.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </div>
</div>
