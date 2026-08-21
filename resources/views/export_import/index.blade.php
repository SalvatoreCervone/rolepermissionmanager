@extends('acl::layouts.app')
@section('title', __('acl::export_import.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::export_import.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::export_import.subtitle') }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    {{-- Export Card --}}
    <div class="card">
        <div class="card-header">
            <h3>📥 {{ __('acl::export_import.export_box_title') }}</h3>
        </div>
        <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px; line-height: 1.6;">
            {{ __('acl::export_import.export_box_desc') }}
        </p>

        <div style="background: var(--bg-body); border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 13px;">
            <div style="font-weight: 600; margin-bottom: 6px;">📦 {{ __('acl::export_import.included_data') }}:</div>
            <ul style="margin: 0; padding-left: 20px; color: var(--text-muted);">
                <li>{{ __('acl::roles.title') }} & {{ __('acl::roles.permissions') }}</li>
                <li>{{ __('acl::permissions.title') }}</li>
                <li>{{ __('acl::routes.title') }} & {{ __('acl::resources.title') }}</li>
                <li>{{ __('acl::nav.scanner_rules') }}</li>
            </ul>
        </div>

        <a href="{{ route('acl.export_import.export') }}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            ⬇️ {{ __('acl::export_import.download_json_btn') }}
        </a>
    </div>

    {{-- Import Card --}}
    <div class="card">
        <div class="card-header">
            <h3>📤 {{ __('acl::export_import.import_box_title') }}</h3>
        </div>
        <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px; line-height: 1.6;">
            {{ __('acl::export_import.import_box_desc') }}
        </p>

        <form action="{{ route('acl.export_import.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">{{ __('acl::export_import.choose_file') }}</label>
                <input type="file" name="file" accept=".json,application/json,text/plain" required class="form-control" style="width: 100%; padding: 8px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer;">
                    <input type="checkbox" name="overwrite" value="1" style="width: 16px; height: 16px;">
                    <span>{{ __('acl::export_import.overwrite_mode') }}</span>
                </label>
                <div style="font-size: 11px; color: var(--text-muted); margin-left: 24px; margin-top: 2px;">
                    {{ __('acl::export_import.overwrite_mode_help') }}
                </div>
            </div>

            <button type="submit" class="btn btn-success" style="display: inline-flex; align-items: center; gap: 8px;" onclick="return confirm('{{ __('acl::export_import.confirm_import') }}')">
                ⬆️ {{ __('acl::export_import.import_btn') }}
            </button>
        </form>
    </div>
</div>
@endsection
