@extends('acl::layouts.app')
@section('title', 'Create Permission — ACL Manager')
@section('content')
<div class="page-header">
    <div>
        <h2>Create Permission</h2>
        <div class="breadcrumb"><a href="{{ route('acl.permissions.index') }}" style="color: var(--accent); text-decoration: none;">Permissions</a> / Create</div>
    </div>
</div>
<div class="card">
    <form action="{{ route('acl.permissions.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Delete Users" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="e.g. users.delete" required>
        </div>
        <div class="form-group">
            <label for="module">Module</label>
            <input type="text" id="module" name="module" class="form-control" value="{{ old('module') }}" placeholder="e.g. Users" list="module-list">
            <datalist id="module-list">
                @foreach($modules as $m)
                    <option value="{{ $m }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" placeholder="What does this permission allow?">{{ old('description') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Create Permission</button>
            <a href="{{ route('acl.permissions.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
    document.getElementById('name').addEventListener('input', function() {
        const parts = this.value.toLowerCase().split(' ');
        if (parts.length >= 2) {
            document.getElementById('slug').value = parts.slice(1).join('-') + '.' + parts[0];
        } else {
            document.getElementById('slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '.');
        }
        // Auto-fill module from first word
        if (parts.length >= 1 && parts[0]) {
            document.getElementById('module').value = parts[parts.length - 1].charAt(0).toUpperCase() + parts[parts.length - 1].slice(1);
        }
    });
</script>
@endsection
