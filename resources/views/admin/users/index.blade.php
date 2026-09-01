@extends('layouts.master')

@section('title', 'Users')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h4 class="mb-0">Users</h4>
                    <p class="text-muted mb-0">Manage system users and administrators.</p>
                </div>
                @can('users.create')
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="ri-add-line align-middle me-1"></i>
                        New User
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
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Roles</th>
                            <th>Direct Permissions</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td style="width: 60px;">
                                    @if($user->avatar)
                                        <img
                                            src="{{ api_asset($user->avatar) }}"
                                            alt="{{ $user->name }}"
                                            class="rounded-circle border"
                                            style="height: 48px; width: 48px; object-fit: cover;"
                                        >
                                    @else
                                        <div
                                            class="bg-light border rounded-circle d-flex align-items-center justify-content-center text-muted"
                                            style="height: 48px; width: 48px;"
                                        >
                                            <i class="ri-user-line"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    @if($user->username)
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '—' }}</td>
                                <td>
                                    @if($user->user_type === 'admin')
                                        <span class="badge bg-primary-subtle text-primary">Admin</span>
                                    @else
                                        <span class="badge bg-info-subtle text-info">Customer</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($user->roles as $role)
                                        <span class="badge bg-light text-dark">{{ $role->name }}</span>
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($user->permissions_count > 0)
                                        <span class="badge bg-primary-subtle text-primary">{{ $user->permissions_count }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->status === 'active')
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    @can('users.update')
                                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-warning">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('users.delete')
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Delete this user?');"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    No users yet. <a href="{{ route('users.create') }}">Create the first one</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
