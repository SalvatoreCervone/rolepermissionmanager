@php
    $res = $resource ?? null;
    $isPublic = (bool) old('is_public', $res ? $res->is_public : false);
    $isSuperAdminOnly = (bool) old('is_super_admin_only', $res ? $res->is_super_admin_only : false);
    $isUnconfigured = (bool) old('is_unconfigured', $res ? $res->is_unconfigured : false);
    $operator = old('operator', $res ? $res->operator : 'OR');

    // Determine initial active policy
    $hasPerms = false;
    if (old('permissions') !== null) {
        $hasPerms = count(old('permissions', [])) > 0;
    } elseif ($res && method_exists($res, 'permissions')) {
        $hasPerms = $res->relationLoaded('permissions')
            ? $res->permissions->isNotEmpty()
            : $res->permissions()->exists();
    }

    if (old('access_policy')) {
        $currentPolicy = old('access_policy');
    } elseif ($isUnconfigured) {
        $currentPolicy = 'unconfigured';
    } elseif ($isPublic) {
        $currentPolicy = 'public';
    } elseif ($isSuperAdminOnly) {
        $currentPolicy = 'super_admin';
    } elseif ($hasPerms) {
        $currentPolicy = 'protected';
    } else {
        $currentPolicy = 'authenticated';
    }
@endphp

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h3 style="margin: 0;">⚙️ {{ __('acl::routes.policy_selector_title') }}</h3>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--text-muted);">{{ __('acl::routes.policy_selector_desc') }}</p>
        </div>
        <div id="policy-current-badge">
            @if($currentPolicy === 'unconfigured')
                <span class="badge badge-unconfigured" style="font-size: 12px; padding: 5px 12px;">🔒 {{ __('acl::routes.policy_unconfigured') }}</span>
            @elseif($currentPolicy === 'public')
                <span class="badge badge-public" style="font-size: 12px; padding: 5px 12px;">🌐 {{ __('acl::routes.policy_public') }}</span>
            @elseif($currentPolicy === 'super_admin')
                <span class="badge badge-superadmin" style="font-size: 12px; padding: 5px 12px;">👑 {{ __('acl::routes.policy_super_admin') }}</span>
            @elseif($currentPolicy === 'protected')
                <span class="badge badge-protected" style="font-size: 12px; padding: 5px 12px;">🛡️ {{ __('acl::routes.policy_protected') }}</span>
            @else
                <span class="badge badge-authenticated" style="font-size: 12px; padding: 5px 12px;">👤 {{ __('acl::routes.policy_authenticated') }}</span>
            @endif
        </div>
    </div>

    {{-- Hidden Form Fields --}}
    <input type="hidden" name="access_policy" id="acl_access_policy" value="{{ $currentPolicy }}">
    <input type="hidden" name="is_public" id="acl_is_public" value="{{ $currentPolicy === 'public' ? '1' : '0' }}">
    <input type="hidden" name="is_super_admin_only" id="acl_is_super_admin_only" value="{{ $currentPolicy === 'super_admin' ? '1' : '0' }}">
    <input type="hidden" name="is_unconfigured" id="acl_is_unconfigured" value="{{ $currentPolicy === 'unconfigured' ? '1' : '0' }}">

    {{-- 5-Policy Visual Selector Grid --}}
    <div class="policy-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-top: 16px;">

        {{-- 1. Public --}}
        <div class="policy-card {{ $currentPolicy === 'public' ? 'active' : '' }}"
             data-policy="public"
             onclick="selectAccessPolicy('public')">
            <div class="policy-icon">🌐</div>
            <div class="policy-name">{{ __('acl::routes.policy_public') }}</div>
            <div class="policy-desc">{{ __('acl::routes.policy_public_desc') }}</div>
            <div class="policy-indicator"></div>
        </div>

        {{-- 2. Authenticated Only --}}
        <div class="policy-card {{ $currentPolicy === 'authenticated' ? 'active' : '' }}"
             data-policy="authenticated"
             onclick="selectAccessPolicy('authenticated')">
            <div class="policy-icon">👤</div>
            <div class="policy-name">{{ __('acl::routes.policy_authenticated') }}</div>
            <div class="policy-desc">{{ __('acl::routes.policy_authenticated_desc') }}</div>
            <div class="policy-indicator"></div>
        </div>

        {{-- 3. Protected with Permissions --}}
        <div class="policy-card {{ $currentPolicy === 'protected' ? 'active' : '' }}"
             data-policy="protected"
             onclick="selectAccessPolicy('protected')">
            <div class="policy-icon">🛡️</div>
            <div class="policy-name">{{ __('acl::routes.policy_protected') }}</div>
            <div class="policy-desc">{{ __('acl::routes.policy_protected_desc') }}</div>
            <div class="policy-indicator"></div>
        </div>

        {{-- 4. Super Admin Only --}}
        <div class="policy-card {{ $currentPolicy === 'super_admin' ? 'active' : '' }}"
             data-policy="super_admin"
             onclick="selectAccessPolicy('super_admin')">
            <div class="policy-icon">👑</div>
            <div class="policy-name">{{ __('acl::routes.policy_super_admin') }}</div>
            <div class="policy-desc">{{ __('acl::routes.policy_super_admin_desc') }}</div>
            <div class="policy-indicator"></div>
        </div>

        {{-- 5. Blocked / Unconfigured --}}
        <div class="policy-card {{ $currentPolicy === 'unconfigured' ? 'active' : '' }}"
             data-policy="unconfigured"
             onclick="selectAccessPolicy('unconfigured')">
            <div class="policy-icon">🔒</div>
            <div class="policy-name">{{ __('acl::routes.policy_unconfigured') }}</div>
            <div class="policy-desc">{{ __('acl::routes.policy_unconfigured_desc') }}</div>
            <div class="policy-indicator"></div>
        </div>

    </div>

    {{-- Permissions Operator (OR / AND) - visible when Protected --}}
    <div id="operator-settings-row" class="form-group" style="margin-top: 20px; padding-top: 18px; border-top: 1px solid var(--border); {{ $currentPolicy === 'protected' ? '' : 'display: none;' }}">
        <label for="operator" style="font-weight: 600;">{{ __('acl::routes.operator_label') }}</label>
        <p style="font-size: 13px; color: var(--text-muted); margin: 0 0 10px;">{{ __('acl::routes.operator_help') ?? 'Determina se l\'utente deve possedere almeno uno dei permessi (OR) o tutti i permessi (AND).' }}</p>
        <select id="operator" name="operator" class="form-control" style="max-width: 380px;">
            <option value="OR" {{ $operator === 'OR' ? 'selected' : '' }}>
                {{ __('acl::routes.operator_or') }}
            </option>
            <option value="AND" {{ $operator === 'AND' ? 'selected' : '' }}>
                {{ __('acl::routes.operator_and') }}
            </option>
        </select>
    </div>

    {{-- Policy Explanation Banner when NOT Protected --}}
    <div id="policy-notice-banner" style="margin-top: 16px; padding: 12px 16px; border-radius: 8px; font-size: 13px; {{ $currentPolicy === 'protected' ? 'display: none;' : '' }}">
        <span id="policy-notice-text"></span>
    </div>
