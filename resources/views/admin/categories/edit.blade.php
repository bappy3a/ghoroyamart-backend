@extends('layouts.master')

@section('title', 'Edit Category')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit Category</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
                        <li class="breadcrumb-item active">{{ $category->name }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('categories.update', $category) }}" method="POST" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.categories._form', [
            'category' => $category,
            'parentOptions' => $parentOptions,
        ])
    </form>
@endsection

@section('script')
    @include('admin.categories._form-scripts')
@endsection

