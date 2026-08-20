@extends('acl::layouts.app')
@section('title', 'Routes / Resources — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Routes / Resources</h2>
        <div class="breadcrumb">Manage protected routes discovered from your application</div>
    </div>
    <form action="{{ route('acl.resources.sync') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-success">🔄 Sync Routes</button>
    </form>
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search identifier, URI, controller...">
        <select name="method" class="form-control" onchange="this.form.submit()">
            <option value="">All Methods</option>
            @foreach($methods as $method)
                <option value="{{ $method }}" {{ request('method') === $method ? 'selected' : '' }}>{{ $method }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="public" {{ request('status') === 'public' ? 'selected' : '' }}>🌐 Public</option>
            <option value="protected" {{ request('status') === 'protected' ? 'selected' : '' }}>🛡️ Protected</option>
            <option value="deprecated" {{ request('status') === 'deprecated' ? 'selected' : '' }}>📦 Deprecated</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        @if(request()->hasAny(['search', 'method', 'status']))
            <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary btn-sm">Clear</a>
        @endif
    </form>

    @if($resources->isEmpty())
        <div class="empty-state">
            <div class="icon">🛤️</div>
            <p>No resources found. Click "Sync Routes" to discover your application routes.</p>
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
                        <th>Operator</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resources as $resource)
                    <tr style="{{ $resource->is_deprecated ? 'opacity: 0.5;' : '' }}">
                        <td><span class="badge badge-{{ strtolower($resource->method) }}">{{ $resource->method }}</span></td>
                        <td><code>{{ $resource->identifier }}</code></td>
                        <td><code>{{ $resource->uri }}</code></td>
                        <td>
                            @if($resource->is_deprecated)
                                <span class="badge badge-deprecated">Deprecated</span>
                            @elseif($resource->is_public)
                                <span class="badge badge-public">Public</span>
                            @else
                                <span class="badge badge-protected">Protected</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ strtolower($resource->operator) }}">{{ $resource->operator }}</span></td>
                        <td>
                            @forelse($resource->permissions as $perm)
                                <span class="chip">{{ $perm->slug }}</span>
                            @empty
                                <span style="color: var(--warning); font-size: 12px;">⚠️ None</span>
                            @endforelse
                        </td>
                        <td>
                            <a href="{{ route('acl.resources.edit', $resource->id) }}" class="btn btn-secondary btn-sm">Configure</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $resources->links() }}
    @endif
</div>
@endsection
