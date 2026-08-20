@extends('acl::layouts.app')
@section('title', 'Create Role — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Create Role</h2>
        <div class="breadcrumb"><a href="{{ route('acl.roles.index') }}" style="color: var(--accent); text-decoration: none;">Roles</a> / Create</div>
    </div>
</div>
<div class="card">
    <form action="{{ route('acl.roles.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Administrator" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="e.g. admin" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" placeholder="Brief description of this role...">{{ old('description') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Create Role</button>
            <a href="{{ route('acl.roles.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    document.getElementById('name').addEventListener('input', function() {
        const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
        document.getElementById('slug').value = slug;
    });
</script>
@endsection
