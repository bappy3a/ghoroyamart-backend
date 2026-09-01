@php
    $isEdit = $role?->exists;
    $isSuperAdmin = $role->name === 'Super Admin';
    $submitLabel = $isEdit ? 'Update Role' : 'Create Role';
    $selectedPermissions = collect(old('permissions', $role->permissions?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
@endphp

@csrf

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Role Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="form-control form-control-lg @error('name') is-invalid @enderror"
                        id="name"
                        name="name"
                        value="{{ old('name', $role->name ?? '') }}"
                        placeholder="e.g. Store Manager"
                        @readonly($isSuperAdmin)
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if($isSuperAdmin)
                        <div class="form-text">Super Admin keeps all permissions and cannot be renamed.</div>
                    @endif
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('roles.index') }}" class="btn btn-light">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ $submitLabel }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Permissions</h5>
                    <p class="text-muted mb-0">Select which backend routes this role can access.</p>
                </div>
                <span class="badge bg-light text-dark">{{ $permissionGroups->flatten()->count() }} routes</span>
            </div>
            <div class="card-body">
                @error('permissions')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror

                @forelse($permissionGroups as $group => $permissions)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">{{ $group }}</h6>
                            <span class="text-muted small">{{ $permissions->count() }} permissions</span>
                        </div>
                        <div class="row g-2">
                            @foreach($permissions as $permission)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="permissions[]"
                                            id="permission-{{ $permission->id }}"
                                            value="{{ $permission->id }}"
                                            @checked($isSuperAdmin || $selectedPermissions->contains($permission->id))
                                            @disabled($isSuperAdmin)
                                        >
                                        <label class="form-check-label" for="permission-{{ $permission->id }}">
                                            {{ $permission->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">
                        No permissions found. Run the permission seeder after routes are loaded.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
