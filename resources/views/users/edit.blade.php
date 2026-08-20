@extends('acl::layouts.app')
@section('title', __('acl::users.manage_access') . ': ' . ($user->{$displayField} ?? "User #{$user->getKey()}") . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::users.manage_access') }}: {{ $user->{$displayField} ?? "User #{$user->getKey()}" }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.users.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::nav.users') }}</a> / {{ __('acl::users.manage_access') }}</div>
    </div>
</div>

<form action="{{ route('acl.users.update', $user->getKey()) }}" method="POST">
    @csrf @method('PUT')

    {{-- User Info Summary --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>👤 {{ __('acl::users.user_info') }}</h3></div>
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
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::users.user_id') }}</label>
                <div style="font-size: 15px; font-family: monospace; color: var(--info); margin-top: 4px;">#{{ $user->getKey() }}</div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::users.super_admin_status') }}</label>
                <div style="margin-top: 4px;">
                    @if(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                        <span class="badge badge-post">{{ __('acl::users.super_admin_yes') }}</span>
                    @else
                        <span class="badge badge-protected">{{ __('acl::users.standard_user') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Assigned Roles --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>👥 {{ __('acl::users.assigned_roles') }}</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $user->roles->count() }} {{ __('acl::common.selected') }}</span>
        </div>
        @php $userRoleIds = $user->roles->pluck('id')->all(); @endphp

        <div class="checkbox-grid">
            @forelse($allRoles as $role)
            <label class="checkbox-item {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                    {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}>
                <div>
                    <div class="cb-label">{{ $role->name }}</div>
                    <div class="cb-slug">{{ $role->slug }} ({{ __('acl::roles.permissions_count', ['count' => $role->permissions->count()]) }})</div>
                </div>
            </label>
            @empty
                <p style="color: var(--text-muted);">{{ __('acl::roles.no_roles_found') }} <a href="{{ route('acl.roles.create') }}" style="color: var(--accent);">{{ __('acl::roles.new_role') }}</a>.</p>
            @endforelse
        </div>
    </div>

    {{-- Direct Permissions --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <div>
                <h3>🔑 {{ __('acl::users.direct_permissions') }}</h3>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    {{ __('acl::users.direct_permissions_help') }}
                </div>
            </div>
            <span style="font-size: 13px; color: var(--text-muted);">{{ $user->permissions->count() }} {{ __('acl::common.direct') }}</span>
        </div>

        @php
            $userDirectPermissionIds = $user->permissions->pluck('id')->all();
            $rolePermissionSlugs = $user->roles->pluck('permissions')->flatten()->pluck('slug')->all();
        @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
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
                                    <span class="badge badge-get" style="font-size: 9px; margin-left: 4px;">{{ __('acl::users.via_role') }}</span>
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
                <p>{{ __('acl::roles.no_permissions_yet') }} <a href="{{ route('acl.permissions.create') }}" style="color: var(--accent);">{{ __('acl::permissions.new_permission') }}</a>.</p>
            </div>
        @endforelse
    </div>

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">{{ __('acl::users.save_access') }}</button>
        <a href="{{ route('acl.users.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
