@extends('acl::layouts.app')
@section('title', __('acl::roles.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::roles.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::roles.subtitle') }}</div>
    </div>
    <a href="{{ route('acl.roles.create') }}" class="btn btn-primary">{{ __('acl::roles.new_role') }}</a>
</div>
<div class="card">
    @if($roles->isEmpty())
        <div class="empty-state">
            <div class="icon">👥</div>
            <p>{{ __('acl::roles.no_roles_found') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('acl::roles.name') }}</th>
                        <th>{{ __('acl::roles.slug') }}</th>
                        <th>{{ __('acl::roles.description') }}</th>
                        <th>{{ __('acl::roles.permissions') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td style="font-weight: 600;">{{ $role->name }}</td>
                        <td><code>{{ $role->slug }}</code></td>
                        <td style="color: var(--text-secondary);">{{ $role->description ?? '—' }}</td>
                        <td><span class="chip">{{ __('acl::roles.permissions_count', ['count' => $role->permissions_count]) }}</span></td>
                        <td class="actions">
                            <a href="{{ route('acl.roles.edit', $role->id) }}" class="btn btn-secondary btn-sm">{{ __('acl::common.edit') }}</a>
                            <form action="{{ route('acl.roles.destroy', $role->id) }}" method="POST" class="inline-form" data-confirm="{{ __('acl::roles.delete_confirm', ['name' => $role->name]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('acl::common.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $roles->links('acl::pagination') }}
    @endif
</div>
@endsection
