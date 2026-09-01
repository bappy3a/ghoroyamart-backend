@extends('layouts.master')

@section('title', 'Create Slide')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Create slide</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('sliders.index') }}">Sliders</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('sliders.store') }}" method="POST" enctype="multipart/form-data">
        @include('admin.sliders._form', [
            'slider' => $slider,
        ])
    </form>
@endsection

@section('script')
    @include('admin.sliders._form-scripts')
@endsection


