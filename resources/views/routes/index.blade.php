@extends('acl::layouts.app')
@section('title', __('acl::routes.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::routes.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::routes.subtitle') }}</div>
    </div>
    <form action="{{ route('acl.routes.sync') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-success">🔄 {{ __('acl::nav.sync_routes') }}</button>
    </form>
</div>

{{-- Tabs Navigation --}}
@php
    $currentStatus = request('status', '');
@endphp
<div class="tabs-nav" style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
    <a href="{{ route('acl.routes.index', array_merge(request()->except('status', 'page'), ['status' => ''])) }}" 
       class="btn {{ ($currentStatus === '' || $currentStatus === 'all') ? 'btn-primary' : 'btn-secondary' }} btn-sm"
       style="display: inline-flex; align-items: center; gap: 6px;">
        <span>🌐</span>
        <span>{{ __('acl::routes.tab_all') }}</span>
        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 11px; padding: 2px 6px; border-radius: 10px;">{{ $totalCount ?? 0 }}</span>
    </a>

    <a href="{{ route('acl.routes.index', array_merge(request()->except('status', 'page'), ['status' => 'managed'])) }}" 
       class="btn {{ $currentStatus === 'managed' ? 'btn-primary' : 'btn-secondary' }} btn-sm"
       style="display: inline-flex; align-items: center; gap: 6px;">
        <span>🛡️</span>
        <span>{{ __('acl::routes.tab_managed') }}</span>
        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 11px; padding: 2px 6px; border-radius: 10px;">{{ $managedCount ?? 0 }}</span>
    </a>

    <a href="{{ route('acl.routes.index', array_merge(request()->except('status', 'page'), ['status' => 'skipped'])) }}" 
       class="btn {{ $currentStatus === 'skipped' ? 'btn-primary' : 'btn-secondary' }} btn-sm"
       style="display: inline-flex; align-items: center; gap: 6px;">
        <span>⏭️</span>
        <span>{{ __('acl::routes.tab_excluded') }}</span>
        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 11px; padding: 2px 6px; border-radius: 10px;">{{ $skippedCount ?? 0 }}</span>
    </a>

    <a href="{{ route('acl.routes.index', array_merge(request()->except('status', 'page'), ['status' => 'deprecated'])) }}" 
       class="btn {{ $currentStatus === 'deprecated' ? 'btn-primary' : 'btn-secondary' }} btn-sm"
       style="display: inline-flex; align-items: center; gap: 6px;">
        <span>📦</span>
        <span>{{ __('acl::routes.tab_deprecated') }}</span>
        <span class="badge" style="background: {{ ($deprecatedCount ?? 0) > 0 ? 'rgba(234, 179, 8, 0.3); color: #facc15;' : 'rgba(255,255,255,0.2);' }} font-size: 11px; padding: 2px 6px; border-radius: 10px;">{{ $deprecatedCount ?? 0 }}</span>
    </a>
</div>

@if($currentStatus === 'deprecated' && ($deprecatedCount ?? 0) > 0)
<div style="background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">📦</span>
        <div>
            <strong style="color: #facc15; font-size: 15px;">{{ __('acl::routes.deprecated_alert_title', ['count' => $deprecatedCount]) }}</strong>
            <p style="margin: 2px 0 0; font-size: 13px; color: var(--text-muted);">{{ __('acl::routes.deprecated_alert_desc') }}</p>
        </div>
    </div>
    <form action="{{ route('acl.routes.clean_deprecated') }}" method="POST" onsubmit="return confirm('{{ __('acl::routes.confirm_clean_deprecated') }}')">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger btn-sm" style="background: #ef4444; color: #fff; padding: 6px 14px; border-radius: 6px; font-weight: 500;">
            🗑️ {{ __('acl::routes.clean_deprecated_btn') }}
        </button>
    </form>
</div>
@endif

