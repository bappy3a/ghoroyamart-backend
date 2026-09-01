@extends('layouts.master')

@section('title', 'Blog Posts')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Blog Posts</h4>
                    <p class="text-muted mb-0">Create and manage your blog content.</p>
                </div>
                <a href="{{ route('blogs.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Post
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Views</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blogs as $blog)
                            <tr>
                                <td style="width: 70px;">
                                    @if($blog->featured_image)
                                        <img
                                            src="{{ api_asset($blog->featured_image) }}"
                                            alt="Blog image"
                                            class="rounded border"
                                            style="height: 60px; width: 70px; object-fit: cover;"
                                        >
                                    @else
                                        <div
                                            class="bg-light border rounded d-flex align-items-center justify-content-center text-muted"
                                            style="height: 60px; width: 70px;"
                                        >
                                            <i class="ri-image-line"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong class="d-block text-truncate" style="max-width: 260px;">
                                        {{ $blog->title }}
                                    </strong>
                                    @if($blog->is_featured)
                                        <span class="badge bg-warning-subtle text-warning text-uppercase small">Featured</span>
                                    @endif
                                </td>
                                <td>
                                    @if($blog->category)
                                        <span class="badge bg-info-subtle text-info">
                                            {{ $blog->category->name }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $blog->author?->name ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if($blog->status === 'published')
                                        <span class="badge bg-success-subtle text-success">Published</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $blog->publish_date?->format('d M Y') ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ $blog->views_count }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('blogs.edit', $blog) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('blogs.destroy', $blog) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this blog post?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No blog posts yet. <a href="{{ route('blogs.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $blogs->links() }}
            </div>
        </div>
    </div>
@endsection
