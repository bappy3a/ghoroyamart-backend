@extends('layouts.master')

@section('title', 'Edit FAQ')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Edit FAQ</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('faqs.index') }}">FAQs</a></li>
                        <li class="breadcrumb-item active">{{ $faq->question }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('faqs.update', $faq) }}" method="POST">
        @method('PUT')
        @include('admin.faqs._form', ['faq' => $faq])
    </form>
@endsection
