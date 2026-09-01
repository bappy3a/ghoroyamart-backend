@extends('layouts.master')

@section('title', 'Permissions')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Permissions</h4>
                    <p class="text-muted mb-0">Backend route permissions generated from named admin routes.</p>
                </div>
                @can('roles.show')
                    <a href="{{ route('roles.index') }}" class="btn btn-light">
                        <i class="ri-shield-user-line align-middle me-1"></i>
                        Roles
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($permissionGroups as $group => $permissions)
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $group }}</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $permissions->count() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Permission</th>
                                        <th class="text-end">Roles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissions as $permission)
                                        <tr>
                                            <td><code>{{ $permission->name }}</code></td>
                                            <td class="text-end">{{ $permission->roles_count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center text-muted py-4">
                        No permissions found. Run the permission seeder after routes are loaded.
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
