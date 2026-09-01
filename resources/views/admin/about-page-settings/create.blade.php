@extends('layouts.master')
@section('title', 'Create About Settings')
@section('content')
<form action="{{ route('about-page-settings.store') }}" method="POST" enctype="multipart/form-data">
    @include('admin.about-page-settings._form', ['aboutPageSetting' => $aboutPageSetting])
</form>
@endsection