{{-- Bulk Action Bar (Visible when routes are selected) --}}
@if(!$isSkipped)
<div id="bulkActionBar" style="display: none; background: var(--bg-card); border: 1px solid var(--accent); border-radius: 8px; padding: 14px 18px; margin-bottom: 20px; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 20px;">⚡</span>
        <div>
            <strong id="selectedCount" style="color: var(--accent); font-size: 16px;">0</strong>
            <span style="font-size: 14px; color: var(--text);">{{ __('acl::routes.routes_selected') }}</span>
        </div>
    </div>

    <form action="{{ route('acl.routes.bulk_update') }}" method="POST" id="bulkForm" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin: 0;">
        @csrf
        <div id="bulkHiddenIds"></div>

        <select name="action" id="bulkActionSelect" class="form-control" style="min-width: 270px;" required>
            <option value="">{{ __('acl::routes.select_action') }}</option>
            <option value="set_super_admin">{{ __('acl::routes.bulk_set_super_admin') }}</option>
            <option value="remove_super_admin">{{ __('acl::routes.bulk_remove_super_admin') }}</option>
            <option value="make_public">{{ __('acl::routes.bulk_make_public') }}</option>
            <option value="make_protected">{{ __('acl::routes.bulk_make_protected') }}</option>
            <option value="set_authenticated_only">{{ __('acl::routes.bulk_set_authenticated_only') }}</option>
            <option value="set_unconfigured">{{ __('acl::routes.bulk_set_unconfigured') }}</option>
            <option value="add_permissions">{{ __('acl::routes.bulk_add_permissions') }}</option>
            <option value="sync_permissions">{{ __('acl::routes.bulk_sync_permissions') }}</option>
            <option value="remove_all_permissions">{{ __('acl::routes.bulk_remove_all_permissions') }}</option>
            <option value="set_operator_or">{{ __('acl::routes.bulk_operator_or') }}</option>
            <option value="set_operator_and">{{ __('acl::routes.bulk_operator_and') }}</option>
            <option value="delete">{{ __('acl::routes.bulk_delete') }}</option>
        </select>

        <button type="button" id="btnOpenPermissionModal" class="btn btn-secondary btn-sm" style="display: none;">
            🔑 {{ __('acl::routes.required_permissions') }} (<span id="bulkPermCount">0</span>)
        </button>

        <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitBulk">
            ⚡ {{ __('acl::routes.apply_bulk_action') }}
        </button>

        <button type="button" class="btn btn-secondary btn-sm" id="btnCancelBulk">
            ✕ {{ __('acl::common.cancel') }}
        </button>

        {{-- Hidden container for selected permissions from modal --}}
        <div id="bulkPermissionsInputs"></div>
    </form>
</div>

