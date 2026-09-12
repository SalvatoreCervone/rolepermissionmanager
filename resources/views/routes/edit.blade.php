@extends('acl::layouts.app')
@section('title', __('acl::routes.configure_title') . ': ' . $resource->identifier . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::routes.configure_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.routes.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::routes.title') }}</a> / {{ $resource->identifier }}</div>
    </div>
</div>

<form action="{{ route('acl.routes.update', $resource->id) }}" method="POST">
    @csrf @method('PUT')

    {{-- Route Info (read-only) --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>🛤️ {{ __('acl::routes.route_info') }}</h3></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.identifier') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->identifier }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.method') }}</label>
                <div style="margin-top: 4px;"><span class="badge badge-{{ strtolower($resource->method ?? 'get') }}">{{ $resource->method }}</span></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.uri') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->uri }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.controller_action') }}</label>
                <div style="margin-top: 4px;"><code style="font-size: 12px;">{{ $resource->controller_action }}</code></div>
            </div>
            @if($resource->source_file)
            <div style="grid-column: span 2;">
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.source_file') }}</label>
                <div style="margin-top: 4px;"><span class="badge" style="background: var(--bg-primary); border: 1px solid var(--border); color: var(--info); font-size: 12px; font-family: monospace;">📄 {{ $resource->source_file }}</span></div>
            </div>
            @endif
        </div>
    </div>

    {{-- Dynamic Parameter Rules (Placeholders) --}}
    @if(!empty($placeholders) && count($placeholders) > 0)
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3>🎯 {{ __('acl::routes.parameter_rules') }}</h3>
                <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">{{ __('acl::routes.parameter_rules_subtitle') }}</div>
            </div>
            <button type="button" id="btnOpenAddParamModal" class="btn btn-primary btn-sm">
                + {{ __('acl::routes.add_parameter_rule') }}
            </button>
        </div>

        <div style="margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                <span style="font-size: 13px; font-weight: 500; color: var(--text-muted);">{{ __('acl::routes.detected_placeholders') }}:</span>
                @foreach($placeholders as $ph)
                    <span class="badge" style="background: rgba(116, 185, 255, 0.15); border: 1px solid rgba(116, 185, 255, 0.3); color: #74b9ff; font-family: monospace; font-size: 13px;">
                        {{ '{' . $ph . '}' }}
                    </span>
                @endforeach
            </div>

            <div class="form-group" style="max-width: 500px; margin-bottom: 0;">
                <label for="unmatched_parameter_behavior">{{ __('acl::routes.unmatched_behavior') }}</label>
                <select id="unmatched_parameter_behavior" name="unmatched_parameter_behavior" class="form-control">
                    <option value="allow" {{ ($resource->unmatched_parameter_behavior ?? 'allow') === 'allow' ? 'selected' : '' }}>
                        {{ __('acl::routes.unmatched_behavior_allow') }}
                    </option>
                    <option value="deny_404" {{ ($resource->unmatched_parameter_behavior ?? '') === 'deny_404' ? 'selected' : '' }}>
                        {{ __('acl::routes.unmatched_behavior_deny_404') }}
                    </option>
                    <option value="deny_403" {{ ($resource->unmatched_parameter_behavior ?? '') === 'deny_403' ? 'selected' : '' }}>
                        {{ __('acl::routes.unmatched_behavior_deny_403') }}
                    </option>
                </select>
            </div>
        </div>

        {{-- Table of Defined Parameter Rules --}}
        @if(isset($parameterRules) && $parameterRules->isNotEmpty())
        <div class="table-container" style="border: 1px solid var(--border); border-radius: 8px; overflow: hidden;">
            <table>
                <thead>
                    <tr>
                        <th style="width: 140px;">{{ __('acl::routes.parameter_name') }}</th>
                        <th style="min-width: 160px;">{{ __('acl::routes.parameter_value') }}</th>
                        <th style="width: 160px;">{{ __('acl::routes.status') }}</th>
                        <th style="min-width: 200px;">{{ __('acl::routes.required_permissions') }}</th>
                        <th style="width: 80px; text-align: right;">{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parameterRules as $pRule)
                    <tr>
                        <td style="vertical-align: middle;">
                            <span class="badge" style="background: rgba(116, 185, 255, 0.15); border: 1px solid rgba(116, 185, 255, 0.3); color: #74b9ff; font-family: monospace;">
                                {{ '{' . $pRule->parameter_name . '}' }}
                            </span>
                        </td>
                        <td style="vertical-align: middle;">
                            <strong style="font-family: 'JetBrains Mono', monospace; color: var(--text-primary);">{{ $pRule->parameter_value }}</strong>
                        </td>
                        <td style="vertical-align: middle;">
                            @if($pRule->is_super_admin_only)
                                <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3);">👑 {{ __('acl::routes.super_admin') }}</span>
                            @elseif($pRule->is_public)
                                <span class="badge badge-public">{{ __('acl::routes.public') }}</span>
                            @else
                                <span class="badge badge-protected">{{ __('acl::routes.protected') }}</span>
                                <span class="badge badge-{{ strtolower($pRule->operator ?? 'OR') }}" style="font-size: 10px; padding: 2px 6px;">{{ $pRule->operator ?? 'OR' }}</span>
                            @endif
                        </td>
                        <td style="vertical-align: middle;">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @forelse($pRule->permissions as $perm)
                                    <span class="chip">{{ $perm->slug }}</span>
                                @empty
                                    <span style="font-size: 12px; color: var(--text-muted);">{{ __('acl::routes.no_permissions') }}</span>
                                @endforelse
                            </div>
                        </td>
                        <td style="text-align: right; vertical-align: middle;">
                            <button type="button" class="btn btn-danger btn-sm" title="{{ __('acl::common.delete') }}" onclick="deleteParamRule('{{ route('acl.routes.parameter_rules.destroy', [$resource->id, $pRule->id]) }}')">🗑️</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div style="background: var(--bg-primary); border: 1px dashed var(--border); border-radius: 8px; padding: 18px; text-align: center; color: var(--text-muted); font-size: 13px;">
            ℹ️ {{ __('acl::routes.no_parameter_rules') }}
        </div>
        @endif
    </div>
    @endif

    {{-- Access Settings --}}
    @include('acl::partials.access-settings', ['resource' => $resource])

    {{-- Permission Assignment --}}
    @include('acl::partials.permission-picker', [
        'allPermissions'      => $allPermissions,
        'selectedPermissions' => old('permissions', $resource->permissions->pluck('id')->all()),
        'title'               => __('acl::routes.required_permissions'),
    ])

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">{{ __('acl::common.save_config') }}</button>
            <a href="{{ route('acl.routes.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
        </div>
        <button type="button" class="btn btn-danger" onclick="lockRouteImmediately()" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4);">
            {{ __('acl::routes.lock_now_btn') }}
        </button>
    </div>
