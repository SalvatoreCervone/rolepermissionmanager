@extends('acl::layouts.app')
@section('title', __('acl::routes.configure_title') . ': ' . $resource->identifier . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::routes.configure_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.routes.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::routes.title') }}</a> / {{ $resource->identifier }}</div>
    </div>
</div>

<form action="{{ route('acl.routes.update', $resource->id) }}" method="POST">
    @csrf @method('PUT')

    {{-- Route Info (read-only) --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>🛤️ {{ __('acl::routes.route_info') }}</h3></div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.identifier') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->identifier }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.method') }}</label>
                <div style="margin-top: 4px;"><span class="badge badge-{{ strtolower($resource->method ?? 'get') }}">{{ $resource->method }}</span></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.uri') }}</label>
                <div style="margin-top: 4px;"><code>{{ $resource->uri }}</code></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.controller_action') }}</label>
                <div style="margin-top: 4px;"><code style="font-size: 12px;">{{ $resource->controller_action }}</code></div>
            </div>
            @if($resource->source_file)
            <div style="grid-column: span 2;">
                <label style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('acl::routes.source_file') }}</label>
                <div style="margin-top: 4px;"><span class="badge" style="background: var(--bg-primary); border: 1px solid var(--border); color: var(--info); font-size: 12px; font-family: monospace;">📄 {{ $resource->source_file }}</span></div>
            </div>
            @endif
        </div>
    </div>

    {{-- Access Settings --}}
    @include('acl::partials.access-settings', ['resource' => $resource])

    {{-- Permission Assignment --}}
    @include('acl::partials.permission-picker', [
        'allPermissions'      => $allPermissions,
        'selectedPermissions' => old('permissions', $resource->permissions->pluck('id')->all()),
        'title'               => __('acl::routes.required_permissions'),
    ])

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save_config') }}</button>
        <a href="{{ route('acl.routes.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
