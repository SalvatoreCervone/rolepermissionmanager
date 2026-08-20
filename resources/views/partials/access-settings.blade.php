@php
    $isPublic = old('is_public', isset($resource) ? $resource->is_public : false);
    $isSuperAdminOnly = old('is_super_admin_only', isset($resource) ? $resource->is_super_admin_only : false);
    $operator = old('operator', isset($resource) ? $resource->operator : 'OR');
@endphp

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header"><h3>⚙️ {{ __('acl::routes.access_settings') }}</h3></div>

    <div class="form-group">
        <label>{{ __('acl::routes.public_access') }}</label>
        <div class="toggle-container">
            <input type="hidden" name="is_public" value="{{ $isPublic ? '1' : '0' }}">
            <div class="toggle {{ $isPublic ? 'active' : '' }}"></div>
            <span class="toggle-label">
                {{ $isPublic ? __('acl::routes.public_help') : __('acl::routes.protected_help') }}
            </span>
        </div>
    </div>

    <div class="form-group">
        <label>{{ __('acl::routes.super_admin_only') }}</label>
        <div class="toggle-container">
            <input type="hidden" name="is_super_admin_only" value="{{ $isSuperAdminOnly ? '1' : '0' }}">
            <div class="toggle {{ $isSuperAdminOnly ? 'active' : '' }}"></div>
            <span class="toggle-label">
                {{ __('acl::routes.super_admin_only_help') }}
            </span>
        </div>
    </div>

    <div class="form-group">
        <label for="operator">{{ __('acl::routes.operator_label') }}</label>
        <select id="operator" name="operator" class="form-control" style="max-width: 350px;">
            <option value="OR" {{ $operator === 'OR' ? 'selected' : '' }}>
                {{ __('acl::routes.operator_or') }}
            </option>
            <option value="AND" {{ $operator === 'AND' ? 'selected' : '' }}>
                {{ __('acl::routes.operator_and') }}
            </option>
        </select>
    </div>
</div>
