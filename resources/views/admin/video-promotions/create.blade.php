@extends('layouts.master')

@section('title', 'Create Video Promotion')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Create Video Promotion</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('video-promotions.index') }}">Video Promotions</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('video-promotions.store') }}" method="POST">
        @include('admin.video-promotions._form', [
            'videoPromotion' => $videoPromotion,
            'products' => $products,
        ])
    </form>
@endsection

@section('script')
    @include('admin.video-promotions._form-scripts')
@endsection
