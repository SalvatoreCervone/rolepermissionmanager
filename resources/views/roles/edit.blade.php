@extends('acl::layouts.app')
@section('title', __('acl::roles.edit_title', ['name' => $role->name]) . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::roles.edit_title', ['name' => $role->name]) }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.roles.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::roles.title') }}</a> / {{ __('acl::common.edit') }}</div>
    </div>
</div>

<form action="{{ route('acl.roles.update', $role->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>{{ __('acl::roles.role_details') }}</h3></div>
        <div class="form-group">
            <label for="name">{{ __('acl::roles.name') }}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">{{ __('acl::roles.slug') }}</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $role->slug) }}" required>
        </div>
        <div class="form-group">
            <label for="description">{{ __('acl::roles.description') }}</label>
            <textarea id="description" name="description" class="form-control">{{ old('description', $role->description) }}</textarea>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>🔑 {{ __('acl::roles.assign_permissions') }}</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $role->permissions->count() }} {{ __('acl::common.selected') }}</span>
        </div>
        @php $rolePermissionIds = $role->permissions->pluck('id')->all(); @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
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
                <p>{{ __('acl::roles.no_permissions_yet') }} <a href="{{ route('acl.permissions.create') }}" style="color: var(--accent);">{{ __('acl::permissions.new_permission') }}</a>.</p>
            </div>
        @endforelse
    </div>

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save') }}</button>
        <a href="{{ route('acl.roles.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
