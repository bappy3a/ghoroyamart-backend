@extends('layouts.master')

@section('title', 'Promotion Landing Pages')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Promotion Landing Pages</h4>
                <p class="text-muted mb-0">Build conversion-focused pages and attach multiple products.</p>
            </div>
            <a href="{{ route('promotion-landing-pages.create') }}" class="btn btn-primary">
                <i class="ri-add-line align-middle me-1"></i> New Landing Page
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
                        <th>Products</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($landingPages as $landingPage)
                        <tr>
                            <td>{{ $landingPage->name }}</td>
                            <td>
                                <span class="badge bg-info-subtle text-info">{{ $landingPage->products->count() }} selected</span>
                            </td>
                            <td><code>{{ $landingPage->slug }}</code></td>
                            <td>
                                <span class="badge {{ $landingPage->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                    {{ $landingPage->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('promo.landing.show', $landingPage->slug) }}" class="btn btn-sm btn-info" target="_blank">View</a>
                                <a href="{{ route('promotion-landing-pages.edit', $landingPage) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('promotion-landing-pages.destroy', $landingPage) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this landing page?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No landing pages found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $landingPages->links() }}</div>
    </div>
</div>
@endsection
