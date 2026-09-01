@extends('layouts.master')

@section('title', 'Create User')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Create User</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data">
        @include('admin.users._form', [
            'user' => $user,
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
        ])
    </form>
@endsection

@section('script')
    @include('admin.users._form-scripts')
@endsection
