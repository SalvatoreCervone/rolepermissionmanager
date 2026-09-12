@extends('acl::layouts.app')
@section('title', __('acl::resources.edit_title') . ': ' . $resource->identifier . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::resources.edit_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.resources.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::resources.title') }}</a> / {{ $resource->identifier }}</div>
    </div>
</div>

<form action="{{ route('acl.resources.update', $resource->id) }}" method="POST">
    @csrf @method('PUT')

    @if($resource->is_unconfigured)
    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 16px 20px; margin-bottom: 24px;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
            <span style="font-size: 28px; line-height: 1;">🔒</span>
            <div>
                <strong style="color: #ef4444; font-size: 16px;">{{ __('acl::resources.unconfigured_edit_warning_title') }}</strong>
                <p style="margin: 6px 0 0; font-size: 14px; color: var(--text);">{{ __('acl::resources.unconfigured_edit_warning_desc') }}</p>
            </div>
        </div>
    </div>
    @endif

    {{-- Resource Details --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>📦 {{ __('acl::resources.resource_details') }}</h3></div>

        <div class="form-group">
            <label for="identifier">{{ __('acl::resources.identifier') }} *</label>
            <input type="text" id="identifier" name="identifier" class="form-control" value="{{ old('identifier', $resource->identifier) }}" required>
            <small style="color: var(--text-muted); display: block; margin-top: 4px;">{{ __('acl::resources.identifier_help') }}</small>
        </div>

        <div class="form-group">
            <label for="description">{{ __('acl::resources.description') }}</label>
            <input type="text" id="description" name="description" class="form-control" value="{{ old('description', $resource->description) }}">
        </div>

        <div class="form-group">
            <label for="controller_action">{{ __('acl::resources.controller_action') }} ({{ __('acl::common.optional') }})</label>
            <input type="text" id="controller_action" name="controller_action" class="form-control" value="{{ old('controller_action', $resource->controller_action) }}">
        </div>
    </div>

    {{-- Access Settings --}}
    @include('acl::partials.access-settings', ['resource' => $resource])

    {{-- Permission Assignment --}}
    @include('acl::partials.permission-picker', [
        'allPermissions'      => $allPermissions,
        'selectedPermissions' => old('permissions', $resource->permissions->pluck('id')->all()),
        'title'               => __('acl::resources.required_permissions'),
    ])

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">{{ __('acl::common.save') }}</button>
            <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
        </div>
        <button type="button" class="btn btn-danger" onclick="lockResourceImmediately()" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4);">
            {{ __('acl::resources.lock_now_btn') }}
        </button>
    </div>
</form>

<script>
function lockResourceImmediately() {
    if (confirm("{{ __('acl::resources.lock_now_confirm') }}")) {
        if (typeof selectAccessPolicy === 'function') {
            selectAccessPolicy('unconfigured');
        } else {
            const polInput = document.getElementById('acl_access_policy');
            if (polInput) polInput.value = 'unconfigured';
            const uncInput = document.getElementById('acl_is_unconfigured');
            if (uncInput) uncInput.value = '1';
        }
        document.querySelector('form[action*="acl/resources"]').submit();
    }
}
</script>
@endsection
