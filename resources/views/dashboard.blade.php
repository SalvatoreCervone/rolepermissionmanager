@extends('acl::layouts.app')

@section('title', 'Dashboard — ACL Manager')

@section('content')
<div class="page-header">
    <div>
        <h2>Dashboard</h2>
        <div class="breadcrumb">Overview of your ACL system</div>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👤</div>
        <div class="stat-value">{{ $stats['total_users'] }}</div>
        <div class="stat-label">Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-value">{{ $stats['total_roles'] }}</div>
        <div class="stat-label">Roles</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🔑</div>
        <div class="stat-value">{{ $stats['total_permissions'] }}</div>
        <div class="stat-label">Permissions</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🛡️</div>
        <div class="stat-value">{{ $stats['protected_resources'] }}</div>
        <div class="stat-label">Protected Routes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🌐</div>
        <div class="stat-value">{{ $stats['public_resources'] }}</div>
        <div class="stat-label">Public Routes</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value">{{ $stats['unlinked_resources'] }}</div>
        <div class="stat-label">Unlinked (No Permissions)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-value">{{ $stats['deprecated_resources'] }}</div>
        <div class="stat-label">Deprecated Routes</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>🕐 Recently Updated Resources</h3>
        <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    @if($recentResources->isEmpty())
        <div class="empty-state">
            <div class="icon">🛤️</div>
            <p>No resources found. Run <code>php artisan acl:sync</code> or click "Sync Routes" to discover your routes.</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Identifier</th>
                        <th>URI</th>
                        <th>Status</th>
                        <th>Permissions</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentResources as $resource)
                    <tr>
                        <td><span class="badge badge-{{ strtolower($resource->method) }}">{{ $resource->method }}</span></td>
                        <td><code>{{ $resource->identifier }}</code></td>
                        <td><code>{{ $resource->uri }}</code></td>
                        <td>
                            @if($resource->is_public)
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