</div>

<style>
.policy-card {
    background: var(--bg-primary);
    border: 2px solid var(--border);
    border-radius: 10px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    position: relative;
    user-select: none;
}
.policy-card:hover {
    border-color: var(--border-hover, #64748b);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.policy-card.active {
    border-color: var(--accent, #6366f1);
    background: rgba(99, 102, 241, 0.08);
    box-shadow: 0 0 0 1px var(--accent, #6366f1), 0 4px 14px rgba(99, 102, 241, 0.2);
}
.policy-card[data-policy="authenticated"].active {
    border-color: #a855f7;
    background: rgba(168, 85, 247, 0.08);
    box-shadow: 0 0 0 1px #a855f7, 0 4px 14px rgba(168, 85, 247, 0.2);
}
.policy-card[data-policy="public"].active {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.08);
    box-shadow: 0 0 0 1px #22c55e, 0 4px 14px rgba(34, 197, 94, 0.2);
}
.policy-card[data-policy="super_admin"].active {
    border-color: #eab308;
    background: rgba(234, 179, 8, 0.08);
    box-shadow: 0 0 0 1px #eab308, 0 4px 14px rgba(234, 179, 8, 0.2);
}
.policy-card[data-policy="unconfigured"].active {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.08);
    box-shadow: 0 0 0 1px #ef4444, 0 4px 14px rgba(239, 68, 68, 0.2);
}
.policy-icon {
    font-size: 26px;
    margin-bottom: 8px;
    line-height: 1;
}
.policy-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 6px;
}
.policy-desc {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.4;
    flex-grow: 1;
}
.policy-indicator {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid var(--border);
    transition: all 0.2s ease;
}
.policy-card.active .policy-indicator {
    border-color: var(--accent, #6366f1);
    background: var(--accent, #6366f1);
    box-shadow: inset 0 0 0 3px var(--bg-card);
}
.policy-card[data-policy="authenticated"].active .policy-indicator {
    border-color: #a855f7;
    background: #a855f7;
}
.policy-card[data-policy="public"].active .policy-indicator {
    border-color: #22c55e;
    background: #22c55e;
}
.policy-card[data-policy="super_admin"].active .policy-indicator {
    border-color: #eab308;
    background: #eab308;
}
.policy-card[data-policy="unconfigured"].active .policy-indicator {
    border-color: #ef4444;
    background: #ef4444;
}
</style>

<script>
function selectAccessPolicy(policy) {
    // 1. Update hidden input values
    const policyInput = document.getElementById('acl_access_policy');
    const publicInput = document.getElementById('acl_is_public');
    const superAdminInput = document.getElementById('acl_is_super_admin_only');
    const unconfiguredInput = document.getElementById('acl_is_unconfigured');

    if (policyInput) policyInput.value = policy;

    if (policy === 'public') {
        if (publicInput) publicInput.value = '1';
        if (superAdminInput) superAdminInput.value = '0';
        if (unconfiguredInput) unconfiguredInput.value = '0';
    } else if (policy === 'super_admin') {
        if (publicInput) publicInput.value = '0';
        if (superAdminInput) superAdminInput.value = '1';
        if (unconfiguredInput) unconfiguredInput.value = '0';
    } else if (policy === 'unconfigured') {
        if (publicInput) publicInput.value = '0';
        if (superAdminInput) superAdminInput.value = '0';
        if (unconfiguredInput) unconfiguredInput.value = '1';
    } else {
        // authenticated or protected
        if (publicInput) publicInput.value = '0';
        if (superAdminInput) superAdminInput.value = '0';
        if (unconfiguredInput) unconfiguredInput.value = '0';
    }

    // 2. Update active CSS class on cards
    document.querySelectorAll('.policy-card').forEach(function(card) {
        if (card.getAttribute('data-policy') === policy) {
            card.classList.add('active');
        } else {
            card.classList.remove('active');
        }
    });

    // 3. Update top badge
    const badgeContainer = document.getElementById('policy-current-badge');
    if (badgeContainer) {
        if (policy === 'unconfigured') {
            badgeContainer.innerHTML = '<span class="badge badge-unconfigured" style="font-size: 12px; padding: 5px 12px;">🔒 {{ __("acl::routes.policy_unconfigured") }}</span>';
        } else if (policy === 'public') {
            badgeContainer.innerHTML = '<span class="badge badge-public" style="font-size: 12px; padding: 5px 12px;">🌐 {{ __("acl::routes.policy_public") }}</span>';
        } else if (policy === 'super_admin') {
            badgeContainer.innerHTML = '<span class="badge badge-superadmin" style="font-size: 12px; padding: 5px 12px;">👑 {{ __("acl::routes.policy_super_admin") }}</span>';
        } else if (policy === 'protected') {
            badgeContainer.innerHTML = '<span class="badge badge-protected" style="font-size: 12px; padding: 5px 12px;">🛡️ {{ __("acl::routes.policy_protected") }}</span>';
        } else {
            badgeContainer.innerHTML = '<span class="badge badge-authenticated" style="font-size: 12px; padding: 5px 12px;">👤 {{ __("acl::routes.policy_authenticated") }}</span>';
        }
    }

    // 4. Toggle Operator and Permission Picker visibility/notice
    const operatorRow = document.getElementById('operator-settings-row');
    const noticeBanner = document.getElementById('policy-notice-banner');
    const noticeText = document.getElementById('policy-notice-text');
    const permPickerWrapper = document.getElementById('permission-picker-wrapper');
    const permPickerContainers = document.querySelectorAll('.permission-picker-container');

    if (policy === 'protected') {
        if (operatorRow) operatorRow.style.display = '';
        if (noticeBanner) noticeBanner.style.display = 'none';
        if (permPickerWrapper) {
            permPickerWrapper.style.display = '';
        } else {
            permPickerContainers.forEach(function(el) { el.style.display = ''; });
        }
    } else {
        if (operatorRow) operatorRow.style.display = 'none';
        if (permPickerWrapper) {
            permPickerWrapper.style.display = 'none';
        } else {
            permPickerContainers.forEach(function(el) { el.style.display = 'none'; });
        }
        if (noticeBanner) {
            noticeBanner.style.display = '';
            if (policy === 'authenticated') {
                noticeBanner.style.background = 'rgba(168, 85, 247, 0.1)';
                noticeBanner.style.border = '1px solid rgba(168, 85, 247, 0.3)';
                noticeBanner.style.color = '#c084fc';
                noticeText.innerHTML = '👤 <strong>{{ __("acl::routes.policy_authenticated") }}:</strong> {{ __("acl::routes.authenticated_only_help") }}';
            } else if (policy === 'public') {
                noticeBanner.style.background = 'rgba(34, 197, 94, 0.1)';
                noticeBanner.style.border = '1px solid rgba(34, 197, 94, 0.3)';
                noticeBanner.style.color = '#4ade80';
                noticeText.innerHTML = '🌐 <strong>{{ __("acl::routes.policy_public") }}:</strong> {{ __("acl::routes.public_help") }}';
            } else if (policy === 'super_admin') {
                noticeBanner.style.background = 'rgba(234, 179, 8, 0.1)';
                noticeBanner.style.border = '1px solid rgba(234, 179, 8, 0.3)';
                noticeBanner.style.color = '#facc15';
                noticeText.innerHTML = '👑 <strong>{{ __("acl::routes.policy_super_admin") }}:</strong> {{ __("acl::routes.super_admin_only_help") }}';
            } else if (policy === 'unconfigured') {
                noticeBanner.style.background = 'rgba(239, 68, 68, 0.1)';
                noticeBanner.style.border = '1px solid rgba(239, 68, 68, 0.3)';
                noticeBanner.style.color = '#f87171';
                noticeText.innerHTML = '🔒 <strong>{{ __("acl::routes.policy_unconfigured") }}:</strong> {{ __("acl::routes.policy_unconfigured_desc") }}';
            }
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const current = document.getElementById('acl_access_policy')?.value || '{{ $currentPolicy }}';
    selectAccessPolicy(current);
});
</script>
