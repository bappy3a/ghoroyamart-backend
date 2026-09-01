@extends('layouts.master')

@section('title', 'Create Role')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Create Role</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('roles.store') }}" method="POST">
        @include('admin.roles._form', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
        ])
    </form>
@endsection
