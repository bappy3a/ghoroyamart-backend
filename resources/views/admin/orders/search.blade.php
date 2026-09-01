@extends('layouts.master')
@section('title', 'Search Order')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-7 col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Search Order</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('orders.search') }}">
                    <label for="order_number" class="form-label">Order Number</label>
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control @error('order_number') is-invalid @enderror"
                            id="order_number"
                            name="order_number"
                            value="{{ old('order_number', $searchedOrderNumber ?? request('order_number')) }}"
                            placeholder="Enter exact order number"
                            required
                            autofocus
                        >
                        <button class="btn btn-primary" type="submit">
                            <i class="ri-search-line align-middle me-1"></i> Search
                        </button>
                    </div>
                    @error('order_number')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </form>

                @if($orderNotFound ?? false)
                    <div class="alert alert-danger mt-4 mb-0" role="alert">
                        Order not found.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
