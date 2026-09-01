@extends('layouts.master')
@section('title', 'Edit About Settings')
@section('content')
<form action="{{ route('about-page-settings.update', $aboutPageSetting) }}" method="POST" enctype="multipart/form-data">
    @method('PUT')
    @include('admin.about-page-settings._form', ['aboutPageSetting' => $aboutPageSetting])
</form>
@endsection
