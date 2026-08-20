@extends('acl::layouts.app')
@section('title', 'Edit Role: ' . $role->name . ' — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Edit Role: {{ $role->name }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.roles.index') }}" style="color: var(--accent); text-decoration: none;">Roles</a> / Edit</div>
    </div>
</div>

<form action="{{ route('acl.roles.update', $role->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>Role Details</h3></div>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $role->slug) }}" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control">{{ old('description', $role->description) }}</textarea>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>🔑 Assign Permissions</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $role->permissions->count() }} selected</span>
        </div>
        @php $rolePermissionIds = $role->permissions->pluck('id')->all(); @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: 'Uncategorized' }}</h4>
                <div class="checkbox-grid">
                    @foreach($permissions as $permission)
                    <label class="checkbox-item {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                            {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                        <div>
                            <div class="cb-label">{{ $permission->name }}</div>
                            <div class="cb-slug">{{ $permission->slug }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="empty-state">
                <p>No permissions available. <a href="{{ route('acl.permissions.create') }}" style="color: var(--accent);">Create one first</a>.</p>
            </div>
        @endforelse
    </div>

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="{{ route('acl.roles.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
