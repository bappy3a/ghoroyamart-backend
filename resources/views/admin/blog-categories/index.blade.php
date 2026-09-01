@extends('layouts.master')

@section('title', 'Blog Categories')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Blog Categories</h4>
                    <p class="text-muted mb-0">Manage categories to organize your blog posts.</p>
                </div>
                <a href="{{ route('blog-categories.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Category
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
                            <th>Icon</th>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Posts</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td style="width: 60px;">
                                    @if($category->icon)
                                        <img
                                            src="{{ api_asset($category->icon) }}"
                                            alt="Category icon"
                                            class="rounded border"
                                            style="height: 50px; width: 50px; object-fit: cover;"
                                        >
                                    @else
                                        <div
                                            class="bg-light border rounded d-flex align-items-center justify-content-center text-muted"
                                            style="height: 50px; width: 50px;"
                                        >
                                            <i class="ri-folder-line"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong class="d-block">
                                        {{ $category->name }}
                                    </strong>
                                    @if($category->description)
                                        <span class="text-muted small d-block text-truncate" style="max-width: 300px;">
                                            {{ $category->description }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <code class="text-muted">{{ $category->slug }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info">
                                        {{ $category->blogs()->count() }}
                                    </span>
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $category->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('blog-categories.edit', $category) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('blog-categories.destroy', $category) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this category? Associated blogs will not be deleted.');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No categories yet. <a href="{{ route('blog-categories.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
@endsection
