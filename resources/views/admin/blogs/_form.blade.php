@php
    $isEdit = $blog?->exists;
    $submitLabel = $isEdit ? 'Update Blog' : 'Publish Blog';
    $selectedStatus = old('status', $blog->status ?? '');
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'Blog Post Details' : 'Create New Blog Post' }}</h5>
                    <p class="text-muted mb-0">Compose and manage your blog content.</p>
                </div>
                @if($isEdit && $blog->updated_at)
                    <span class="text-muted small">Updated {{ $blog->updated_at->diffForHumans() }}</span>
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
                        value="{{ old('title', $blog->title ?? '') }}"
                        placeholder="Enter blog post title"
                        required
                    >
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <input
                    type="hidden"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $blog->slug ?? '') }}"
                >
                @error('slug')
                    <div class="alert alert-danger py-2 px-3 mb-3">{{ $message }}</div>
                @enderror

                <div class="mb-3">
                    <label for="description" class="form-label">Short Description</label>
                    <textarea
                        class="form-control @error('description') is-invalid @enderror"
                        id="description"
                        name="description"
                        rows="2"
                        placeholder="Brief summary for preview (max 500 characters)"
                    >{{ old('description', $blog->description ?? '') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Appears in blog listings and social sharing</div>
                </div>

                <div class="mb-0">
                    <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea
                        class="form-control @error('content') is-invalid @enderror"
                        id="ckeditor-blog-content"
                        name="content"
                        rows="10"
                        placeholder="Write your blog post content here..."
                    >{{ old('content', $blog->content ?? '') }}</textarea>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">You can use HTML or plain text formatting</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Category & Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="blog_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                    <select
                        class="form-select @error('blog_category_id') is-invalid @enderror"
                        id="blog_category_id"
                        name="blog_category_id"
                        required
                    >
                        <option value="">Select a category...</option>
                        @foreach($categories as $category)
                            <option
                                value="{{ $category->id }}"
                                @selected(old('blog_category_id', $blog->blog_category_id ?? null) == $category->id)
                            >
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('blog_category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select
                        class="form-select @error('status') is-invalid @enderror"
                        name="status"
                        required
                    >
                        <option value="" @selected($selectedStatus === '') disabled>Select status...</option>
                        <option value="draft" @selected($selectedStatus === 'draft')>Draft (Private)</option>
                        <option value="published" @selected($selectedStatus === 'published')>Published</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="publish_date" class="form-label">Publish Date</label>
                    <input
                        type="datetime-local"
                        class="form-control @error('publish_date') is-invalid @enderror"
                        id="publish_date"
                        name="publish_date"
                        value="{{ old('publish_date', $blog->publish_date?->format('Y-m-d\TH:i') ?? '') }}"
                    >
                    @error('publish_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Leave empty to use current time when publishing</div>
                </div>

                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_active"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $blog->is_active ?? true))
                    >
                    <label class="form-check-label" for="is_active">
                        <span>Active</span>
                        <span class="d-block text-muted small">Uncheck to hide from blog.</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Featured Image</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="featured_image" class="form-label">Upload image</label>
                    <input
                        type="file"
                        class="form-control @error('featured_image') is-invalid @enderror"
                        id="featured_image"
                        name="featured_image"
                        accept="image/*"
                    >
                    @error('featured_image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Max 5MB. Recommended: 1200×630px</div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="featured_image-preview-placeholder"
                        class="{{ $blog->featured_image ? 'd-none' : '' }} text-muted small"
                    >
                        No image selected
                    </div>
                    <img
                        src="{{ $blog->featured_image ? api_asset($blog->featured_image) : '' }}"
                        alt="Featured image preview"
                        id="featured_image-preview-img"
                        class="img-thumbnail {{ $blog->featured_image ? '' : 'd-none' }}"
                        style="max-height: 150px;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Visibility</h5>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        role="switch"
                        id="is_featured"
                        name="is_featured"
                        value="1"
                        @checked(old('is_featured', $blog->is_featured ?? false))
                    >
                    <label class="form-check-label" for="is_featured">
                        <span>Featured Post</span>
                        <span class="d-block text-muted small">Pin this post to the top of your blog.</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Publishing tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Write compelling titles</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Add a featured image</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use proper categories</li>
                    <li><i class="ri-check-line text-success me-1"></i>Save as draft before publishing</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Save your progress</div>
                <p class="text-muted mb-0 small">You can always save as draft and publish later.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('blogs.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
