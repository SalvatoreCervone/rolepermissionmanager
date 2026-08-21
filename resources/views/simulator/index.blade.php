@extends('acl::layouts.app')
@section('title', __('acl::simulator.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::simulator.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::simulator.subtitle') }}</div>
    </div>
</div>

{{-- Tester Input Form --}}
<div class="card" style="margin-bottom: 24px;">
    <form method="GET" action="{{ route('acl.simulator.index') }}">
        <div style="display: grid; grid-template-columns: {{ (isset($allModels) && count($allModels) > 1) ? '180px 1fr 1fr auto' : '1fr 1fr auto' }}; gap: 16px; align-items: flex-end;">
            @if(isset($allModels) && count($allModels) > 1)
            <div>
                <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">👥 {{ __('acl::users.title') }} Type</label>
                <select name="model" class="form-control" onchange="this.form.submit()" style="width: 100%;">
                    @foreach($allModels as $mKey => $mConf)
                        <option value="{{ $mKey }}" {{ ($modelKey ?? '') === $mKey ? 'selected' : '' }}>
                            {{ $mConf['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">👤 {{ __('acl::simulator.select_user') }}</label>
                <select name="user_id" class="form-control" required style="width: 100%;">
                    <option value="">— {{ __('acl::simulator.choose_user') }} —</option>
                    @foreach($users as $u)
                        @php
                            $uLabel = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($u, $displayField);
                            $uSub = \SalvatoreCervone\RolePermissionManager\Http\Controllers\UserController::formatFieldValue($u, $secondaryField);
                        @endphp
                        <option value="{{ $u->id }}" {{ $selectedUserId == $u->id ? 'selected' : '' }}>
                            {{ $uLabel }} ({{ $uSub ?: "#{$u->id}" }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">🛤️ {{ __('acl::simulator.select_resource') }}</label>
                <select name="identifier" class="form-control" required style="width: 100%;">
                    <option value="">— {{ __('acl::simulator.choose_resource') }} —</option>
                    @foreach($resources as $res)
                        <option value="{{ $res->identifier }}" {{ $selectedIdentifier === $res->identifier ? 'selected' : '' }}>
                            {{ $res->method ? "[{$res->method}] " : '' }}{{ $res->identifier }} {{ $res->uri ? "({$res->uri})" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="btn btn-primary" style="height: 38px; display: flex; align-items: center; gap: 6px;">
                    🔍 {{ __('acl::simulator.test_access_btn') }}
                </button>
            </div>
        </div>
    </form>
</div>

@if($evaluation)
{{-- Results Card --}}
<div class="card" style="margin-bottom: 24px; border-left: 4px solid {{ $evaluation['isAllowed'] ? 'var(--success)' : 'var(--danger)' }};">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 32px;">{{ $evaluation['isAllowed'] ? '🟢' : '🔴' }}</span>
            <div>
                <h3 style="margin: 0; font-size: 20px; color: {{ $evaluation['isAllowed'] ? 'var(--success)' : 'var(--danger)' }};">
                    {{ $evaluation['isAllowed'] ? __('acl::simulator.verdict_allowed') : __('acl::simulator.verdict_denied') }}
                </h3>
                <div style="font-size: 14px; color: var(--text-muted); margin-top: 2px;">{{ $evaluation['verdictReason'] }}</div>
            </div>
        </div>
        <span class="badge" style="font-size: 13px; padding: 6px 14px; background: {{ $evaluation['isAllowed'] ? 'var(--success-subtle)' : 'var(--danger-subtle)' }}; color: {{ $evaluation['isAllowed'] ? 'var(--success)' : 'var(--danger)' }};">
            {{ $evaluation['isAllowed'] ? '200 OK / Authorized' : '403 Forbidden' }}
        </span>
    </div>

    {{-- Details Grid --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
        <div>
            <h4 style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">👤 {{ __('acl::simulator.user_privileges') }}</h4>
            <div style="margin-bottom: 10px;">
                <span style="font-size: 12px; color: var(--text-muted);">{{ __('acl::roles.title') }}:</span>
                <div style="margin-top: 4px;">
                    @forelse($evaluation['userRoles'] as $role)
                        <span class="chip">{{ $role->name }}</span>
                    @empty
                        <span style="color: var(--text-muted); font-size: 13px;">{{ __('acl::common.none') }}</span>
                    @endforelse
                </div>
            </div>
            <div>
                <span style="font-size: 12px; color: var(--text-muted);">{{ __('acl::roles.permissions') }} ({{ count($evaluation['allUserPerms']) }}):</span>
                <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">
                    @forelse($evaluation['allUserPerms'] as $slug)
                        <code style="font-size: 11px; background: var(--bg-body); padding: 2px 6px; border-radius: 4px;">{{ $slug }}</code>
                    @empty
                        <span style="color: var(--text-muted); font-size: 13px;">{{ __('acl::common.none') }}</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <h4 style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">🛤️ {{ __('acl::simulator.resource_requirements') }}</h4>
            @if(isset($evaluation['resource']))
                <div style="margin-bottom: 10px;">
                    <span style="font-size: 12px; color: var(--text-muted);">{{ __('acl::routes.status') }}:</span>
                    <div style="margin-top: 4px;">
                        @if($evaluation['resource']->is_super_admin_only)
                            <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #eab308;">👑 {{ __('acl::routes.super_admin') }}</span>
                        @elseif($evaluation['resource']->is_public)
                            <span class="badge badge-public">{{ __('acl::routes.public') }}</span>
                        @else
                            <span class="badge badge-protected">{{ __('acl::routes.protected') }}</span>
                        @endif
                        <span class="badge badge-{{ strtolower($evaluation['resource']->operator) }}" style="margin-left: 6px;">{{ $evaluation['resource']->operator }}</span>
                    </div>
                </div>
                <div>
                    <span style="font-size: 12px; color: var(--text-muted);">{{ __('acl::routes.required_permissions') }}:</span>
                    <div style="margin-top: 4px; display: flex; flex-wrap: wrap; gap: 4px;">
                        @forelse($evaluation['resource']->permissions as $perm)
                            @php $isMatched = in_array($perm->slug, $evaluation['allUserPerms']); @endphp
                            <code style="font-size: 11px; padding: 2px 6px; border-radius: 4px; border: 1px solid {{ $isMatched ? 'var(--success)' : 'var(--danger)' }}; color: {{ $isMatched ? 'var(--success)' : 'var(--danger)' }};">
                                {{ $isMatched ? '✓' : '✕' }} {{ $perm->slug }}
                            </code>
                        @empty
                            <span style="color: var(--text-muted); font-size: 13px;">{{ __('acl::routes.no_permissions') }}</span>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Step by step evaluation trace --}}
    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border);">
        <h4 style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 12px;">📋 {{ __('acl::simulator.evaluation_steps') }}</h4>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            @foreach($evaluation['steps'] as $idx => $step)
            <div style="display: flex; align-items: flex-start; gap: 12px; background: var(--bg-body); padding: 10px 14px; border-radius: 6px;">
                <span style="font-size: 16px;">
                    @if($step['status'] === 'success') 🟢
                    @elseif($step['status'] === 'danger') 🔴
                    @else ℹ️ @endif
                </span>
                <div>
                    <div style="font-weight: 600; font-size: 13px;">{{ $idx + 1 }}. {{ $step['name'] }}</div>
                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">{{ $step['details'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
