@extends('acl::layouts.app')
@section('title', __('acl::permissions.edit_title', ['name' => $permission->name]) . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::permissions.edit_title', ['name' => $permission->name]) }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.permissions.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::permissions.title') }}</a> / {{ __('acl::common.edit') }}</div>
    </div>
</div>
<form action="{{ route('acl.permissions.update', $permission->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>{{ __('acl::permissions.details') }}</h3></div>
        <div class="form-group">
            <label for="name">{{ __('acl::permissions.name') }}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $permission->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">{{ __('acl::permissions.slug') }}</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $permission->slug) }}" required>
        </div>
        <div class="form-group">
            <label for="module">{{ __('acl::permissions.module') }}</label>
            <input type="text" id="module" name="module" class="form-control" value="{{ old('module', $permission->module) }}" list="module-list">
            <datalist id="module-list">
                @foreach($modules as $m)
                    <option value="{{ $m }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group">
            <label for="description">{{ __('acl::permissions.description') }}</label>
            <textarea id="description" name="description" class="form-control">{{ old('description', $permission->description) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save') }}</button>
    </div>
</form>

{{-- Linked Roles (read-only info) --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header"><h3>👥 {{ __('acl::permissions.linked_roles') }}</h3></div>
    @if($permission->roles->isEmpty())
        <p style="color: var(--text-muted); font-size: 14px;">{{ __('acl::permissions.no_roles_linked') }}</p>
    @else
        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            @foreach($permission->roles as $role)
                <a href="{{ route('acl.roles.edit', $role->id) }}" class="chip" style="text-decoration: none;">{{ $role->name }}</a>
            @endforeach
        </div>
    @endif
</div>

{{-- Linked Resources (read-only info) --}}
<div class="card">
    <div class="card-header"><h3>🛤️ {{ __('acl::permissions.linked_resources') }}</h3></div>
    @if($permission->securedResources->isEmpty())
        <p style="color: var(--text-muted); font-size: 14px;">{{ __('acl::permissions.no_resources_linked') }}</p>
    @else
        <div class="table-container">
            <table>
                <thead><tr><th>{{ __('acl::resources.method') }}</th><th>{{ __('acl::resources.identifier') }}</th><th>{{ __('acl::resources.uri') }}</th></tr></thead>
                <tbody>
                    @foreach($permission->securedResources as $resource)
                    <tr>
                        <td><span class="badge badge-{{ strtolower($resource->method) }}">{{ $resource->method }}</span></td>
                        <td><code>{{ $resource->identifier }}</code></td>
                        <td><code>{{ $resource->uri }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
