@extends('layouts.master')

@section('title', 'Edit Coupon')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit Coupon</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('coupons.index') }}">Coupons</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('coupons.update', $coupon) }}" method="POST" id="couponForm">
        @csrf
        @method('PUT')
        @include('admin.coupons._form', [
            'coupon' => $coupon,
            'products' => $products,
        ])
    </form>
@endsection

@section('script')
    @include('admin.coupons._form-scripts')
@endsection

