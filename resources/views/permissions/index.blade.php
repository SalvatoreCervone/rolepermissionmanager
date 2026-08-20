@extends('acl::layouts.app')
@section('title', __('acl::permissions.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::permissions.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::permissions.subtitle') }}</div>
    </div>
    <a href="{{ route('acl.permissions.create') }}" class="btn btn-primary">{{ __('acl::permissions.new_permission') }}</a>
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <select name="module" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::permissions.all_modules') }}</option>
            @foreach($modules as $module)
                <option value="{{ $module }}" {{ $moduleFilter === $module ? 'selected' : '' }}>{{ $module }}</option>
            @endforeach
        </select>
        @if($moduleFilter)
            <a href="{{ route('acl.permissions.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear_filter') }}</a>
        @endif
    </form>

    @if($permissions->isEmpty())
        <div class="empty-state">
            <div class="icon">🔑</div>
            <p>{{ __('acl::permissions.no_perms_found') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('acl::permissions.name') }}</th>
                        <th>{{ __('acl::permissions.slug') }}</th>
                        <th>{{ __('acl::permissions.module') }}</th>
                        <th>{{ __('acl::permissions.roles') }}</th>
                        <th>{{ __('acl::permissions.resources') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
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
                            <a href="{{ route('acl.permissions.edit', $permission->id) }}" class="btn btn-secondary btn-sm">{{ __('acl::common.edit') }}</a>
                            <form action="{{ route('acl.permissions.destroy', $permission->id) }}" method="POST" class="inline-form" data-confirm="{{ __('acl::permissions.delete_confirm', ['name' => $permission->name]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('acl::common.delete') }}</button>
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
