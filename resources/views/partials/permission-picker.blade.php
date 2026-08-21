<div class="card permission-picker-container" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h3>🔑 {{ $title ?? __('acl::roles.permissions') }}</h3>
            @if(!empty($helpText))
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ $helpText }}</div>
            @endif
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 13px; color: var(--text-muted);">
                <strong class="perm-selected-count">{{ is_countable($selectedPermissions ?? []) ? count($selectedPermissions) : 0 }}</strong> {{ $countLabel ?? __('acl::common.selected') }}
            </span>
        </div>
    </div>

    @php
        $selectedIds = is_array($selectedPermissions ?? []) ? $selectedPermissions : [];
        $fieldName = $inputName ?? 'permissions[]';
        $roleSlugs = $grantedRoleSlugs ?? [];
    @endphp

    @if(!empty($allPermissions) && $allPermissions->isNotEmpty())
        <div style="margin-bottom: 16px;">
            <input type="text" class="form-control perm-filter-input" placeholder="🔍 {{ __('acl::common.search') }} permessi (nome, modulo o slug)..." style="font-size: 13px; width: 100%;">
        </div>
    @endif

    @forelse($allPermissions as $module => $permissions)
        <div class="module-section" style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h4 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--text);">
                    📁 {{ $module ?: __('acl::permissions.uncategorized') }}
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: normal;">({{ count($permissions) }})</span>
                </h4>
                <div style="display: flex; gap: 6px;">
                    <button type="button" class="btn btn-secondary btn-sm btn-select-all-module" style="padding: 2px 8px; font-size: 11px;">✓ {{ __('acl::common.select_all') }}</button>
                    <button type="button" class="btn btn-secondary btn-sm btn-deselect-all-module" style="padding: 2px 8px; font-size: 11px;">✕ {{ __('acl::common.cancel') }}</button>
                </div>
            </div>
            <div class="checkbox-grid">
                @foreach($permissions as $permission)
                @php $grantedViaRole = in_array($permission->slug, $roleSlugs); @endphp
                <label class="checkbox-item perm-checkbox-label {{ in_array($permission->id, $selectedIds) ? 'checked' : '' }}" data-search="{{ strtolower($module . ' ' . $permission->name . ' ' . $permission->slug) }}">
                    <input type="checkbox" name="{{ $fieldName }}" value="{{ $permission->id }}" class="perm-checkbox-input"
                        {{ in_array($permission->id, $selectedIds) ? 'checked' : '' }}>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.permission-picker-container').forEach(function(container) {
        const filterInput = container.querySelector('.perm-filter-input');
        const checkboxes = container.querySelectorAll('.perm-checkbox-input');
        const countSpan = container.querySelector('.perm-selected-count');
        const moduleSections = container.querySelectorAll('.module-section');

        function updateCount() {
            if (countSpan) {
                const total = Array.from(checkboxes).filter(cb => cb.checked).length;
                countSpan.textContent = total;
            }
        }

        checkboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                const label = this.closest('.checkbox-item');
                if (label) {
                    if (this.checked) label.classList.add('checked');
                    else label.classList.remove('checked');
                }
                updateCount();
            });
        });

        // Filter search
        if (filterInput) {
            filterInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                moduleSections.forEach(function(section) {
                    let hasVisible = false;
                    const items = section.querySelectorAll('.perm-checkbox-label');
                    items.forEach(function(item) {
                        const searchText = item.getAttribute('data-search') || '';
                        if (!query || searchText.includes(query)) {
                            item.style.display = 'flex';
                            hasVisible = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    section.style.display = hasVisible ? 'block' : 'none';
                });
            });
        }

        // Select / Deselect all per module
        container.querySelectorAll('.btn-select-all-module').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const section = this.closest('.module-section');
                if (section) {
                    section.querySelectorAll('.perm-checkbox-input').forEach(function(cb) {
                        cb.checked = true;
                        const label = cb.closest('.checkbox-item');
                        if (label) label.classList.add('checked');
                    });
                    updateCount();
                }
            });
        });

        container.querySelectorAll('.btn-deselect-all-module').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const section = this.closest('.module-section');
                if (section) {
                    section.querySelectorAll('.perm-checkbox-input').forEach(function(cb) {
                        cb.checked = false;
                        const label = cb.closest('.checkbox-item');
                        if (label) label.classList.remove('checked');
                    });
                    updateCount();
                }
            });
        });
    });
});
</script>
