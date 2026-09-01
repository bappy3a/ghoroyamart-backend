@extends('layouts.master')

@section('title', 'Edit Flash Deal')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit Flash Deal</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('flash-deals.index') }}">Flash Deals</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('flash-deals.update', $flashDeal) }}" method="POST" enctype="multipart/form-data" id="flashDealForm">
        @method('PUT')
        @include('admin.flash-deals._form', [
            'flashDeal' => $flashDeal,
            'products' => $products,
        ])
    </form>
@endsection

@section('script')
    @include('admin.flash-deals._form-scripts')
@endsection

