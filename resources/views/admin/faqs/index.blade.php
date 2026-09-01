@extends('layouts.master')

@section('title', 'FAQs')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">FAQs</h4>
                    <p class="text-muted mb-0">Manage the questions and answers shown on the FAQ page.</p>
                </div>
                <a href="{{ route('faqs.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New FAQ
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
                            <th>Order</th>
                            <th>Question</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faqs as $faq)
                            <tr>
                                <td><span class="badge bg-secondary-subtle text-secondary">{{ $faq->sort_order }}</span></td>
                                <td>
                                    <strong class="d-block">{{ $faq->question }}</strong>
                                    <span class="text-muted small d-block" style="max-width: 600px;">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($faq->answer), 120) }}
                                    </span>
                                </td>
                                <td>
                                    @if($faq->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $faq->created_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('faqs.edit', $faq) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('faqs.destroy', $faq) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this FAQ?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No FAQs yet. <a href="{{ route('faqs.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $faqs->links() }}
            </div>
        </div>
    </div>
@endsection
