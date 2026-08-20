@extends('acl::layouts.app')
@section('title', __('acl::routes.configure_title') . ': ' . $resource->identifier . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::routes.configure_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.routes.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::routes.title') }}</a> / {{ $resource->identifier }}</div>
    </div>
</div>

<form action="{{ route('acl.routes.update', $resource->id) }}" method="POST">
    @csrf @method('PUT')

    {{-- Route Info (read-only) --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>🛤️ {{ __('acl::routes.route_info') }}</h3></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.identifier') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->identifier }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.method') }}</label>
                <div style="margin-top: 4px;"><span class="badge badge-{{ strtolower($resource->method ?? 'get') }}">{{ $resource->method }}</span></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.uri') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->uri }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.controller_action') }}</label>
                <div style="margin-top: 4px;"><code style="font-size: 12px;">{{ $resource->controller_action }}</code></div>
            </div>
        </div>
    </div>

    {{-- Access Settings --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>⚙️ {{ __('acl::routes.access_settings') }}</h3></div>

        <div class="form-group">
            <label>{{ __('acl::routes.public_access') }}</label>
            <div class="toggle-container">
                <input type="hidden" name="is_public" value="{{ old('is_public', $resource->is_public) ? '1' : '0' }}">
                <div class="toggle {{ old('is_public', $resource->is_public) ? 'active' : '' }}"></div>
                <span class="toggle-label">
                    {{ $resource->is_public ? __('acl::routes.public_help') : __('acl::routes.protected_help') }}
                </span>
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('acl::routes.super_admin_only') }}</label>
            <div class="toggle-container">
                <input type="hidden" name="is_super_admin_only" value="{{ old('is_super_admin_only', $resource->is_super_admin_only) ? '1' : '0' }}">
                <div class="toggle {{ old('is_super_admin_only', $resource->is_super_admin_only) ? 'active' : '' }}"></div>
                <span class="toggle-label">
                    {{ __('acl::routes.super_admin_only_help') }}
                </span>
            </div>
        </div>

        <div class="form-group">
            <label for="operator">{{ __('acl::routes.operator_label') }}</label>
            <select id="operator" name="operator" class="form-control" style="max-width: 350px;">
                <option value="OR" {{ old('operator', $resource->operator) === 'OR' ? 'selected' : '' }}>
                    {{ __('acl::routes.operator_or') }}
                </option>
                <option value="AND" {{ old('operator', $resource->operator) === 'AND' ? 'selected' : '' }}>
                    {{ __('acl::routes.operator_and') }}
                </option>
            </select>
        </div>
    </div>

    {{-- Permission Assignment --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>🔑 {{ __('acl::routes.required_permissions') }}</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $resource->permissions->count() }} {{ __('acl::common.selected') }}</span>
        </div>

        @php $resourcePermissionIds = $resource->permissions->pluck('id')->all(); @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
                <div class="checkbox-grid">
                    @foreach($permissions as $permission)
                    <label class="checkbox-item {{ in_array($permission->id, $resourcePermissionIds) ? 'checked' : '' }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                            {{ in_array($permission->id, $resourcePermissionIds) ? 'checked' : '' }}>
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
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save_config') }}</button>
        <a href="{{ route('acl.routes.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
