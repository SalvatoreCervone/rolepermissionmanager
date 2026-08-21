@extends('acl::layouts.app')

@section('title', __('acl::dashboard.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))

@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::dashboard.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::dashboard.subtitle') }}</div>
    </div>
    <div style="display: flex; gap: 8px;">
        <a href="{{ route('acl.simulator.index') }}" class="btn btn-primary btn-sm">🔍 {{ __('acl::simulator.title') }}</a>
        <a href="{{ route('acl.matrix.index') }}" class="btn btn-secondary btn-sm">📊 {{ __('acl::matrix.title') }}</a>
    </div>
</div>

{{-- Coverage / Security Health Card --}}
<div class="card" style="margin-bottom: 24px; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <div>
            <h3 style="margin: 0; font-size: 16px;">🛡️ {{ __('acl::dashboard.security_health') }}</h3>
            <span style="font-size: 13px; color: var(--text-muted);">{{ __('acl::dashboard.security_health_desc') }}</span>
        </div>
        <div style="font-size: 24px; font-weight: 700; color: {{ $stats['coverage_percentage'] >= 80 ? 'var(--success)' : ($stats['coverage_percentage'] >= 50 ? 'var(--warning)' : 'var(--danger)') }};">
            {{ $stats['coverage_percentage'] }}%
        </div>
    </div>

    {{-- Progress Multi-Bar --}}
    @php
        $tot = max(1, $stats['total_resources']);
        $pPub = round(($stats['public_resources'] / $tot) * 100);
        $pSup = round(($stats['super_admin_resources'] / $tot) * 100);
        $pPer = round(($stats['with_perms_resources'] / $tot) * 100);
        $pUnl = max(0, 100 - ($pPub + $pSup + $pPer));
    @endphp
    <div style="height: 10px; background: var(--bg-body); border-radius: 6px; overflow: hidden; display: flex; margin-bottom: 12px;">
        <div style="width: {{ $pPer }}%; background: var(--info);" title="Protette con Permessi ({{ $pPer }}%)"></div>
        <div style="width: {{ $pSup }}%; background: #eab308;" title="Solo Super Admin ({{ $pSup }}%)"></div>
        <div style="width: {{ $pPub }}%; background: var(--success);" title="Pubbliche ({{ $pPub }}%)"></div>
        @if($pUnl > 0)
        <div style="width: {{ $pUnl }}%; background: var(--warning);" title="Senza Permessi Assegnati ({{ $pUnl }}%)"></div>
        @endif
    </div>

    <div style="display: flex; gap: 20px; font-size: 12px; color: var(--text-muted); flex-wrap: wrap;">
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 10px; height: 10px; border-radius: 2px; background: var(--info);"></span>
            {{ __('acl::routes.protected') }} ({{ $stats['with_perms_resources'] }})
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 10px; height: 10px; border-radius: 2px; background: #eab308;"></span>
            {{ __('acl::routes.super_admin') }} ({{ $stats['super_admin_resources'] }})
        </span>
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 10px; height: 10px; border-radius: 2px; background: var(--success);"></span>
            {{ __('acl::routes.public') }} ({{ $stats['public_resources'] }})
        </span>
        @if($stats['unlinked_resources'] > 0)
        <span style="display: flex; align-items: center; gap: 6px;">
            <span style="width: 10px; height: 10px; border-radius: 2px; background: var(--warning);"></span>
            {{ __('acl::dashboard.stat_unlinked') }} ({{ $stats['unlinked_resources'] }})
        </span>
        @endif
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👤</div>
        <div class="stat-value">{{ $stats['total_users'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_users') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value">{{ $stats['total_roles'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_roles') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🔑</div>
        <div class="stat-value">{{ $stats['total_permissions'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_permissions') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🛤️</div>
        <div class="stat-value">{{ $stats['total_routes'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_routes') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-value">{{ $stats['total_custom_resources'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_custom_resources') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🛡️</div>
        <div class="stat-value">{{ $stats['protected_resources'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_protected') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👑</div>
        <div class="stat-value">{{ $stats['super_admin_resources'] }}</div>
        <div class="stat-label">{{ __('acl::routes.super_admin') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value" style="{{ $stats['unlinked_resources'] > 0 ? 'color: var(--warning);' : '' }}">{{ $stats['unlinked_resources'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_unlinked') }}</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>🕐 {{ __('acl::dashboard.recent_resources') }}</h3>
        <a href="{{ route('acl.routes.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::dashboard.view_all') }}</a>
    </div>
    @if($recentResources->isEmpty())
        <div class="empty-state">
            <div class="icon">🛤️</div>
            <p>{{ __('acl::dashboard.no_resources') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Identifier</th>
                        <th>URI / Action</th>
                        <th>Status</th>
                        <th>Permissions</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentResources as $resource)
                    <tr>
                        <td>
                            @if($resource->isRoute())
                                <span class="badge badge-{{ strtolower($resource->method ?? 'get') }}">{{ $resource->method ?? 'ROUTE' }}</span>
                            @else
                                <span class="badge" style="background: var(--accent-subtle); color: var(--accent);">CUSTOM</span>
                            @endif
                        </td>
                        <td><code>{{ $resource->identifier }}</code></td>
                        <td><code>{{ $resource->uri ?: ($resource->controller_action ?: '—') }}</code></td>
                        <td>
                            @if($resource->is_deprecated)
                                <span class="badge badge-deprecated">Deprecated</span>
                            @elseif($resource->is_super_admin_only)
                                <span class="badge" style="background: rgba(234, 179, 8, 0.15); color: #eab308;">👑 Super Admin</span>
                            @elseif($resource->is_public)
                                <span class="badge badge-public">Public</span>
                            @else
                                <span class="badge badge-protected">Protected</span>
                            @endif
                        </td>
                        <td>{{ $resource->permissions->count() }}</td>
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $resource->updated_at ? $resource->updated_at->diffForHumans() : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