{{-- Bulk Permissions Modal --}}
<div id="bulkPermModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; width: 100%; max-width: 750px; max-height: 85vh; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
        <div style="padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;">🔑 {{ __('acl::routes.select_permissions_to_apply') }}</h3>
            <button type="button" id="btnClosePermModal" style="background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer;">✕</button>
        </div>
        <div style="padding: 20px; overflow-y: auto; flex: 1;">
            @if(isset($allPermissions))
                @foreach($allPermissions as $module => $permissions)
                <div class="module-section" style="margin-bottom: 16px;">
                    <h4 style="font-size: 13px; margin-bottom: 8px; color: var(--text-muted);">{{ $module ?: __('acl::permissions.uncategorized') }}</h4>
                    <div class="checkbox-grid">
                        @foreach($permissions as $permission)
                        <label class="checkbox-item modal-perm-item">
                            <input type="checkbox" class="modal-perm-cb" value="{{ $permission->id }}">
                            <div>
                                <div class="cb-label">{{ $permission->name }}</div>
                                <div class="cb-slug">{{ $permission->slug }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            @endif
        </div>
        <div style="padding: 16px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelPermModal">{{ __('acl::common.cancel') }}</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnConfirmPermModal">✓ {{ __('acl::common.save') }}</button>
        </div>
    </div>
</div>
@endif

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('acl::common.search') }}...">
        <select name="method" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::routes.all_methods') }}</option>
            @foreach($methods as $method)
                <option value="{{ $method }}" {{ request('method') === $method ? 'selected' : '' }}>{{ $method }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::routes.all_status') }}</option>
            <option value="managed" {{ request('status') === 'managed' ? 'selected' : '' }}>🛡️ {{ __('acl::routes.tab_managed') }}</option>
            <option value="public" {{ request('status') === 'public' ? 'selected' : '' }}>🌐 {{ __('acl::routes.public') }}</option>
            <option value="authenticated" {{ request('status') === 'authenticated' ? 'selected' : '' }}>👤 {{ __('acl::routes.authenticated_only') }}</option>
            <option value="protected" {{ request('status') === 'protected' ? 'selected' : '' }}>🛡️ {{ __('acl::routes.protected') }}</option>
            <option value="super_admin" {{ request('status') === 'super_admin' ? 'selected' : '' }}>👑 {{ __('acl::routes.super_admin') }}</option>
            <option value="unconfigured" {{ request('status') === 'unconfigured' ? 'selected' : '' }}>🔒 {{ __('acl::routes.unconfigured') }}</option>
            <option value="deprecated" {{ request('status') === 'deprecated' ? 'selected' : '' }}>📦 {{ __('acl::routes.deprecated') }}</option>
            <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>⏭️ {{ __('acl::routes.skipped') }}</option>
        </select>
        @if(isset($routeFiles) && $routeFiles->isNotEmpty())
            <select name="file" class="form-control" onchange="this.form.submit()">
                <option value="">{{ __('acl::routes.all_files') }}</option>
                @foreach($routeFiles as $rf)
                    <option value="{{ $rf }}" {{ request('file') === $rf ? 'selected' : '' }}>📄 {{ $rf }}</option>
                @endforeach
            </select>
        @endif
        <select name="permission" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::routes.all_permissions') }}</option>
            <option value="none" {{ request('permission') === 'none' ? 'selected' : '' }}>⚠️ {{ __('acl::routes.no_permissions_assigned') }}</option>
            <option value="has_any" {{ request('permission') === 'has_any' ? 'selected' : '' }}>🔒 {{ __('acl::routes.has_permissions_assigned') }}</option>
            @if(isset($allPermissions))
                @foreach($allPermissions as $module => $perms)
                    <optgroup label="{{ $module ?: __('acl::permissions.uncategorized') }}">
                        @foreach($perms as $perm)
                            <option value="{{ $perm->id }}" {{ (string)request('permission') === (string)$perm->id ? 'selected' : '' }}>
                                {{ $perm->name }} ({{ $perm->slug }})
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            @endif
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'method', 'status', 'file', 'permission']))
            <a href="{{ route('acl.routes.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear') }}</a>
        @endif
    </form>

    @if($routes->isEmpty())
        <div class="empty-state">
            <div class="icon">{{ $isSkipped ? '⏭️' : '🛤️' }}</div>
            <p>{{ $isSkipped ? __('acl::routes.no_skipped_routes_found') : __('acl::routes.no_routes_found') }}</p>
        </div>
    @elseif($isSkipped)
        {{-- Skipped / Excluded Routes Table --}}
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="min-width: 320px;">{{ __('acl::routes.route_info') }}</th>
                        <th style="min-width: 200px;">{{ __('acl::routes.exclusion_reason') }}</th>
                        <th style="width: 140px; text-align: right;">{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routes as $route)
                    <tr>
                        <td style="vertical-align: top; padding: 12px 16px;">
                            {{-- Line 1: Method badge + Identifier + Skipped badge --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="badge badge-{{ strtolower($route->method ?? 'get') }}">{{ $route->method }}</span>
                                    <strong style="font-size: 14px; color: var(--text-primary); font-family: 'JetBrains Mono', 'Fira Code', monospace;">{{ $route->identifier }}</strong>
                                </div>
                                <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                    🚫 {{ ($route->exclusion_type ?? 'config') === 'rule' ? __('acl::routes.excluded_by_rule') : __('acl::routes.excluded_by_config') }}
                                </span>
                            </div>

                            {{-- Line 2: URI + Controller Action + Source File --}}
                            <div style="display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--text-muted); flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <span>🌐</span>
                                    <code style="color: var(--text-primary);">/{{ ltrim($route->uri, '/') }}</code>
                                </div>

                                @if($route->controller_action && $route->controller_action !== '—')
                                    <div style="display: flex; align-items: center; gap: 5px;" title="{{ $route->controller_action }}">
                                        <span>⚡</span>
                                        <code style="font-size: 11px; color: var(--text-secondary);">
                                            {{ \Illuminate\Support\Str::replaceFirst('App\\Http\\Controllers\\', '', $route->controller_action) }}
                                        </code>
                                    </div>
                                @endif

                                @if(isset($route->source_file) && $route->source_file)
                                    <div style="display: flex; align-items: center; gap: 5px;" title="{{ __('acl::routes.source_file') }}: {{ $route->source_file }}">
                                        <span class="badge" style="background: rgba(116, 185, 255, 0.1); border: 1px solid rgba(116, 185, 255, 0.25); color: #74b9ff; font-size: 11px; font-family: monospace;">
                                            📄 {{ $route->source_file }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td style="vertical-align: middle;">
                            <span style="color: var(--warning); font-size: 13px;">⚠️ {{ $route->reason }}</span>
                        </td>

                        <td style="text-align: right; vertical-align: middle;">
                            <a href="{{ route('acl.scanner_rules.index') }}" class="btn btn-secondary btn-sm" title="{{ __('acl::routes.manage_rules') }}">⚙️ {{ __('acl::nav.scanner_rules') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $routes->links('acl::pagination') }}
    @else
        {{-- Managed Routes Table --}}
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">
                            <input type="checkbox" id="selectAllRoutes" title="{{ __('acl::common.select_all') }}">
                        </th>
                        <th style="min-width: 340px;">{{ __('acl::routes.route_info') }}</th>
                        <th style="min-width: 220px;">{{ __('acl::roles.permissions') }}</th>
                        <th style="width: 110px; text-align: right;">{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routes as $route)
                    <tr style="{{ ($route->is_deprecated ?? false) ? 'opacity: 0.5;' : '' }}">
                        <td style="text-align: center; vertical-align: top; padding-top: 14px;">
                            @if($route->is_skipped ?? false)
                                <input type="checkbox" disabled title="{{ __('acl::routes.excluded_cannot_bulk') }}" style="opacity: 0.25; cursor: not-allowed;">
                            @else
                                <input type="checkbox" class="route-select-cb" value="{{ $route->id }}" data-identifier="{{ $route->identifier }}">
                            @endif
                        </td>
                        <td style="vertical-align: top; padding: 12px 16px;">
                            {{-- Line 1: Method badge + Identifier (bold) + Status & Operator badges --}}
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span class="badge badge-{{ strtolower($route->method ?? 'get') }}">{{ $route->method }}</span>
                                    <strong style="font-size: 14px; color: var(--text-primary); font-family: 'JetBrains Mono', 'Fira Code', monospace; letter-spacing: -0.2px;">{{ $route->identifier }}</strong>
                                </div>

                                <div style="display: flex; align-items: center; gap: 6px;">
                                    @if($route->is_skipped ?? false)
                                        <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">
                                            🚫 {{ ($route->exclusion_type ?? 'config') === 'rule' ? __('acl::routes.excluded_by_rule') : __('acl::routes.excluded_by_config') }}
                                        </span>
                                    @elseif($route->is_deprecated)
                                        <span class="badge badge-deprecated">{{ __('acl::routes.deprecated') }}</span>
                                    @elseif($route->is_unconfigured)
                                        <span class="badge badge-unconfigured" title="{{ __('acl::routes.unconfigured_tooltip') }}">🔒 {{ __('acl::routes.unconfigured') }}</span>
                                    @elseif($route->is_super_admin_only)
                                        <span class="badge badge-superadmin">👑 {{ __('acl::routes.super_admin') }}</span>
                                    @elseif($route->is_public)
                                        <span class="badge badge-public">🌐 {{ __('acl::routes.public') }}</span>
                                    @elseif($route->isAuthenticatedOnly())
                                        <span class="badge badge-authenticated" title="{{ __('acl::routes.authenticated_only_tooltip') }}">👤 {{ __('acl::routes.authenticated_only') }}</span>
                                    @else
                                        <span class="badge badge-protected">🛡️ {{ __('acl::routes.protected') }}</span>
                                        <span class="badge badge-{{ strtolower($route->operator) }}" style="font-size: 10px; padding: 2px 6px;">{{ $route->operator }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Line 2: URI + Controller Action + Source File Badge --}}
                            <div style="display: flex; align-items: center; gap: 14px; font-size: 12px; color: var(--text-muted); flex-wrap: wrap;">
                                {{-- URI --}}
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <span>🌐</span>
                                    <code style="color: var(--text-primary);">/{{ ltrim($route->uri, '/') }}</code>
                                </div>

                                {{-- Controller Action --}}
                                @if($route->controller_action && $route->controller_action !== '—')
                                    <div style="display: flex; align-items: center; gap: 5px;" title="{{ $route->controller_action }}">
                                        <span>⚡</span>
                                        <code style="font-size: 11px; color: var(--text-secondary);">
                                            {{ \Illuminate\Support\Str::replaceFirst('App\\Http\\Controllers\\', '', $route->controller_action) }}
                                        </code>
                                    </div>
                                @endif

                                {{-- Route Source File Badge --}}
                                @if($route->source_file)
                                    <div style="display: flex; align-items: center; gap: 5px;" title="{{ __('acl::routes.source_file') }}: {{ $route->source_file }}">
                                        <span class="badge" style="background: rgba(116, 185, 255, 0.1); border: 1px solid rgba(116, 185, 255, 0.25); color: #74b9ff; font-size: 11px; font-family: monospace;">
                                            📄 {{ $route->source_file }}
                                        </span>
                                    </div>
                                @endif

                                @if($route->is_skipped ?? false)
                                    <div style="display: flex; align-items: center; gap: 5px; color: var(--warning);">
                                        <span>⚠️</span>
                                        <span style="font-size: 11px;">{{ __('acl::routes.exclusion_reason') }}: <code>{{ $route->reason }}</code></span>
                                    </div>
                                @endif
                            </div>
                        </td>

                        <td style="vertical-align: middle;">
                            @if($route->is_skipped ?? false)
                                <span style="display: inline-flex; align-items: center; gap: 5px; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border); color: var(--text-muted); border-radius: 4px; padding: 3px 8px; font-size: 11px;">
                                    <span>ℹ️</span> {{ __('acl::routes.unmanaged_acl') }}
                                </span>
                            @else
                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                    @forelse($route->permissions as $perm)
                                        <span class="chip">{{ $perm->slug }}</span>
                                    @empty
                                        @if($route->is_unconfigured)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25); color: #ef4444; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 500;">
                                                🔒 {{ __('acl::routes.unconfigured') }}
                                            </span>
                                        @elseif($route->isAuthenticatedOnly())
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.25); color: #c084fc; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 500;">
                                                👤 {{ __('acl::routes.authenticated_only') }}
                                            </span>
                                        @elseif($route->is_public)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.25); color: #4ade80; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 500;">
                                                🌐 {{ __('acl::routes.public') }}
                                            </span>
                                        @elseif($route->is_super_admin_only)
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.25); color: #facc15; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 500;">
                                                👑 {{ __('acl::routes.super_admin') }}
                                            </span>
                                        @else
                                            <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.25); color: #eab308; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 500;">
                                                ⚠️ {{ __('acl::routes.no_permissions') }}
                                            </span>
                                        @endif
                                    @endforelse
                                </div>
                            @endif
                        </td>

                        <td style="text-align: right; vertical-align: middle;">
                            @if($route->is_skipped ?? false)
                                <a href="{{ route('acl.scanner_rules.index') }}" class="btn btn-secondary btn-sm" title="{{ __('acl::routes.manage_rules') }}">⚙️ {{ __('acl::nav.scanner_rules') }}</a>
                            @else
                                <div style="display: inline-flex; align-items: center; gap: 6px; justify-content: flex-end;">
                                    <a href="{{ route('acl.routes.edit', $route->id) }}" class="btn btn-secondary btn-sm">⚙️ {{ __('acl::routes.configure') }}</a>
                                    @if($route->is_deprecated)
                                        <form action="{{ route('acl.routes.destroy', $route->id) }}" method="POST" class="inline-form" onsubmit="return confirm('{{ __('acl::common.confirm_delete') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="{{ __('acl::common.delete') }}">🗑️</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $routes->links('acl::pagination') }}
    @endif
