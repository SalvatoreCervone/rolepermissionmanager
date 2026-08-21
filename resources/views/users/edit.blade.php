@extends('acl::layouts.app')
@php
    $userName = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($user, $displayField);
    $userSub = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($user, $secondaryField);
@endphp
@section('title', __('acl::users.edit_access_title', ['name' => $userName]) . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::users.edit_access_title', ['name' => $userName]) }}</h2>
        <div class="breadcrumb">
            <a href="{{ route('acl.users.index', isset($modelKey) ? ['model' => $modelKey] : []) }}" style="color: var(--accent); text-decoration: none;">
                {{ $modelConfig['label'] ?? __('acl::users.title') }}
            </a> / {{ __('acl::common.edit') }}
        </div>
    </div>
</div>

<form action="{{ route('acl.users.update', ['id' => $user->getKey(), 'model' => $modelKey ?? 'users']) }}" method="POST">
    @csrf @method('PUT')

    {{-- User Summary Card --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>👤 {{ __('acl::users.user_info') }}</h3>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldHeader($displayField) }}</label>
                <div style="font-size: 16px; font-weight: 600; margin-top: 4px;">{{ $userName }}</div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldHeader($secondaryField) }}</label>
                <div style="font-size: 16px; margin-top: 4px;"><code>{{ $userSub ?: '—' }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::users.status') }}</label>
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
        @php $userRoleIds = old('roles', $user->roles->pluck('id')->all()); @endphp

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
    @php
        $rolePermissionSlugs = $user->roles->pluck('permissions')->flatten()->pluck('slug')->all();
    @endphp
    @include('acl::partials.permission-picker', [
        'allPermissions'      => $allPermissions,
        'selectedPermissions' => old('permissions', $user->permissions->pluck('id')->all()),
        'title'               => __('acl::users.direct_permissions'),
        'helpText'            => __('acl::users.direct_permissions_help'),
        'countLabel'          => __('acl::common.direct'),
        'grantedRoleSlugs'    => $rolePermissionSlugs,
    ])

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">{{ __('acl::users.save_access') }}</button>
        <a href="{{ route('acl.users.index', isset($modelKey) ? ['model' => $modelKey] : []) }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
