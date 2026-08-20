@extends('acl::layouts.app')
@section('title', __('acl::resources.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::resources.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::resources.subtitle') }}</div>
    </div>
    <form action="{{ route('acl.resources.sync') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-success">🔄 {{ __('acl::nav.sync_routes') }}</button>
    </form>
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('acl::common.search') }}...">
        <select name="method" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::resources.all_methods') }}</option>
            @foreach($methods as $method)
                <option value="{{ $method }}" {{ request('method') === $method ? 'selected' : '' }}>{{ $method }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::resources.all_status') }}</option>
            <option value="public" {{ request('status') === 'public' ? 'selected' : '' }}>🌐 {{ __('acl::resources.public') }}</option>
            <option value="protected" {{ request('status') === 'protected' ? 'selected' : '' }}>🛡️ {{ __('acl::resources.protected') }}</option>
            <option value="deprecated" {{ request('status') === 'deprecated' ? 'selected' : '' }}>📦 {{ __('acl::resources.deprecated') }}</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'method', 'status']))
            <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear') }}</a>
        @endif
    </form>

    @if($resources->isEmpty())
        <div class="empty-state">
            <div class="icon">🛤️</div>
            <p>{{ __('acl::resources.no_resources_found') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('acl::resources.method') }}</th>
                        <th>{{ __('acl::resources.identifier') }}</th>
                        <th>{{ __('acl::resources.uri') }}</th>
                        <th>{{ __('acl::resources.status') }}</th>
                        <th>{{ __('acl::resources.operator') }}</th>
                        <th>{{ __('acl::roles.permissions') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
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
                                <span class="badge badge-deprecated">{{ __('acl::resources.deprecated') }}</span>
                            @elseif($resource->is_public)
                                <span class="badge badge-public">{{ __('acl::resources.public') }}</span>
                            @else
                                <span class="badge badge-protected">{{ __('acl::resources.protected') }}</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ strtolower($resource->operator) }}">{{ $resource->operator }}</span></td>
                        <td>
                            @forelse($resource->permissions as $perm)
                                <span class="chip">{{ $perm->slug }}</span>
                            @empty
                                <span style="color: var(--warning); font-size: 12px;">⚠️ {{ __('acl::resources.no_permissions') }}</span>
                            @endforelse
                        </td>
                        <td>
                            <a href="{{ route('acl.resources.edit', $resource->id) }}" class="btn btn-secondary btn-sm">{{ __('acl::resources.configure') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $resources->links('acl::pagination') }}
    @endif
</div>
@endsection
