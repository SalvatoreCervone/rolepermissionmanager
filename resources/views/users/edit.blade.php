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

@php
    $isDeactivated = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::isUserDeactivated($user);
@endphp

    {{-- User Summary Card --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3>👤 {{ __('acl::users.user_info') }}</h3>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="openResetPasswordModal('{{ $user->getKey() }}', '{{ addslashes($userName) }}')">
                    🔑 {{ __('acl::users.reset_password') }}
                </button>
                @if($isDeactivated)
                    <button type="button" class="btn btn-secondary btn-sm" style="color: #22c55e; border-color: rgba(34, 197, 94, 0.4);" onclick="openActivateModal('{{ $user->getKey() }}', '{{ addslashes($userName) }}')">
                        ✅ {{ __('acl::users.activate') }}
                    </button>
                @else
                    <button type="button" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="openDeactivateModal('{{ $user->getKey() }}', '{{ addslashes($userName) }}')">
                        🚫 {{ __('acl::users.deactivate') }}
                    </button>
                @endif
            </div>
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
                <div style="margin-top: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    @if($isDeactivated)
                        <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                            🚫 {{ __('acl::users.status_deactivated') }}
                        </span>
                    @else
                        <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">
                            ✅ {{ __('acl::users.status_active') }}
                        </span>
                    @endif
                    @if(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                        <span class="badge badge-post">👑 Super Admin</span>
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

{{-- Reset Password Modal --}}
<div id="resetPasswordModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; width: 100%; max-width: 480px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;">🔑 {{ __('acl::users.reset_password') }}</h3>
            <button type="button" onclick="closeResetPasswordModal()" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">✕</button>
        </div>
        <form id="resetPasswordForm" method="POST" action="" style="padding: 20px;">
            @csrf
            <p style="margin-top: 0; margin-bottom: 16px; font-size: 13px; color: var(--text-secondary);">
                {{ __('acl::users.reset_password') }} per <strong id="resetPasswordUserName" style="color: var(--text-primary);"></strong>
            </p>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500;">{{ __('acl::users.new_password') }}</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="password" id="resetPasswordInput" class="form-control" placeholder="{{ __('acl::users.password_placeholder') }}" required minlength="6" style="flex: 1;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="generateRandomPassword('resetPasswordInput', 'resetPasswordConfirmInput')">
                        🎲 {{ __('acl::users.generate_password') }}
                    </button>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500;">{{ __('acl::users.confirm_password') }}</label>
                <input type="text" name="password_confirmation" id="resetPasswordConfirmInput" class="form-control" placeholder="{{ __('acl::users.password_confirmation_placeholder') }}" required minlength="6">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeResetPasswordModal()">{{ __('acl::common.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">💾 {{ __('acl::common.save') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Deactivate User Modal --}}
<div id="deactivateModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; width: 100%; max-width: 480px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; color: #ef4444;">🚫 {{ __('acl::users.deactivate') }}</h3>
            <button type="button" onclick="closeDeactivateModal()" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">✕</button>
        </div>
        <form id="deactivateForm" method="POST" action="" style="padding: 20px;">
            @csrf
            <p style="margin-top: 0; font-size: 14px; font-weight: 600; color: var(--text-primary);">
                {{ __('acl::users.deactivate_confirm') }}
            </p>
            <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
                {{ __('acl::users.deactivate_help') }}
            </p>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px;">
                Utente: <strong id="deactivateUserName" style="color: var(--text-primary);"></strong>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeDeactivateModal()">{{ __('acl::common.cancel') }}</button>
                <button type="submit" class="btn btn-sm" style="background: #ef4444; color: #fff; border: none;">🚫 {{ __('acl::users.deactivate') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Activate User Modal --}}
<div id="activateModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; width: 100%; max-width: 480px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; color: #22c55e;">✅ {{ __('acl::users.activate') }}</h3>
            <button type="button" onclick="closeActivateModal()" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">✕</button>
        </div>
        <form id="activateForm" method="POST" action="" style="padding: 20px;">
            @csrf
            <p style="margin-top: 0; font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
                Riattiva l'accesso per <strong id="activateUserName" style="color: var(--text-primary);"></strong>. Puoi facoltativamente impostare una nuova password.
            </p>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500;">{{ __('acl::users.new_password') }} (opzionale)</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="password" id="activatePasswordInput" class="form-control" placeholder="{{ __('acl::users.password_placeholder') }}" minlength="6" style="flex: 1;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="generateRandomPassword('activatePasswordInput', 'activatePasswordConfirmInput')">
                        🎲 {{ __('acl::users.generate_password') }}
                    </button>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500;">{{ __('acl::users.confirm_password') }}</label>
                <input type="text" name="password_confirmation" id="activatePasswordConfirmInput" class="form-control" placeholder="{{ __('acl::users.password_confirmation_placeholder') }}" minlength="6">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeActivateModal()">{{ __('acl::common.cancel') }}</button>
                <button type="submit" class="btn btn-sm" style="background: #22c55e; color: #fff; border: none;">✅ {{ __('acl::users.activate') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modelParam = '{{ $modelKey ?? '' }}';

    function openResetPasswordModal(id, name) {
        document.getElementById('resetPasswordUserName').textContent = name;
        document.getElementById('resetPasswordInput').value = '';
        const confirmInput = document.getElementById('resetPasswordConfirmInput');
        if (confirmInput) confirmInput.value = '';
        let url = '{{ route('acl.users.reset_password', ['id' => ':id']) }}'.replace(':id', id);
        if (modelParam) url += '?model=' + encodeURIComponent(modelParam);
        document.getElementById('resetPasswordForm').action = url;
        document.getElementById('resetPasswordModal').style.display = 'flex';
    }

    function closeResetPasswordModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }

    function openDeactivateModal(id, name) {
        document.getElementById('deactivateUserName').textContent = name;
        let url = '{{ route('acl.users.deactivate', ['id' => ':id']) }}'.replace(':id', id);
        if (modelParam) url += '?model=' + encodeURIComponent(modelParam);
        document.getElementById('deactivateForm').action = url;
        document.getElementById('deactivateModal').style.display = 'flex';
    }

    function closeDeactivateModal() {
        document.getElementById('deactivateModal').style.display = 'none';
    }

    function openActivateModal(id, name) {
        document.getElementById('activateUserName').textContent = name;
        document.getElementById('activatePasswordInput').value = '';
        const confirmInput = document.getElementById('activatePasswordConfirmInput');
        if (confirmInput) confirmInput.value = '';
        let url = '{{ route('acl.users.activate', ['id' => ':id']) }}'.replace(':id', id);
        if (modelParam) url += '?model=' + encodeURIComponent(modelParam);
        document.getElementById('activateForm').action = url;
        document.getElementById('activateModal').style.display = 'flex';
    }

    function closeActivateModal() {
        document.getElementById('activateModal').style.display = 'none';
    }

    function generateRandomPassword(inputId, confirmInputId) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
        let pass = '';
        for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const input = document.getElementById(inputId);
        if (input) {
            input.value = pass;
            input.type = 'text';
        }
        if (confirmInputId) {
            const confirmInput = document.getElementById(confirmInputId);
            if (confirmInput) {
                confirmInput.value = pass;
                confirmInput.type = 'text';
            }
        }
    }
</script>
@endsection
