@extends('acl::layouts.app')
@section('title', __('acl::users.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>👤 {{ __('acl::users.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::users.subtitle') }}</div>
    </div>
</div>

@if(isset($allModels) && count($allModels) > 1)
    <div style="display: flex; gap: 8px; margin-bottom: 20px;">
        @foreach($allModels as $mKey => $mConf)
            <a href="{{ route('acl.users.index', ['model' => $mKey]) }}" 
               class="btn {{ $modelKey === $mKey ? 'btn-primary' : 'btn-secondary' }}"
               style="display: flex; align-items: center; gap: 8px; padding: 8px 18px; font-weight: 600;">
                <span>👤</span> {{ $mConf['label'] }}
            </a>
        @endforeach
    </div>
@endif

<div class="card" style="margin-bottom: 24px;">
    {{-- Autocomplete & Filter Bar --}}
    <form method="GET" action="{{ route('acl.users.index') }}" class="filter-bar" style="position: relative;">
        @if(isset($modelKey))
            <input type="hidden" name="model" value="{{ $modelKey }}">
        @endif

        <div style="position: relative; flex: 1; max-width: 400px;">
            <input type="text"
                   id="user-search-input"
                   name="search"
                   class="form-control"
                   value="{{ request('search') }}"
                   placeholder="{{ __('acl::users.search_placeholder', ['fields' => implode(', ', $searchableFields)]) }}"
                   autocomplete="off">
            <div id="autocomplete-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius-sm); margin-top: 4px; box-shadow: var(--shadow); z-index: 200; max-height: 280px; overflow-y: auto;"></div>
        </div>

        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::users.all_status') }}</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>✅ {{ __('acl::users.status_active') }}</option>
            <option value="deactivated" {{ request('status') === 'deactivated' ? 'selected' : '' }}>🚫 {{ __('acl::users.status_deactivated') }}</option>
        </select>

        <select name="role" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::users.all_roles') }}</option>
            @foreach($roles as $r)
                <option value="{{ $r->slug }}" {{ request('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>

        <select name="permission" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::routes.all_permissions') }}</option>
            <option value="none" {{ request('permission') === 'none' ? 'selected' : '' }}>⚠️ {{ __('acl::routes.no_permissions_assigned') }}</option>
            <option value="has_any" {{ request('permission') === 'has_any' ? 'selected' : '' }}>🔒 {{ __('acl::routes.has_permissions_assigned') }}</option>
            @if(isset($allPermissions))
                @foreach($allPermissions as $module => $perms)
                    <optgroup label="{{ $module ?: __('acl::permissions.uncategorized') }}">
                        @foreach($perms as $perm)
                            <option value="{{ $perm->id }}" {{ (string)request('permission') === (string)$perm->id ? 'selected' : '' }}>
                                {{ $perm->name }} ({{ $perm->slug }})
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            @endif
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'role', 'permission', 'status']))
            <a href="{{ route('acl.users.index', isset($modelKey) ? ['model' => $modelKey] : []) }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear') }}</a>
        @endif
    </form>
</div>

<div class="card">
    @if($users->isEmpty())
        <div class="empty-state">
            <div class="icon">👤</div>
            <p>{{ __('acl::users.no_users_found') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldHeader($displayField) }}</th>
                        @if($secondaryField)
                            <th>{{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldHeader($secondaryField) }}</th>
                        @endif
                        <th>{{ __('acl::users.status') }}</th>
                        <th>{{ __('acl::users.assigned_roles') }}</th>
                        <th>{{ __('acl::users.direct_permissions') }}</th>
                        <th style="text-align: right; min-width: 250px;">{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    @php
                        $isDeactivated = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::isUserDeactivated($user);
                        $formattedName = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($user, $displayField);
                    @endphp
                    <tr style="{{ $isDeactivated ? 'opacity: 0.65; background: rgba(239, 68, 68, 0.02);' : '' }}">
                        <td style="font-weight: 600;">
                            {{ $formattedName }}
                            @if(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                                <span class="badge badge-post" style="margin-left: 6px;">👑 Super Admin</span>
                            @endif
                        </td>
                        @if($secondaryField)
                            <td style="color: var(--text-secondary);">{{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($user, $secondaryField) ?: '—' }}</td>
                        @endif
                        <td>
                            @if($isDeactivated)
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    🚫 {{ __('acl::users.status_deactivated') }}
                                </span>
                            @else
                                <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);">
                                    ✅ {{ __('acl::users.status_active') }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @forelse($user->roles as $role)
                                <span class="chip">{{ $role->name }}</span>
                            @empty
                                <span style="color: var(--text-muted); font-size: 13px;">{{ __('acl::users.no_roles') }}</span>
                            @endforelse
                        </td>
                        <td>
                            @if($user->permissions->count() > 0)
                                <span class="badge badge-protected">+{{ $user->permissions->count() }} direct</span>
                            @else
                                <span style="color: var(--text-muted); font-size: 13px;">0 direct</span>
                            @endif
                        </td>
                        <td class="actions" style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('acl.users.edit', ['id' => $user->getKey(), 'model' => $modelKey ?? 'users']) }}" class="btn btn-secondary btn-sm" title="{{ __('acl::users.manage_access') }}">
                                ⚙️ {{ __('acl::users.manage_access') }}
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="openResetPasswordModal('{{ $user->getKey() }}', '{{ addslashes($formattedName) }}')" title="{{ __('acl::users.reset_password') }}">
                                🔑 {{ __('acl::users.reset_password') }}
                            </button>
                            @if($isDeactivated)
                                <button type="button" class="btn btn-secondary btn-sm" style="color: #22c55e; border-color: rgba(34, 197, 94, 0.4);" onclick="openActivateModal('{{ $user->getKey() }}', '{{ addslashes($formattedName) }}')" title="{{ __('acl::users.activate') }}">
                                    ✅ {{ __('acl::users.activate') }}
                                </button>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4);" onclick="openDeactivateModal('{{ $user->getKey() }}', '{{ addslashes($formattedName) }}')" title="{{ __('acl::users.deactivate') }}">
                                    🚫 {{ __('acl::users.deactivate') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('acl::pagination') }}
    @endif
</div>

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
                    <input type="password" name="password" id="resetPasswordInput" class="form-control" placeholder="{{ __('acl::users.password_placeholder') }}" required minlength="6" style="flex: 1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnToggleResetPwd" onclick="togglePasswordVisibility('resetPasswordInput', this)" title="Mostra / Nascondi password">
                        👁️
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="generateRandomPassword('resetPasswordInput', 'resetPasswordConfirmInput')">
                        🎲 {{ __('acl::users.generate_password') }}
                    </button>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500;">{{ __('acl::users.confirm_password') }}</label>
                <div style="display: flex; gap: 8px;">
                    <input type="password" name="password_confirmation" id="resetPasswordConfirmInput" class="form-control" placeholder="{{ __('acl::users.password_confirmation_placeholder') }}" required minlength="6" style="flex: 1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnToggleResetConfirmPwd" onclick="togglePasswordVisibility('resetPasswordConfirmInput', this)" title="Mostra / Nascondi password">
                        👁️
                    </button>
                </div>
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
                    <input type="password" name="password" id="activatePasswordInput" class="form-control" placeholder="{{ __('acl::users.password_placeholder') }}" minlength="6" style="flex: 1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnToggleActivatePwd" onclick="togglePasswordVisibility('activatePasswordInput', this)" title="Mostra / Nascondi password">
                        👁️
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="generateRandomPassword('activatePasswordInput', 'activatePasswordConfirmInput')">
                        🎲 {{ __('acl::users.generate_password') }}
                    </button>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; margin-bottom: 6px; font-weight: 500;">{{ __('acl::users.confirm_password') }}</label>
                <div style="display: flex; gap: 8px;">
                    <input type="password" name="password_confirmation" id="activatePasswordConfirmInput" class="form-control" placeholder="{{ __('acl::users.password_confirmation_placeholder') }}" minlength="6" style="flex: 1;">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnToggleActivateConfirmPwd" onclick="togglePasswordVisibility('activatePasswordConfirmInput', this)" title="Mostra / Nascondi password">
                        👁️
                    </button>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeActivateModal()">{{ __('acl::common.cancel') }}</button>
                <button type="submit" class="btn btn-sm" style="background: #22c55e; color: #fff; border: none;">✅ {{ __('acl::users.activate') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Live Autocomplete Script
    (function() {
        const input = document.getElementById('user-search-input');
        const dropdown = document.getElementById('autocomplete-dropdown');
        let debounceTimer = null;

        if (!input || !dropdown) return;

        input.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => {
                const modelParam = '{{ $modelKey ?? '' }}';
                const url = `{{ route('acl.users.search') }}?q=` + encodeURIComponent(query) + (modelParam ? `&model=${encodeURIComponent(modelParam)}` : '');

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.length === 0) {
                            dropdown.innerHTML = '<div style="padding: 12px 16px; color: var(--text-muted); font-size: 13px;">No users found</div>';
                            dropdown.style.display = 'block';
                            return;
                        }

                        let html = '';
                        data.forEach(item => {
                            const rolesHtml = item.roles.length > 0
                                ? `<span style="font-size: 11px; color: var(--accent); margin-left: 8px;">(${item.roles.join(', ')})</span>`
                                : '';
                            html += `
                                <a href="${item.edit_url}" style="display: block; padding: 10px 16px; border-bottom: 1px solid var(--border); color: var(--text-primary); text-decoration: none; font-size: 13px; transition: background 0.15s;" onmouseover="this.style.background='var(--accent-subtle)'" onmouseout="this.style.background='transparent'">
                                    <div style="font-weight: 600;">${item.label} ${rolesHtml}</div>
                                    <div style="font-size: 11px; color: var(--text-muted);">${item.sublabel}</div>
                                </a>
                            `;
                        });
                        dropdown.innerHTML = html;
                        dropdown.style.display = 'block';
                    })
                    .catch(() => {
                        dropdown.style.display = 'none';
                    });
            }, 250);
        });

        // Close autocomplete when clicking outside
        document.addEventListener('click', function(e) {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    })();

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

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            if (btn) btn.textContent = '🙈';
        } else {
            input.type = 'password';
            if (btn) btn.textContent = '👁️';
        }
    }
</script>
@endsection
