@extends('acl::layouts.app')
@section('title', __('acl::matrix.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::matrix.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::matrix.subtitle') }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <input type="text" id="matrixSearchInput" class="form-control" placeholder="🔍 {{ __('acl::common.search') }} permessi..." style="width: 260px; font-size: 13px;">
    </div>
</div>

<div class="card" style="padding: 0; overflow: hidden;">
    <div style="overflow-x: auto; max-height: calc(100vh - 220px);">
        <table style="margin: 0; border-collapse: separate; border-spacing: 0;">
            <thead>
                <tr>
                    <th style="position: sticky; top: 0; left: 0; z-index: 3; background: var(--bg-card); min-width: 260px; box-shadow: 2px 2px 4px rgba(0,0,0,0.1);">
                        {{ __('acl::roles.permissions') }}
                    </th>
                    @foreach($roles as $role)
                    <th style="position: sticky; top: 0; z-index: 2; background: var(--bg-card); text-align: center; min-width: 140px; border-left: 1px solid var(--border);">
                        <div>{{ $role->name }}</div>
                        <code style="font-size: 11px; font-weight: normal; color: var(--text-muted);">{{ $role->slug }}</code>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($allPermissions as $module => $permissions)
                    <tr class="matrix-module-row" style="background: var(--bg-body);">
                        <td colspan="{{ count($roles) + 1 }}" style="font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--accent); padding: 10px 16px;">
                            📁 {{ $module ?: __('acl::permissions.uncategorized') }}
                        </td>
                    </tr>
                    @foreach($permissions as $permission)
                    <tr class="matrix-perm-row" data-search="{{ strtolower($module . ' ' . $permission->name . ' ' . $permission->slug) }}">
                        <td style="position: sticky; left: 0; z-index: 1; background: var(--bg-card); box-shadow: 2px 0 4px rgba(0,0,0,0.05);">
                            <div style="font-weight: 600; font-size: 13px;">{{ $permission->name }}</div>
                            <code style="font-size: 11px; color: var(--text-muted);">{{ $permission->slug }}</code>
                        </td>
                        @foreach($roles as $role)
                        @php $hasPerm = isset($matrix[$role->id][$permission->id]); @endphp
                        <td style="text-align: center; border-left: 1px solid var(--border); vertical-align: middle;">
                            <input type="checkbox" class="matrix-toggle-cb"
                                data-role="{{ $role->id }}"
                                data-perm="{{ $permission->id }}"
                                {{ $hasPerm ? 'checked' : '' }}
                                style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent);">
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ count($roles) + 1 }}" class="empty-state">
                            <p>{{ __('acl::roles.no_permissions_yet') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="matrixToast" style="display: none; position: fixed; bottom: 24px; right: 24px; background: var(--success); color: #fff; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.25); z-index: 99999;">
    ✓ {{ __('acl::matrix.saved_toast') }}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('matrixSearchInput');
    const permRows = document.querySelectorAll('.matrix-perm-row');
    const moduleRows = document.querySelectorAll('.matrix-module-row');
    const toast = document.getElementById('matrixToast');
    let toastTimer = null;

    function showToast(msg) {
        if (msg) toast.textContent = msg;
        toast.style.display = 'block';
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(function() {
            toast.style.display = 'none';
        }, 2000);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            permRows.forEach(function(row) {
                const search = row.getAttribute('data-search') || '';
                row.style.display = (!query || search.includes(query)) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.matrix-toggle-cb').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const roleId = this.getAttribute('data-role');
            const permId = this.getAttribute('data-perm');
            const isChecked = this.checked;

            fetch("{{ route('acl.matrix.toggle') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    role_id: roleId,
                    permission_id: permId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(isChecked ? '✓ Permesso assegnato' : '✕ Permesso revocato');
                } else {
                    alert('Errore durante il salvataggio.');
                }
            })
            .catch(err => {
                alert('Errore di connessione.');
            });
        });
    });
});
</script>
@endsection
