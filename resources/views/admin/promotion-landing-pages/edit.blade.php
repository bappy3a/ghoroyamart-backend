@extends('layouts.master')

@section('title', 'Edit Promotion Landing Page')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Edit Promotion Landing Page</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('promotion-landing-pages.index') }}">Promotion Landing Pages</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('promotion-landing-pages.update', $landingPage) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.promotion-landing-pages._form', ['landingPage' => $landingPage, 'products' => $products])
</form>
@endsection
