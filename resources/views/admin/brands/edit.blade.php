@extends('layouts.master')

@section('title', 'Edit Brand')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit Brand</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('brands.index') }}">Brands</a></li>
                        <li class="breadcrumb-item active">{{ $brand->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('brands.update', $brand) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.brands._form', [
            'brand' => $brand,
        ])
    </form>
@endsection

@section('script')
    @include('admin.brands._form-scripts')
@endsection


