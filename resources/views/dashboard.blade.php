@extends('acl::layouts.app')

@section('title', __('acl::dashboard.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))

@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::dashboard.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::dashboard.subtitle') }}</div>
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
        <div class="stat-icon">🌐</div>
        <div class="stat-value">{{ $stats['public_resources'] }}</div>
        <div class="stat-label">{{ __('acl::dashboard.stat_public') }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value">{{ $stats['unlinked_resources'] }}</div>
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
                            @elseif($resource->is_public)
                                <span class="badge badge-public">Public</span>
                            @else
                                <span class="badge badge-protected">Protected</span>
                            @endif
                        </td>
                        <td>{{ $resource->permissions->count() }}</td>
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $resource->updated_at->diffForHumans() }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
