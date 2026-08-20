@extends('acl::layouts.app')
@section('title', __('acl::resources.create_title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::resources.create_title') }}</h2>
        <div class="breadcrumb"><a href="{{ route('acl.resources.index') }}" style="color: var(--accent); text-decoration: none;">{{ __('acl::resources.title') }}</a> / {{ __('acl::common.new') }}</div>
    </div>
</div>

<form action="{{ route('acl.resources.store') }}" method="POST">
    @csrf

    {{-- Resource Details --}}
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header"><h3>📦 {{ __('acl::resources.resource_details') }}</h3></div>

        <div class="form-group">
            <label for="identifier">{{ __('acl::resources.identifier') }} *</label>
            <input type="text" id="identifier" name="identifier" class="form-control" value="{{ old('identifier') }}"
                placeholder="es. CorsoController@dettagliocorsi, App\Services\PaymentService@charge, button.export_excel" required autofocus>
            <small style="color: var(--text-muted); display: block; margin-top: 4px;">{{ __('acl::resources.identifier_help') }}</small>
        </div>

        <div class="form-group">
            <label for="description">{{ __('acl::resources.description') }}</label>
            <input type="text" id="description" name="description" class="form-control" value="{{ old('description') }}"
                placeholder="es. Visualizzazione dettagli corsi interni">
        </div>

        <div class="form-group">
            <label for="controller_action">{{ __('acl::resources.controller_action') }} ({{ __('acl::common.optional') }})</label>
            <input type="text" id="controller_action" name="controller_action" class="form-control" value="{{ old('controller_action') }}"
                placeholder="es. App\Http\Controllers\CorsoController@dettagliocorsi">
        </div>
    </div>

    {{-- Access Settings --}}
    @include('acl::partials.access-settings')

    {{-- Permission Assignment --}}
    @include('acl::partials.permission-picker', [
        'allPermissions'      => $allPermissions,
        'selectedPermissions' => old('permissions', []),
        'title'               => __('acl::resources.required_permissions'),
    ])

    <div style="display: flex; gap: 12px;">
        <button type="submit" class="btn btn-primary">{{ __('acl::common.save') }}</button>
        <a href="{{ route('acl.resources.index') }}" class="btn btn-secondary">{{ __('acl::common.cancel') }}</a>
    </div>
</form>
@endsection
