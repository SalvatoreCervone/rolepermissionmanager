@extends('acl::layouts.app')
@section('title', __('acl::permissions.create_title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::permissions.create_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.permissions.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::permissions.title') }}</a> / {{ __('acl::common.create') }}</div>
    </div>
</div>
<div class="card">
    <form action="{{ route('acl.permissions.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">{{ __('acl::permissions.name') }}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('acl::permissions.name_placeholder') }}" required>
        </div>
        <div class="form-group">
            <label for="slug">{{ __('acl::permissions.slug') }}</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('acl::permissions.slug_placeholder') }}" required>
        </div>
        <div class="form-group">
            <label for="module">{{ __('acl::permissions.module') }}</label>
            <input type="text" id="module" name="module" class="form-control" value="{{ old('module') }}" placeholder="{{ __('acl::permissions.module_placeholder') }}" list="module-list">
            <datalist id="module-list">
                @foreach($modules as $m)
                    <option value="{{ $m }}">
                @endforeach
            </datalist>
        </div>
        <div class="form-group">
            <label for="description">{{ __('acl::permissions.description') }}</label>
            <textarea id="description" name="description" class="form-control" placeholder="{{ __('acl::permissions.desc_placeholder') }}">{{ old('description') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">{{ __('acl::permissions.create_title') }}</button>
            <a href="{{ route('acl.permissions.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
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
