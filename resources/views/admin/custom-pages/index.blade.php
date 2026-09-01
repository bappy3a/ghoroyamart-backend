@extends('layouts.master')

@section('title', 'Custom Pages')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Custom Pages</h4>
                    <p class="text-muted mb-0">Every saved page is automatically published on the storefront.</p>
                </div>
                <a href="{{ route('custom-pages.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Page
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
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Public URL</th>
                            <th>Sub Title</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customPages as $page)
                            <tr>
                                <td>
                                    <strong>{{ $page->name }}</strong>
                                </td>
                                <td>
                                    <code class="text-muted">{{ $page->slug }}</code>
                                </td>
                                <td>
                                    <a
                                        href="{{ $page->publicUrl() }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary"
                                        title="{{ $page->publicUrl() }}"
                                    >
                                        {{ \Illuminate\Support\Str::limit($page->publicUrl(), 42) }}
                                        <i class="ri-external-link-line ms-1"></i>
                                    </a>
                                </td>
                                <td>
                                    {{ $page->sub_title ?: '—' }}
                                </td>
                                <td>{{ $page->created_at?->format('d M Y') ?? '—' }}</td>
                                <td>{{ $page->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a
                                        href="{{ $page->publicUrl() }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-info"
                                    >
                                        View Page
                                    </a>
                                    <a href="{{ route('custom-pages.edit', $page) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('custom-pages.destroy', $page) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this custom page?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No custom pages yet. <a href="{{ route('custom-pages.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $customPages->links() }}
            </div>
        </div>
    </div>
@endsection
