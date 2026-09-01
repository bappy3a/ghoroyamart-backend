@extends('layouts.master')

@section('title', 'Roles')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Roles</h4>
                    <p class="text-muted mb-0">Manage admin roles and route-level permissions.</p>
                </div>
                @can('roles.create')
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">
                        <i class="ri-add-line align-middle me-1"></i>
                        New Role
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Updated</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td>
                                    <strong>{{ $role->name }}</strong>
                                    @if($role->name === 'Super Admin')
                                        <span class="badge bg-primary-subtle text-primary ms-2">Protected</span>
                                    @endif
                                </td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    @can('roles.update')
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('roles.delete')
                                        @if($role->name !== 'Super Admin')
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this role?');"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No roles yet. <a href="{{ route('roles.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $roles->links() }}
            </div>
        </div>
    </div>
@endsection
