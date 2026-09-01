@extends('layouts.master')

@section('title', 'Video Promotions')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Video Promotions</h4>
                    <p class="text-muted mb-0">Manage promotional videos for products.</p>
                </div>
                <a href="{{ route('video-promotions.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Video Promotion
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
                            <th>Title</th>
                            <th>Video URL</th>
                            <th>Product</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($videoPromotions as $promotion)
                            <tr>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $promotion->order_number }}</span>
                                </td>
                                <td>
                                    <strong>{{ $promotion->title }}</strong>
                                </td>
                                <td>
                                    <a href="{{ $promotion->video_url }}" target="_blank" class="text-primary">
                                        <i class="ri-external-link-line me-1"></i>
                                        View Video
                                    </a>
                                </td>
                                <td>
                                    @if($promotion->product)
                                        <span class="text-muted">{{ $promotion->product->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($promotion->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $promotion->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('video-promotions.edit', $promotion) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('video-promotions.destroy', $promotion) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this video promotion?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No video promotions yet. <a href="{{ route('video-promotions.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $videoPromotions->links() }}
            </div>
        </div>
    </div>
@endsection
