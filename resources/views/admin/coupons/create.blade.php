@extends('layouts.master')

@section('title', 'Create Coupon')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Create Coupon</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('coupons.index') }}">Coupons</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('coupons.store') }}" method="POST" id="couponForm">
        @include('admin.coupons._form', [
            'coupon' => new \App\Models\Coupon(),
            'products' => $products,
        ])
    </form>
@endsection

@section('script')
    @include('admin.coupons._form-scripts')
@endsection