</div>

@if(!$isSkipped)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAllRoutes');
    const routeCheckboxes = document.querySelectorAll('.route-select-cb:not(:disabled)');
    const bulkBar = document.getElementById('bulkActionBar');
    const selectedCount = document.getElementById('selectedCount');
    const bulkHiddenIds = document.getElementById('bulkHiddenIds');
    const bulkActionSelect = document.getElementById('bulkActionSelect');
    const btnOpenPermModal = document.getElementById('btnOpenPermissionModal');
    const btnCancelBulk = document.getElementById('btnCancelBulk');
    const bulkForm = document.getElementById('bulkForm');

    const permModal = document.getElementById('bulkPermModal');
    const btnClosePermModal = document.getElementById('btnClosePermModal');
    const btnCancelPermModal = document.getElementById('btnCancelPermModal');
    const btnConfirmPermModal = document.getElementById('btnConfirmPermModal');
    const modalPermCheckboxes = document.querySelectorAll('.modal-perm-cb');
    const bulkPermissionsInputs = document.getElementById('bulkPermissionsInputs');
    const bulkPermCount = document.getElementById('bulkPermCount');

    let selectedPermIds = [];

    function updateBulkState() {
        const checked = Array.from(routeCheckboxes).filter(cb => cb.checked);
        const count = checked.length;
        selectedCount.textContent = count;

        if (count > 0) {
            bulkBar.style.display = 'flex';
            bulkHiddenIds.innerHTML = checked.map(cb => `<input type="hidden" name="ids[]" value="${cb.value}">`).join('');
        } else {
            bulkBar.style.display = 'none';
            bulkHiddenIds.innerHTML = '';
            if (selectAll) selectAll.checked = false;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            routeCheckboxes.forEach(cb => {
                if (!cb.disabled) cb.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    routeCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const allChecked = Array.from(routeCheckboxes).every(c => c.checked);
            if (selectAll) selectAll.checked = allChecked;
            updateBulkState();
        });
    });

    if (btnCancelBulk) {
        btnCancelBulk.addEventListener('click', function() {
            routeCheckboxes.forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateBulkState();
        });
    }

    bulkActionSelect.addEventListener('change', function() {
        const val = this.value;
        if (val === 'add_permissions' || val === 'sync_permissions') {
            btnOpenPermModal.style.display = 'inline-block';
            openPermModal();
        } else {
            btnOpenPermModal.style.display = 'none';
        }
    });

    btnOpenPermModal.addEventListener('click', openPermModal);

    function openPermModal() {
        permModal.style.display = 'flex';
    }

    function closePermModal() {
        permModal.style.display = 'none';
    }

    if (btnClosePermModal) btnClosePermModal.addEventListener('click', closePermModal);
    if (btnCancelPermModal) btnCancelPermModal.addEventListener('click', closePermModal);

    if (btnConfirmPermModal) {
        btnConfirmPermModal.addEventListener('click', function() {
            selectedPermIds = Array.from(modalPermCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
            bulkPermCount.textContent = selectedPermIds.length;
            bulkPermissionsInputs.innerHTML = selectedPermIds.map(id => `<input type="hidden" name="permissions[]" value="${id}">`).join('');
            closePermModal();
        });
    }

    modalPermCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const parent = this.closest('.modal-perm-item');
            if (parent) {
                if (this.checked) parent.classList.add('checked');
                else parent.classList.remove('checked');
            }
        });
    });

    bulkForm.addEventListener('submit', function(e) {
        const count = Array.from(routeCheckboxes).filter(cb => cb.checked).length;
        if (count === 0) {
            e.preventDefault();
            alert("Nessuna rotta selezionata.");
            return;
        }

        const action = bulkActionSelect.value;
        if (!action) {
            e.preventDefault();
            alert("Seleziona un'azione massiva da eseguire.");
            return;
        }

        if (action === 'delete') {
            if (!confirm("{{ __('acl::routes.confirm_bulk_action') }}")) {
                e.preventDefault();
                return;
            }
        }

        if ((action === 'add_permissions' || action === 'sync_permissions') && selectedPermIds.length === 0) {
            if (!confirm("Non hai selezionato alcun permesso. Vuoi procedere comunque?")) {
                e.preventDefault();
                openPermModal();
                return;
            }
        }
    });
});
</script>
@endif
@endsection
