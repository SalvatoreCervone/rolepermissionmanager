@extends('acl::layouts.app')
@section('title', 'Manage Access: ' . ($user->{$displayField} ?? "User #{$user->getKey()}") . ' — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Manage Access: {{ $user->{$displayField} ?? "User #{$user->getKey()}" }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.users.index') }}" style="color: var(--accent); text-decoration: none;">Users</a> / Manage Access</div>
    </div>
</div>

<form action="{{ route('acl.users.update', $user->getKey()) }}" method="POST">
    @csrf @method('PUT')

    {{-- User Info Summary --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>👤 User Information</h3></div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ ucfirst($displayField) }}</label>
                <div style="font-size: 15px; font-weight: 600; margin-top: 4px;">{{ $user->{$displayField} ?? '—' }}</div>
            </div>
            @if($secondaryField)
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ ucfirst($secondaryField) }}</label>
                <div style="font-size: 15px; color: var(--text-secondary); margin-top: 4px;">{{ $user->{$secondaryField} ?? '—' }}</div>
            </div>
            @endif
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">User ID</label>
                <div style="font-size: 15px; font-family: monospace; color: var(--info); margin-top: 4px;">#{{ $user->getKey() }}</div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Super Admin Status</label>
                <div style="margin-top: 4px;">
                    @if(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                        <span class="badge badge-post">👑 Yes (Bypasses all checks)</span>
                    @else
                        <span class="badge badge-protected">Standard User</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Assigned Roles --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>👥 Assigned Roles</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $user->roles->count() }} selected</span>
        </div>
        @php $userRoleIds = $user->roles->pluck('id')->all(); @endphp

        <div class="checkbox-grid">
            @forelse($allRoles as $role)
            <label class="checkbox-item {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                    {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}>
                <div>
                    <div class="cb-label">{{ $role->name }}</div>
                    <div class="cb-slug">{{ $role->slug }} ({{ $role->permissions->count() }} permissions)</div>
                </div>
            </label>
            @empty
                <p style="color: var(--text-muted);">No roles available. <a href="{{ route('acl.roles.create') }}" style="color: var(--accent);">Create a role first</a>.</p>
            @endforelse
        </div>
    </div>

    {{-- Direct Permissions --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <div>
                <h3>🔑 Direct Permissions</h3>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    Grant individual permissions directly to this user (in addition to permissions inherited from their roles).
                </div>
            </div>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $user->permissions->count() }} direct</span>
        </div>

        @php
            $userDirectPermissionIds = $user->permissions->pluck('id')->all();
            $rolePermissionSlugs = $user->roles->pluck('permissions')->flatten()->pluck('slug')->all();
        @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: 'Uncategorized' }}</h4>
                <div class="checkbox-grid">
                    @foreach($permissions as $permission)
                    @php $grantedViaRole = in_array($permission->slug, $rolePermissionSlugs); @endphp
                    <label class="checkbox-item {{ in_array($permission->id, $userDirectPermissionIds) ? 'checked' : '' }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                            {{ in_array($permission->id, $userDirectPermissionIds) ? 'checked' : '' }}>
                        <div>
                            <div class="cb-label">
                                {{ $permission->name }}
                                @if($grantedViaRole)
                                    <span class="badge badge-get" style="font-size: 9px; margin-left: 4px;">via role</span>
                                @endif
                            </div>
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
        <button type="submit" class="btn btn-primary">Save Access Settings</button>
        <a href="{{ route('acl.users.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