</form>

<script>
function lockRouteImmediately() {
    if (confirm("{{ __('acl::routes.lock_now_confirm') }}")) {
        if (typeof selectAccessPolicy === 'function') {
            selectAccessPolicy('unconfigured');
        } else {
            const polInput = document.getElementById('acl_access_policy');
            if (polInput) polInput.value = 'unconfigured';
            const uncInput = document.getElementById('acl_is_unconfigured');
            if (uncInput) uncInput.value = '1';
        }
        document.querySelector('form[action*="acl/routes"]').submit();
    }
}
</script>

{{-- Modal for Adding a Parameter Rule --}}
@if(!empty($placeholders) && count($placeholders) > 0)
<div id="addParamRuleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; width: 100%; max-width: 700px; max-height: 90vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;">🎯 {{ __('acl::routes.add_parameter_rule') }}</h3>
            <button type="button" id="btnCloseParamModal" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">✕</button>
        </div>

        <form action="{{ route('acl.routes.parameter_rules.store', $resource->id) }}" method="POST" style="display: flex; flex-direction: column; flex: 1; overflow: hidden; margin: 0;">
            @csrf
            <div style="padding: 20px; overflow-y: auto; flex: 1;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal_parameter_name">{{ __('acl::routes.parameter_name') }} *</label>
                        <select id="modal_parameter_name" name="parameter_name" class="form-control" required>
                            @foreach($placeholders as $ph)
                                <option value="{{ $ph }}">{{ '{' . $ph . '}' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal_parameter_value">{{ __('acl::routes.parameter_value') }} *</label>
                        <input type="text" id="modal_parameter_value" name="parameter_value" class="form-control" placeholder="{{ __('acl::routes.parameter_value_placeholder') }}" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px; align-items: center; background: var(--bg-primary); padding: 14px; border-radius: 8px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-size: 12px;">{{ __('acl::routes.public_access') }}</label>
                        <div class="toggle-container" style="margin-top: 6px;">
                            <input type="hidden" name="is_public" id="modal_is_public" value="0">
                            <div class="toggle" onclick="toggleModalSetting(this, 'modal_is_public')"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label style="font-size: 12px;">{{ __('acl::routes.super_admin_only') }}</label>
                        <div class="toggle-container" style="margin-top: 6px;">
                            <input type="hidden" name="is_super_admin_only" id="modal_is_super_admin" value="0">
                            <div class="toggle" onclick="toggleModalSetting(this, 'modal_is_super_admin')"></div>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="modal_operator" style="font-size: 12px;">{{ __('acl::routes.operator') }}</label>
                        <select id="modal_operator" name="operator" class="form-control" style="margin-top: 4px;">
                            <option value="OR">OR</option>
                            <option value="AND">AND</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="font-size: 13px; font-weight: 500; margin-bottom: 8px; display: block;">{{ __('acl::routes.required_permissions') }}</label>
                    @if(isset($allPermissions))
                        @foreach($allPermissions as $module => $perms)
                        <div class="module-section" style="margin-bottom: 14px;">
                            <h4 style="font-size: 12px; margin-bottom: 6px; color: var(--text-muted);">{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
                            <div class="checkbox-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
                                @foreach($perms as $permission)
                                <label class="checkbox-item" style="padding: 8px 10px;">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}">
                                    <div>
                                        <div class="cb-label" style="font-size: 12px;">{{ $permission->name }}</div>
                                        <div class="cb-slug" style="font-size: 10px;">{{ $permission->slug }}</div>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div style="padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-secondary btn-sm" id="btnCancelParamModal">{{ __('acl::common.cancel') }}</button>
                <button type="submit" class="btn btn-primary btn-sm">✓ {{ __('acl::common.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('addParamRuleModal');
    const btnOpen = document.getElementById('btnOpenAddParamModal');
    const btnClose = document.getElementById('btnCloseParamModal');
    const btnCancel = document.getElementById('btnCancelParamModal');

    if (btnOpen) {
        btnOpen.addEventListener('click', function() {
            modal.style.display = 'flex';
        });
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
    }

    if (btnClose) btnClose.addEventListener('click', closeModal);
    if (btnCancel) btnCancel.addEventListener('click', closeModal);
});

function toggleModalSetting(element, inputId) {
    element.classList.toggle('active');
    const input = document.getElementById(inputId);
    if (input) {
        input.value = element.classList.contains('active') ? '1' : '0';
    }
}

function deleteParamRule(actionUrl) {
    if (confirm('{{ __('acl::routes.confirm_delete_parameter_rule') }}')) {
        const form = document.getElementById('deleteParamRuleForm');
        form.action = actionUrl;
        form.submit();
    }
}
</script>

<form id="deleteParamRuleForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endif
@endsection
