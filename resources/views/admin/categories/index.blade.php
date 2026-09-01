@extends('layouts.master')

@section('title', 'Categories')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Categories</h4>
                    <p class="text-muted mb-0">Organize products with parent/child categories.</p>
                </div>
                <a href="{{ route('categories.create') }}" class="btn btn-primary">
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
                            <th>Parent</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td style="width: 70px;">
                                    @if($category->image)
                                        <img
                                            src="{{ api_asset($category->image) }}"
                                            alt="{{ $category->name }} image"
                                            class="rounded border"
                                            style="height: 48px; width: 48px; object-fit: cover;"
                                        >
                                    @else
                                        <div
                                            class="bg-light border rounded d-flex align-items-center justify-content-center text-muted"
                                            style="height: 48px; width: 48px;"
                                        >
                                            <i class="{{ $category->icon_class ?: 'ri-image-line' }}"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $category->name }}</strong>
                                    <div class="text-muted small">{{ $category->slug }}</div>
                                </td>
                                <td>{{ $category->parent->name ?? '—' }}</td>
                                <td>
                                    @if($category->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                    @if($category->is_featured)
                                        <span class="badge bg-primary-subtle text-primary">Featured</span>
                                    @endif
                                    @if($category->is_popular)
                                        <span class="badge bg-warning-subtle text-warning">Popular</span>
                                    @endif
                                </td>
                                <td>{{ $category->updated_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this category?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No categories yet. <a href="{{ route('categories.create') }}">Create the first one</a>.
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

