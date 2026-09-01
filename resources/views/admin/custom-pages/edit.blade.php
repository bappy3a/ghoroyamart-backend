@extends('layouts.master')

@section('title', 'Edit Custom Page')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit Custom Page</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('custom-pages.index') }}">Custom Pages</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('custom-pages.update', $customPage) }}" method="POST" id="edit-custom-page-form">
        @csrf
        @method('PUT')
        @include('admin.custom-pages._form', [
            'customPage' => $customPage,
        ])
    </form>
@endsection

@section('script')
    @include('admin.custom-pages._form-scripts')
@endsection

