@extends('acl::layouts.app')
@section('title', __('acl::roles.create_title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::roles.create_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.roles.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::roles.title') }}</a> / {{ __('acl::common.create') }}</div>
    </div>
</div>
<div class="card">
    <form action="{{ route('acl.roles.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">{{ __('acl::roles.name') }}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('acl::roles.name_placeholder') }}" required>
        </div>
        <div class="form-group">
            <label for="slug">{{ __('acl::roles.slug') }}</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('acl::roles.slug_placeholder') }}" required>
        </div>
        <div class="form-group">
            <label for="description">{{ __('acl::roles.description') }}</label>
            <textarea id="description" name="description" class="form-control" placeholder="{{ __('acl::roles.desc_placeholder') }}">{{ old('description') }}</textarea>
        </div>
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">{{ __('acl::roles.create_title') }}</button>
            <a href="{{ route('acl.roles.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
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
