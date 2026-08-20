@extends('acl::layouts.app')
@section('title', 'Configure Resource: ' . $resource->identifier . ' — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Configure Resource</h2>
        <div class="breadcrumb"><a href="{{ route('acl.resources.index') }}" style="color: var(--accent); text-decoration: none;">Resources</a> / {{ $resource->identifier }}</div>
    </div>
</div>

<form action="{{ route('acl.resources.update', $resource->id) }}" method="POST">
    @csrf @method('PUT')

    {{-- Resource Info (read-only) --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>🛤️ Route Information</h3></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Identifier</label>
                <div style="margin-top: 4px;"><code>{{ $resource->identifier }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">HTTP Method</label>
                <div style="margin-top: 4px;"><span class="badge badge-{{ strtolower($resource->method) }}">{{ $resource->method }}</span></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">URI</label>
                <div style="margin-top: 4px;"><code>{{ $resource->uri }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Controller Action</label>
                <div style="margin-top: 4px;"><code style="font-size: 12px;">{{ $resource->controller_action }}</code></div>
            </div>
        </div>
    </div>

    {{-- Access Settings --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>⚙️ Access Settings</h3></div>

        <div class="form-group">
            <label>Public Access</label>
            <div class="toggle-container">
                <input type="hidden" name="is_public" value="{{ old('is_public', $resource->is_public) ? '1' : '0' }}">
                <div class="toggle {{ old('is_public', $resource->is_public) ? 'active' : '' }}"></div>
                <span class="toggle-label">
                    {{ $resource->is_public ? 'This route is PUBLIC (no authentication required)' : 'This route is PROTECTED (requires authentication + permissions)' }}
                </span>
            </div>
        </div>

        <div class="form-group">
            <label for="operator">Permission Operator</label>
            <select id="operator" name="operator" class="form-control" style="max-width: 300px;">
                <option value="OR" {{ old('operator', $resource->operator) === 'OR' ? 'selected' : '' }}>
                    OR — User needs at least ONE of the permissions
                </option>
                <option value="AND" {{ old('operator', $resource->operator) === 'AND' ? 'selected' : '' }}>
                    AND — User needs ALL of the permissions
                </option>
            </select>
        </div>
    </div>

    {{-- Permission Assignment --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>🔑 Required Permissions</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $resource->permissions->count() }} selected</span>
        </div>

        @php $resourcePermissionIds = $resource->permissions->pluck('id')->all(); @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: 'Uncategorized' }}</h4>
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
                <p>No permissions available. <a href="{{ route('acl.permissions.create') }}" style="color: var(--accent);">Create one first</a>.</p>
            </div>
        @endforelse
    </div>

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">Save Configuration</button>
        <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
