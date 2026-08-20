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
            <option value="public" {{ request('status') === 'public' ? 'selected' : '' }}>🌐 {{ __('acl::routes.public') }}</option>
            <option value="protected" {{ request('status') === 'protected' ? 'selected' : '' }}>🛡️ {{ __('acl::routes.protected') }}</option>
            <option value="deprecated" {{ request('status') === 'deprecated' ? 'selected' : '' }}>📦 {{ __('acl::routes.deprecated') }}</option>
            <option value="skipped" {{ request('status') === 'skipped' ? 'selected' : '' }}>⏭️ {{ __('acl::routes.skipped') }}</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'method', 'status']))
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
                        <th>{{ __('acl::routes.method') }}</th>
                        <th>{{ __('acl::routes.identifier') }}</th>
                        <th>{{ __('acl::routes.uri') }}</th>
                        <th>{{ __('acl::routes.controller_action') }}</th>
                        <th>{{ __('acl::routes.exclusion_reason') }}</th>
                        <th>{{ __('acl::routes.status') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routes as $route)
                    <tr>
                        <td><span class="badge badge-{{ strtolower($route->method ?? 'get') }}">{{ $route->method }}</span></td>
                        <td><code>{{ $route->identifier }}</code></td>
                        <td><code>{{ $route->uri }}</code></td>
                        <td><code style="font-size: 12px;">{{ $route->controller_action }}</code></td>
                        <td>
                            <span style="color: var(--warning); font-size: 13px;">⚠️ {{ $route->reason }}</span>
                        </td>
                        <td>
                            <span class="badge" style="background: var(--warning-subtle); color: var(--warning);">{{ __('acl::routes.skipped') }}</span>
                        </td>
                        <td>
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
                        <th>{{ __('acl::routes.method') }}</th>
                        <th>{{ __('acl::routes.identifier') }}</th>
                        <th>{{ __('acl::routes.uri') }}</th>
                        <th>{{ __('acl::routes.status') }}</th>
                        <th>{{ __('acl::routes.operator') }}</th>
                        <th>{{ __('acl::roles.permissions') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routes as $route)
                    <tr style="{{ $route->is_deprecated ? 'opacity: 0.5;' : '' }}">
                        <td><span class="badge badge-{{ strtolower($route->method ?? 'get') }}">{{ $route->method }}</span></td>
                        <td><code>{{ $route->identifier }}</code></td>
                        <td><code>{{ $route->uri }}</code></td>
                        <td>
                            @if($route->is_deprecated)
                                <span class="badge badge-deprecated">{{ __('acl::routes.deprecated') }}</span>
                            @elseif($route->is_public)
                                <span class="badge badge-public">{{ __('acl::routes.public') }}</span>
                            @else
                                <span class="badge badge-protected">{{ __('acl::routes.protected') }}</span>
                            @endif
                        </td>
                        <td><span class="badge badge-{{ strtolower($route->operator) }}">{{ $route->operator }}</span></td>
                        <td>
                            @forelse($route->permissions as $perm)
                                <span class="chip">{{ $perm->slug }}</span>
                            @empty
                                <span style="color: var(--warning); font-size: 12px;">⚠️ {{ __('acl::routes.no_permissions') }}</span>
                            @endforelse
                        </td>
                        <td>
                            <a href="{{ route('acl.routes.edit', $route->id) }}" class="btn btn-secondary btn-sm">{{ __('acl::routes.configure') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $routes->links('acl::pagination') }}
    @endif
</div>
@endsection
