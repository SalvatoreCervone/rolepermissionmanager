@extends('acl::layouts.app')
@section('title', 'Edit Permission: ' . $permission->name . ' — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Edit Permission: {{ $permission->name }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.permissions.index') }}" style="color: var(--accent); text-decoration: none;">Permissions</a> / Edit</div>
    </div>
</div>
<form action="{{ route('acl.permissions.update', $permission->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>Permission Details</h3></div>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $permission->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $permission->slug) }}" required>
        </div>
        <div class="form-group">
            <label for="module">Module</label>
            <input type="text" id="module" name="module" class="form-control" value="{{ old('module', $permission->module) }}" list="module-list">
            <datalist id="module-list">
                @foreach($modules as $m)
                    <option value="{{ $m }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control">{{ old('description', $permission->description) }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>

{{-- Linked Roles (read-only info) --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header"><h3>👥 Linked Roles</h3></div>
    @if($permission->roles->isEmpty())
        <p style="color: var(--text-muted); font-size: 14px;">This permission is not assigned to any role.</p>
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
    <div class="card-header"><h3>🛤️ Linked Resources</h3></div>
    @if($permission->securedResources->isEmpty())
        <p style="color: var(--text-muted); font-size: 14px;">This permission is not linked to any route.</p>
    @else
        <div class="table-container">
            <table>
                <thead><tr><th>Method</th><th>Identifier</th><th>URI</th></tr></thead>
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
