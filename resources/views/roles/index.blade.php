@extends('acl::layouts.app')
@section('title', 'Roles — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Roles</h2>
        <div class="breadcrumb">Manage user roles and their associated permissions</div>
    </div>
    <a href="{{ route('acl.roles.create') }}" class="btn btn-primary">+ New Role</a>
</div>
<div class="card">
    @if($roles->isEmpty())
        <div class="empty-state">
            <div class="icon">👥</div>
            <p>No roles found. Create your first role to get started.</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td style="font-weight: 600;">{{ $role->name }}</td>
                        <td><code>{{ $role->slug }}</code></td>
                        <td style="color: var(--text-secondary);">{{ $role->description ?? '—' }}</td>
                        <td><span class="chip">{{ $role->permissions_count }} permissions</span></td>
                        <td class="actions">
                            <a href="{{ route('acl.roles.edit', $role->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <form action="{{ route('acl.roles.destroy', $role->id) }}" method="POST" class="inline-form" data-confirm="Are you sure you want to delete the role '{{ $role->name }}'?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $roles->links() }}
    @endif
</div>
@endsection
