<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h3>🔑 {{ $title ?? __('acl::roles.permissions') }}</h3>
            @if(!empty($helpText))
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">{{ $helpText }}</div>
            @endif
        </div>
        <span style="font-size: 13px; color: var(--text-muted);">
            {{ is_countable($selectedPermissions ?? []) ? count($selectedPermissions) : 0 }} {{ $countLabel ?? __('acl::common.selected') }}
        </span>
    </div>

    @php
        $selectedIds = is_array($selectedPermissions ?? []) ? $selectedPermissions : [];
        $fieldName = $inputName ?? 'permissions[]';
        $roleSlugs = $grantedRoleSlugs ?? [];
    @endphp

    @forelse($allPermissions as $module => $permissions)
        <div class="module-section">
            <h4>{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
            <div class="checkbox-grid">
                @foreach($permissions as $permission)
                @php $grantedViaRole = in_array($permission->slug, $roleSlugs); @endphp
                <label class="checkbox-item {{ in_array($permission->id, $selectedIds) ? 'checked' : '' }}">
                    <input type="checkbox" name="{{ $fieldName }}" value="{{ $permission->id }}"
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
