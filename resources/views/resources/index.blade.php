@extends('acl::layouts.app')
@section('title', __('acl::resources.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::resources.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::resources.subtitle') }}</div>
    </div>
    <a href="{{ route('acl.resources.create') }}" class="btn btn-primary">➕ {{ __('acl::resources.create_title') }}</a>
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('acl::common.search') }}...">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::resources.all_status') }}</option>
            <option value="public" {{ request('status') === 'public' ? 'selected' : '' }}>🌐 {{ __('acl::resources.public') }}</option>
            <option value="protected" {{ request('status') === 'protected' ? 'selected' : '' }}>🛡️ {{ __('acl::resources.protected') }}</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear') }}</a>
        @endif
    </form>

    @if($resources->isEmpty())
        <div class="empty-state">
            <div class="icon">📦</div>
            <p>{{ __('acl::resources.no_resources_found') }}</p>
            <a href="{{ route('acl.resources.create') }}" class="btn btn-primary btn-sm" style="margin-top: 12px;">➕ {{ __('acl::resources.create_title') }}</a>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('acl::resources.identifier') }}</th>
                        <th>{{ __('acl::resources.description') }}</th>
                        <th>{{ __('acl::resources.controller_action') }}</th>
                        <th>{{ __('acl::resources.status') }}</th>
                        <th>{{ __('acl::resources.operator') }}</th>
                        <th>{{ __('acl::roles.permissions') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($resources as $resource)
                    <tr>
                        <td><code>{{ $resource->identifier }}</code></td>
                        <td>{{ $resource->description ?: '—' }}</td>
                        <td>
                            @if($resource->controller_action)
                                <code style="font-size: 12px;">{{ $resource->controller_action }}</code>
                            @else
                                <span style="color: var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($resource->is_public)
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
                            <div class="actions">
                                <a href="{{ route('acl.resources.edit', $resource->id) }}" class="btn btn-secondary btn-sm">{{ __('acl::common.edit') }}</a>
                                <form action="{{ route('acl.resources.destroy', $resource->id) }}" method="POST" class="inline-form" data-confirm="{{ __('acl::common.confirm_delete') }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('acl::common.delete') }}</button>
                                </form>
                            </div>
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
