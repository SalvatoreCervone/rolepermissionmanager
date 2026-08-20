@extends('acl::layouts.app')
@section('title', __('acl::resources.create_title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::resources.create_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.resources.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::resources.title') }}</a> / {{ __('acl::common.new') }}</div>
    </div>
</div>

<form action="{{ route('acl.resources.store') }}" method="POST">
    @csrf

    {{-- Resource Details --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>📦 {{ __('acl::resources.resource_details') }}</h3></div>

        <div class="form-group">
            <label for="identifier">{{ __('acl::resources.identifier') }} *</label>
            <input type="text" id="identifier" name="identifier" class="form-control" value="{{ old('identifier') }}"
                placeholder="es. CorsoController@dettagliocorsi, App\Services\PaymentService@charge, button.export_excel" required autofocus>
            <small style="color: var(--text-muted); display: block; margin-top: 4px;">{{ __('acl::resources.identifier_help') }}</small>
        </div>

        <div class="form-group">
            <label for="description">{{ __('acl::resources.description') }}</label>
            <input type="text" id="description" name="description" class="form-control" value="{{ old('description') }}"
                placeholder="es. Visualizzazione dettagli corsi interni">
        </div>

        <div class="form-group">
            <label for="controller_action">{{ __('acl::resources.controller_action') }} ({{ __('acl::common.optional') }})</label>
            <input type="text" id="controller_action" name="controller_action" class="form-control" value="{{ old('controller_action') }}"
                placeholder="es. App\Http\Controllers\CorsoController@dettagliocorsi">
        </div>
    </div>

    {{-- Access Settings --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>⚙️ {{ __('acl::resources.access_settings') }}</h3></div>

        <div class="form-group">
            <label>{{ __('acl::resources.public_access') }}</label>
            <div class="toggle-container">
                <input type="hidden" name="is_public" value="{{ old('is_public', '0') }}">
                <div class="toggle {{ old('is_public') === '1' ? 'active' : '' }}"></div>
                <span class="toggle-label">
                    {{ old('is_public') === '1' ? __('acl::resources.public_help') : __('acl::resources.protected_help') }}
                </span>
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('acl::routes.super_admin_only') }}</label>
            <div class="toggle-container">
                <input type="hidden" name="is_super_admin_only" value="{{ old('is_super_admin_only', '0') }}">
                <div class="toggle {{ old('is_super_admin_only') === '1' ? 'active' : '' }}"></div>
                <span class="toggle-label">
                    {{ __('acl::routes.super_admin_only_help') }}
                </span>
            </div>
        </div>

        <div class="form-group">
            <label for="operator">{{ __('acl::resources.operator_label') }}</label>
            <select id="operator" name="operator" class="form-control" style="max-width: 350px;">
                <option value="OR" {{ old('operator', 'OR') === 'OR' ? 'selected' : '' }}>
                    {{ __('acl::resources.operator_or') }}
                </option>
                <option value="AND" {{ old('operator') === 'AND' ? 'selected' : '' }}>
                    {{ __('acl::resources.operator_and') }}
                </option>
            </select>
        </div>
    </div>

    {{-- Permission Assignment --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>🔑 {{ __('acl::resources.required_permissions') }}</h3>
        </div>

        @php $selectedPermissions = old('permissions', []); @endphp

        @forelse($allPermissions as $module => $permissions)
            <div class="module-section">
                <h4>{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
                <div class="checkbox-grid">
                    @foreach($permissions as $permission)
                    <label class="checkbox-item {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                            {{ in_array($permission->id, $selectedPermissions) ? 'checked' : '' }}>
                        <div>
                            <div class="cb-label">{{ $permission->name }}</div>
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
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save') }}</button>
        <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
