@extends('acl::layouts.app')
@section('title', __('acl::scanner.title') . ' — ' . config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))
@section('content')
<div class="page-header">
    <div>
        <h2>⚙️ {{ __('acl::scanner.title') }}</h2>
        <div class="breadcrumb">{{ __('acl::scanner.subtitle') }}</div>
    </div>
</div>

{{-- Add Rule Form --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3>➕ {{ __('acl::scanner.add_rule') }}</h3>
    </div>
    <form action="{{ route('acl.scanner_rules.store') }}" method="POST">
        @csrf
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="type">{{ __('acl::scanner.type') }}</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="exclude">{{ __('acl::scanner.type_exclude') }}</option>
                    <option value="include">{{ __('acl::scanner.type_include') }}</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="target">{{ __('acl::scanner.target') }}</label>
                <select id="target" name="target" class="form-control" required>
                    <option value="name">{{ __('acl::scanner.target_name') }}</option>
                    <option value="prefix">{{ __('acl::scanner.target_prefix') }}</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="pattern">{{ __('acl::scanner.pattern') }}</label>
                <input type="text"
                       id="pattern"
                       name="pattern"
                       class="form-control"
                       value="{{ old('pattern') }}"
                       placeholder="{{ __('acl::scanner.pattern_placeholder') }}"
                       required>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label for="description">{{ __('acl::scanner.description') }}</label>
                <input type="text"
                       id="description"
                       name="description"
                       class="form-control"
                       value="{{ old('description') }}"
                       placeholder="{{ __('acl::scanner.desc_placeholder') }}">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-sm">
            ➕ {{ __('acl::scanner.save_rule') }}
        </button>
    </form>
</div>

{{-- Custom Dynamic Rules Table --}}
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <div>
            <h3>📋 {{ __('acl::scanner.custom_rules') }}</h3>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                {{ __('acl::scanner.custom_rules_help') }}
            </div>
        </div>
        <span class="chip">{{ $rules->count() }} {{ __('acl::common.selected') }}</span>
    </div>

    @if($rules->isEmpty())
        <div class="empty-state">
            <div class="icon">⚙️</div>
            <p>{{ __('acl::scanner.no_rules_found') }}</p>
        </div>
    @else
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('acl::scanner.type') }}</th>
                        <th>{{ __('acl::scanner.target') }}</th>
                        <th>{{ __('acl::scanner.pattern') }}</th>
                        <th>{{ __('acl::scanner.description') }}</th>
                        <th>{{ __('acl::scanner.status') }}</th>
                        <th>{{ __('acl::common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rules as $rule)
                    <tr style="{{ !$rule->is_active ? 'opacity: 0.5;' : '' }}">
                        <td>
                            @if($rule->type === 'include')
                                <span class="badge badge-get">✅ Include</span>
                            @else
                                <span class="badge badge-delete">🚫 Exclude</span>
                            @endif
                        </td>
                        <td>
                            @if($rule->target === 'name')
                                <span class="chip">🏷️ Route Name</span>
                            @else
                                <span class="chip">🛤️ URI Prefix</span>
                            @endif
                        </td>
                        <td><code>{{ $rule->pattern }}</code></td>
                        <td style="color: var(--text-secondary);">{{ $rule->description ?? '—' }}</td>
                        <td>
                            @if($rule->is_active)
                                <span class="badge badge-protected">{{ __('acl::scanner.active') }}</span>
                            @else
                                <span class="badge badge-deprecated">{{ __('acl::scanner.inactive') }}</span>
                            @endif
                        </td>
                        <td class="actions">
                            <form action="{{ route('acl.scanner_rules.toggle', $rule->id) }}" method="POST" class="inline-form">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-secondary btn-sm" title="{{ __('acl::scanner.toggle') }}">
                                    {{ $rule->is_active ? '⏸️ ' . __('acl::scanner.inactive') : '▶️ ' . __('acl::scanner.active') }}
                                </button>
                            </form>
                            <form action="{{ route('acl.scanner_rules.destroy', $rule->id) }}" method="POST" class="inline-form" data-confirm="{{ __('acl::scanner.delete_confirm', ['pattern' => $rule->pattern]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('acl::common.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- System Defaults Summary (Config) --}}
<div class="card">
    <div class="card-header">
        <div>
            <h3>🔒 {{ __('acl::scanner.system_defaults') }}</h3>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                {{ __('acl::scanner.system_defaults_help') }}
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h4 style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                🛤️ Excluded URI Prefixes (Config)
            </h4>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach($configExcludedPrefixes as $cp)
                    <span class="chip" style="font-family: monospace;">{{ $cp }}</span>
                @endforeach
            </div>
        </div>

        <div>
            <h4 style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px;">
                🏷️ Excluded Route Names (Config)
            </h4>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                @foreach($configExcludedNames as $cn)
                    <span class="chip" style="font-family: monospace;">{{ $cn }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
