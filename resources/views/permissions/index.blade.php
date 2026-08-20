@extends('acl::layouts.app')
@section('title', 'Permissions — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Permissions</h2>
        <div class="breadcrumb">Manage granular permissions for your application</div>
    </div>
    <a href="{{ route('acl.permissions.create') }}" class="btn btn-primary">+ New Permission</a>
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <select name="module" class="form-control" onchange="this.form.submit()">
            <option value="">All Modules</option>
            @foreach($modules as $module)
                <option value="{{ $module }}" {{ $moduleFilter === $module ? 'selected' : '' }}>{{ $module }}</option>
            @endforeach
        </select>
        @if($moduleFilter)
            <a href="{{ route('acl.permissions.index') }}" class="btn btn-secondary btn-sm">Clear Filter</a>
        @endif
    </form>

    @if($permissions->isEmpty())
        <div class="empty-state">
            <div class="icon">🔑</div>
            <p>No permissions found. Create your first permission or run <code>acl:sync --auto-permissions</code>.</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Module</th>
                        <th>Roles</th>
                        <th>Resources</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $permission)
                    <tr>
                        <td style="font-weight: 600;">{{ $permission->name }}</td>
                        <td><code>{{ $permission->slug }}</code></td>
                        <td>
                            @if($permission->module)
                                <span class="badge badge-module">{{ $permission->module }}</span>
                            @else
                                <span style="color: var(--text-muted);">—</span>
                            @endif
                        </td>
                        <td><span class="chip">{{ $permission->roles_count }}</span></td>
                        <td><span class="chip">{{ $permission->secured_resources_count }}</span></td>
                        <td class="actions">
                            <a href="{{ route('acl.permissions.edit', $permission->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="{{ route('acl.permissions.destroy', $permission->id) }}" method="POST" class="inline-form" data-confirm="Are you sure you want to delete the permission '{{ $permission->name }}'?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $permissions->links() }}
    @endif
</div>
@endsection
