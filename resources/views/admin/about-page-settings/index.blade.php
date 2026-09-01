@extends('layouts.master')

@section('title', 'About Page Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">About Page Settings</h4>
                <p class="text-muted mb-0">Manage the dynamic content shown on the About Us page.</p>
            </div>
            @if($aboutPageSetting)
                <a href="{{ route('about-page-settings.edit', $aboutPageSetting) }}" class="btn btn-primary">Edit Settings</a>
            @else
                <a href="{{ route('about-page-settings.create') }}" class="btn btn-primary">Create Settings</a>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($aboutPageSetting)
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <h5 class="mb-3">{{ $aboutPageSetting->page_title }}</h5>
                        <p class="mb-1"><strong>Breadcrumb:</strong> {{ $aboutPageSetting->breadcrumb_title }}</p>
                        <p class="mb-0 text-muted">{{ $aboutPageSetting->breadcrumb_subtitle ?: 'No subtitle set' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100">
                        <p class="mb-1"><strong>Cover image:</strong></p>
                        @if($aboutPageSetting->cover_image)
                            <img src="{{ api_asset($aboutPageSetting->cover_image) }}" class="img-fluid rounded" style="max-height:180px;" alt="Cover image">
                        @else
                            <span class="text-muted">No cover image</span>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-5 text-muted">
                No About page settings found.
            </div>
        @endif
    </div>
</div>
@endsection
