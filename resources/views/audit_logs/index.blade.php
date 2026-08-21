@extends('acl::layouts.app')
@section('title', __('acl::audit_logs.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>{{ __('acl::audit_logs.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::audit_logs.subtitle') }}</div>
    </div>
    @if(!$logs->isEmpty())
    <form action="{{ route('acl.audit_logs.clear') }}" method="POST" onsubmit="return confirm('{{ __('acl::audit_logs.confirm_clear') }}');">
        @csrf
        <button type="submit" class="btn btn-danger btn-sm">🗑️ {{ __('acl::audit_logs.clear_btn') }}</button>
    </form>
    @endif
</div>

<div class="card">
    <form method="GET" class="filter-bar">
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('acl::common.search') }}...">
        <select name="action" class="form-control" onchange="this.form.submit()">
            <option value="">{{ __('acl::audit_logs.all_actions') }}</option>
            @foreach($actions as $act)
                <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">{{ __('acl::common.filter') }}</button>
        @if(request()->hasAny(['search', 'action']))
            <a href="{{ route('acl.audit_logs.index') }}" class="btn btn-secondary btn-sm">{{ __('acl::common.clear') }}</a>
        @endif
    </form>

    @if($logs->isEmpty())
        <div class="empty-state">
            <div class="icon">📜</div>
            <p>{{ __('acl::audit_logs.no_logs_found') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('acl::audit_logs.timestamp') }}</th>
                        <th>{{ __('acl::audit_logs.user') }}</th>
                        <th>{{ __('acl::audit_logs.action') }}</th>
                        <th>{{ __('acl::audit_logs.target') }}</th>
                        <th>{{ __('acl::audit_logs.details') }}</th>
                        <th>{{ __('acl::audit_logs.ip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td style="white-space: nowrap; font-size: 12px; color: var(--text-muted);">
                            {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '—' }}
                        </td>
                        <td style="font-weight: 600;">
                            {{ $log->user_name ?: 'System' }}
                        </td>
                        <td>
                            <span class="badge" style="background: var(--bg-body); color: var(--text); border: 1px solid var(--border); font-size: 11px;">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            @if($log->target_type)
                                <span style="font-size: 11px; color: var(--text-muted);">[{{ $log->target_type }}]</span>
                            @endif
                            <code>{{ $log->target_identifier ?: '—' }}</code>
                        </td>
                        <td style="font-size: 13px; color: var(--text-secondary);">
                            {{ $log->details ?: '—' }}
                        </td>
                        <td>
                            <code style="font-size: 11px;">{{ $log->ip_address ?: '—' }}</code>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $logs->links('acl::pagination') }}
    @endif
</div>
@endsection
