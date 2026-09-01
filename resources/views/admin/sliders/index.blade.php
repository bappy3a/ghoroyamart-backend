@extends('layouts.master')

@section('title', 'Sliders')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Homepage sliders</h4>
                    <p class="text-muted mb-0">Manage hero banners, button links, and display order.</p>
                </div>
                <a href="{{ route('sliders.create') }}" class="btn btn-primary">
                    <i class="ri-add-line align-middle me-1"></i>
                    New Slide
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
                            <th>Text</th>
                            <th>Button</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sliders as $slider)
                            <tr>
                                <td style="width: 90px;">
                                    @if($slider->image)
                                        <img
                                            src="{{ api_asset($slider->image) }}"
                                            alt="Slider image"
                                            class="rounded border"
                                            style="height: 60px; width: 100px; object-fit: cover;"
                                        >
                                    @else
                                        <div
                                            class="bg-light border rounded d-flex align-items-center justify-content-center text-muted"
                                            style="height: 60px; width: 100px;"
                                        >
                                            <i class="ri-image-line"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($slider->subtitle)
                                        <span class="badge bg-secondary-subtle text-secondary mb-1">
                                            {{ $slider->subtitle }}
                                        </span>
                                    @endif
                                    <strong class="d-block text-truncate" style="max-width: 280px;">
                                        {{ $slider->title ?: $slider->text }}
                                    </strong>
                                    @if($slider->description)
                                        <span class="text-muted small d-block text-truncate" style="max-width: 280px;">
                                            {{ $slider->description }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($slider->button_text)
                                        <span class="badge bg-primary-subtle text-primary">
                                            {{ $slider->button_text }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($slider->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $slider->sort_order }}</td>
                                <td>{{ $slider->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('sliders.edit', $slider) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>
                                    <form action="{{ route('sliders.destroy', $slider) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this slide?');"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No slides yet. <a href="{{ route('sliders.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $sliders->links() }}
            </div>
        </div>
    </div>
@endsection


