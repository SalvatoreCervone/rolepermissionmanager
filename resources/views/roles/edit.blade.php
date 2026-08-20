@extends('acl::layouts.app')
@section('title', __('acl::roles.edit_title', ['name' => $role->name]) . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::roles.edit_title', ['name' => $role->name]) }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.roles.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::roles.title') }}</a> / {{ __('acl::common.edit') }}</div>
    </div>
</div>

<form action="{{ route('acl.roles.update', $role->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>{{ __('acl::roles.role_details') }}</h3></div>
        <div class="form-group">
            <label for="name">{{ __('acl::roles.name') }}</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $role->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">{{ __('acl::roles.slug') }}</label>
            <input type="text" id="slug" name="slug" class="form-control" value="{{ old('slug', $role->slug) }}" required>
        </div>
        <div class="form-group">
            <label for="description">{{ __('acl::roles.description') }}</label>
            <textarea id="description" name="description" class="form-control">{{ old('description', $role->description) }}</textarea>
        </div>
    </div>

    {{-- Assign Permissions --}}
    @include('acl::partials.permission-picker', [
        'allPermissions'      => $allPermissions,
        'selectedPermissions' => old('permissions', $role->permissions->pluck('id')->all()),
        'title'               => __('acl::roles.assign_permissions'),
    ])

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save') }}</button>
        <a href="{{ route('acl.roles.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
