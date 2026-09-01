@php
    $isEdit = $user?->exists;
    $submitLabel = $isEdit ? 'Update User' : 'Create User';
    $selectedRoles = collect(old('roles', $user->roles?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
    $selectedPermissions = collect(old('permissions', $user->permissions?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id);
@endphp

@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1">{{ $isEdit ? 'User Details' : 'Create User' }}</h5>
                    <p class="text-muted mb-0">Manage user information and account settings.</p>
                </div>
                @if($isEdit && $user->updated_at)
                    <span class="text-muted small">Updated {{ $user->updated_at->diffForHumans() }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name', $user->name ?? '') }}"
                            placeholder="e.g. John Doe"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            class="form-control @error('username') is-invalid @enderror"
                            id="username"
                            name="username"
                            value="{{ old('username', $user->username ?? '') }}"
                            placeholder="e.g. johndoe"
                        >
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            id="email"
                            name="email"
                            value="{{ old('email', $user->email ?? '') }}"
                            placeholder="e.g. john@example.com"
                            required
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input
                            type="text"
                            class="form-control @error('phone') is-invalid @enderror"
                            id="phone"
                            name="phone"
                            value="{{ old('phone', $user->phone ?? '') }}"
                            placeholder="e.g. +1234567890"
                        >
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            Password @if(!$isEdit)<span class="text-danger">*</span>@endif
                        </label>
                        <input
                            type="password"
                            class="form-control @error('password') is-invalid @enderror"
                            id="password"
                            name="password"
                            placeholder="Min 8 characters"
                            {{ !$isEdit ? 'required' : '' }}
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($isEdit)
                            <div class="form-text">Leave blank to keep current password.</div>
                        @endif
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">
                            Confirm Password @if(!$isEdit)<span class="text-danger">*</span>@endif
                        </label>
                        <input
                            type="password"
                            class="form-control @error('password_confirmation') is-invalid @enderror"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Re-enter password"
                            {{ !$isEdit ? 'required' : '' }}
                        >
                        @error('password_confirmation')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select
                            class="form-select @error('gender') is-invalid @enderror"
                            id="gender"
                            name="gender"
                        >
                            <option value="">Select Gender</option>
                            <option value="male" @selected(old('gender', $user->gender ?? '') === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $user->gender ?? '') === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $user->gender ?? '') === 'other')>Other</option>
                        </select>
                        @error('gender')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input
                            type="date"
                            class="form-control @error('date_of_birth') is-invalid @enderror"
                            id="date_of_birth"
                            name="date_of_birth"
                            value="{{ old('date_of_birth', $user->date_of_birth ?? '') }}"
                        >
                        @error('date_of_birth')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-0">
                    <label for="bio" class="form-label">Bio</label>
                    <textarea
                        class="form-control @error('bio') is-invalid @enderror"
                        id="bio"
                        name="bio"
                        rows="4"
                        placeholder="Tell us about yourself..."
                    >{{ old('bio', $user->bio ?? '') }}</textarea>
                    @error('bio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Account Settings</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="user_type" class="form-label">User Type <span class="text-danger">*</span></label>
                    <select class="form-select @error('user_type') is-invalid @enderror" id="user_type" name="user_type" required>
                        <option value="">Select Type</option>
                        <option value="admin" @selected(old('user_type', $user->user_type ?? '') === 'admin')>Admin</option>
                        <option value="staff" @selected(old('user_type', $user->user_type ?? 'staff') === 'staff')>Staff</option>
                        <option value="customer" @selected(old('user_type', $user->user_type ?? '') === 'customer')>Customer</option>
                    </select>
                    @error('user_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="user_type" name="status" >
                        <option selected value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $user->status ?? '') === 'inactive')>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Roles</h5>
            </div>
            <div class="card-body">
                @forelse($roles as $role)
                    <div class="form-check mb-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="roles[]"
                            id="role-{{ $role->id }}"
                            value="{{ $role->id }}"
                            @checked($selectedRoles->contains($role->id))
                        >
                        <label class="form-check-label" for="role-{{ $role->id }}">
                            {{ $role->name }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted mb-0">No roles found. Create roles before assigning admin access.</p>
                @endforelse
                @error('roles')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
                @error('roles.*')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Profile Picture</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="avatar" class="form-label">Upload Avatar</label>
                    <input
                        type="file"
                        class="form-control @error('avatar') is-invalid @enderror"
                        id="avatar"
                        name="avatar"
                        accept="image/*"
                    >
                    @error('avatar')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="avatar-preview-placeholder"
                        class="{{ $user->avatar ? 'd-none' : '' }} text-muted small"
                    >
                        No avatar selected
                    </div>
                    <img
                        src="{{ $user->avatar ? api_asset($user->avatar) : '' }}"
                        alt="Avatar preview"
                        id="avatar-preview-img"
                        class="img-thumbnail rounded-circle {{ $user->avatar ? '' : 'd-none' }}"
                        style="max-height: 100px; max-width: 100px; object-fit: cover;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h5 class="mb-0">Cover Photo</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="cover_photo" class="form-label">Upload Cover Photo</label>
                    <input
                        type="file"
                        class="form-control @error('cover_photo') is-invalid @enderror"
                        id="cover_photo"
                        name="cover_photo"
                        accept="image/*"
                    >
                    @error('cover_photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div
                        id="cover-preview-placeholder"
                        class="{{ $user->cover_photo ? 'd-none' : '' }} text-muted small"
                    >
                        No cover photo selected
                    </div>
                    <img
                        src="{{ $user->cover_photo ? api_asset($user->cover_photo) : '' }}"
                        alt="Cover preview"
                        id="cover-preview-img"
                        class="img-thumbnail {{ $user->cover_photo ? '' : 'd-none' }}"
                        style="max-height: 120px; width: 100%; object-fit: cover;"
                    >
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-light-subtle">
            <div class="card-body">
                <h6 class="fw-semibold mb-3">Quick tips</h6>
                <ul class="list-unstyled text-muted mb-0">
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Use strong passwords (8+ characters).</li>
                    <li class="mb-2"><i class="ri-check-line text-success me-1"></i>Admin users have full system access.</li>
                    <li><i class="ri-check-line text-success me-1"></i>Inactive users cannot log in.</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Direct Permissions</h5>
                    <p class="text-muted mb-0">Give this user extra backend access without changing their role.</p>
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
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="permissions[]"
                                            id="user-permission-{{ $permission->id }}"
                                            value="{{ $permission->id }}"
                                            @checked($selectedPermissions->contains($permission->id))
                                        >
                                        <label class="form-check-label" for="user-permission-{{ $permission->id }}">
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

                @error('permissions.*')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div> --}}

    <div class="col-12 mb-3">
        <div class="d-flex justify-content-between flex-wrap gap-3 align-items-center">
            <div>
                <div class="fw-medium">Need a reminder?</div>
                <p class="text-muted mb-0 small">You can edit user information at any time.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('users.index') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    {{ $submitLabel }}
                </button>
            </div>
        </div>
    </div>
</div>
