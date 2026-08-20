@extends('acl::layouts.app')
@section('title', __('acl::users.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>👤 {{ __('acl::users.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::users.subtitle') }}</div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    {{-- Autocomplete & Filter Bar --}}
    <form method="GET" action="{{ route('acl.users.index') }}" class="filter-bar" style="position: relative;">
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

        <select name="role" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::users.all_roles') }}</option>
            @foreach($roles as $r)
                <option value="{{ $r->slug }}" {{ request('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'role']))
            <a href="{{ route('acl.users.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear') }}</a>
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
                        <th>{{ __('acl::users.assigned_roles') }}</th>
                        <th>{{ __('acl::users.direct_permissions') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td style="font-weight: 600;">
                            {{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($user, $displayField) }}
                            @if(method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
                                <span class="badge badge-post" style="margin-left: 6px;">👑 Super Admin</span>
                            @endif
                        </td>
                        @if($secondaryField)
                            <td style="color: var(--text-secondary);">{{ \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($user, $secondaryField) ?: '—' }}</td>
                        @endif
                        <td>
                            @forelse($user->roles as $role)
                                <span class="chip">{{ $role->name }}</span>
                            @empty
                                <span style="color: var(--text-muted); font-size: 13px;">No roles</span>
                            @endforelse
                        </td>
                        <td>
                            @if($user->permissions->count() > 0)
                                <span class="badge badge-protected">+{{ $user->permissions->count() }} direct</span>
                            @else
                                <span style="color: var(--text-muted); font-size: 13px;">0 direct</span>
                            @endif
                        </td>
                        <td class="actions">
                            <a href="{{ route('acl.users.edit', $user->getKey()) }}" class="btn btn-secondary btn-sm">
                                ⚙️ {{ __('acl::users.manage_access') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $users->links('acl::pagination') }}
    @endif
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
                fetch(`{{ route('acl.users.search') }}?q=` + encodeURIComponent(query))
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
</script>
@endsection
