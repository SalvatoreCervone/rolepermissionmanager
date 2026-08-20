@extends('acl::layouts.app')
@section('title', __('acl::resources.configure_title') . ': ' . $resource->identifier . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::resources.configure_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.resources.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::resources.title') }}</a> / {{ $resource->identifier }}</div>
    </div>
</div>

<form action="{{ route('acl.resources.update', $resource->id) }}" method="POST">
    @csrf @method('PUT')

    {{-- Resource Info (read-only) --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>🛤️ {{ __('acl::resources.route_info') }}</h3></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::resources.identifier') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->identifier }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::resources.method') }}</label>
                <div style="margin-top: 4px;"><span class="badge badge-{{ strtolower($resource->method) }}">{{ $resource->method }}</span></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::resources.uri') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->uri }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::resources.controller_action') }}</label>
                <div style="margin-top: 4px;"><code style="font-size: 12px;">{{ $resource->controller_action }}</code></div>
            </div>
        </div>
    </div>

    {{-- Access Settings --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>⚙️ {{ __('acl::resources.access_settings') }}</h3></div>

        <div class="form-group">
            <label>{{ __('acl::resources.public_access') }}</label>
            <div class="toggle-container">
                <input type="hidden" name="is_public" value="{{ old('is_public', $resource->is_public) ? '1' : '0' }}">
                <div class="toggle {{ old('is_public', $resource->is_public) ? 'active' : '' }}"></div>
                <span class="toggle-label">
                    {{ $resource->is_public ? __('acl::resources.public_help') : __('acl::resources.protected_help') }}
                </span>
            </div>
        </div>

        <div class="form-group">
            <label for="operator">{{ __('acl::resources.operator_label') }}</label>
            <select id="operator" name="operator" class="form-control" style="max-width: 350px;">
                <option value="OR" {{ old('operator', $resource->operator) === 'OR' ? 'selected' : '' }}>
                    {{ __('acl::resources.operator_or') }}
                </option>
                <option value="AND" {{ old('operator', $resource->operator) === 'AND' ? 'selected' : '' }}>
                    {{ __('acl::resources.operator_and') }}
                </option>
            </select>
        </div>
    </div>

    {{-- Permission Assignment --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>🔑 {{ __('acl::resources.required_permissions') }}</h3>
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
        <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
