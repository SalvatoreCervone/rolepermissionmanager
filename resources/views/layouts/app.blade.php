<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager'))</title>
    <script>
        if (localStorage.getItem('acl_sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 68px;
            --bg-primary: #0f111a;
            --bg-secondary: #1a1d2e;
            --bg-card: #1e2235;
            --bg-input: #252940;
            --border: #2a2e45;
            --text-primary: #e4e6f0;
            --text-secondary: #8b8fa8;
            --text-muted: #5c6078;
            --accent: #6c5ce7;
            --accent-hover: #7c6ef7;
            --accent-subtle: rgba(108, 92, 231, 0.15);
            --success: #00b894;
            --success-subtle: rgba(0, 184, 148, 0.15);
            --warning: #fdcb6e;
            --warning-subtle: rgba(253, 203, 110, 0.15);
            --danger: #e17055;
            --danger-subtle: rgba(225, 112, 85, 0.15);
            --info: #74b9ff;
            --info-subtle: rgba(116, 185, 255, 0.15);
            --radius: 10px;
            --radius-sm: 6px;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.25);
            --transition: 0.2s ease;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Layout */
        .layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 100;
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 20px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            min-height: 73px;
        }
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 0;
            overflow: hidden;
        }
        .sidebar-brand .brand-icon {
            font-size: 22px;
            flex-shrink: 0;
            display: inline-block;
        }
        .sidebar-brand .brand-text {
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-header h1 {
            font-size: 17px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent), var(--info));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-header .subtitle {
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 2px;
            white-space: nowrap;
        }
        .sidebar-toggle-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            border-radius: var(--radius-sm);
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition);
            flex-shrink: 0;
            padding: 0;
        }
        .sidebar-toggle-btn:hover {
            background: var(--accent-subtle);
            border-color: var(--accent);
            color: var(--accent);
        }
        .sidebar-toggle-btn svg {
            transition: transform 0.25s ease;
        }
        .sidebar-nav {
            padding: 16px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .sidebar-nav .nav-section {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            padding: 16px 12px 8px;
            font-weight: 600;
            white-space: nowrap;
            transition: opacity 0.2s ease, margin 0.2s ease;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            transition: all var(--transition);
            white-space: nowrap;
            position: relative;
        }
        .sidebar-nav a:hover {
            background: var(--accent-subtle);
            color: var(--text-primary);
        }
        .sidebar-nav a.active {
            background: var(--accent-subtle);
            color: var(--accent);
            font-weight: 600;
        }
        .sidebar-nav a .icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }
        .sidebar-nav a .nav-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: opacity 0.2s ease;
        }
        .sidebar-nav .sync-form {
            margin-top: 8px;
            padding: 0;
        }
        .sidebar-nav .sync-form button {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Main content */
        .main {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 32px;
            transition: margin-left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            min-width: 0;
        }

        /* Collapsed Sidebar Styles */
        html.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapsed-width);
        }
        html.sidebar-collapsed .sidebar-header {
            padding: 16px 8px;
            justify-content: center;
            flex-direction: column;
            gap: 12px;
        }
        html.sidebar-collapsed .sidebar-brand {
            justify-content: center;
        }
        html.sidebar-collapsed .sidebar-brand .brand-text {
            display: none;
        }
        html.sidebar-collapsed .sidebar-brand .brand-icon {
            font-size: 24px;
        }
        html.sidebar-collapsed .sidebar-toggle-btn svg {
            transform: rotate(180deg);
        }
        html.sidebar-collapsed .sidebar-nav {
            padding: 12px 8px;
        }
        html.sidebar-collapsed .sidebar-nav .nav-section {
            height: 1px;
            padding: 0;
            margin: 12px 6px;
            background: var(--border);
            overflow: hidden;
            font-size: 0;
            text-indent: -9999px;
        }
        html.sidebar-collapsed .sidebar-nav a {
            padding: 10px 0;
            justify-content: center;
            gap: 0;
        }
        html.sidebar-collapsed .sidebar-nav a .nav-text {
            display: none;
        }
        html.sidebar-collapsed .sidebar-nav .sync-form {
            padding: 0;
        }
        html.sidebar-collapsed .sidebar-nav .sync-form button {
            width: 44px;
            height: 36px;
            margin: 0 auto;
            padding: 0;
            font-size: 16px;
        }
        html.sidebar-collapsed .sidebar-nav .sync-form .sync-text {
            display: none;
        }
        html.sidebar-collapsed .main {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Page header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }
        .page-header h2 {
            font-size: 24px;
            font-weight: 700;
        }
        .page-header .breadcrumb {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Cards */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-header h3 { font-size: 16px; font-weight: 600; }

        /* Stat cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: all var(--transition);
        }
        .stat-card:hover { transform: translateY(-2px); border-color: var(--accent); }
        .stat-card .stat-icon { font-size: 28px; margin-bottom: 12px; }
        .stat-card .stat-value { font-size: 32px; font-weight: 700; }
        .stat-card .stat-label { font-size: 13px; color: var(--text-secondary); margin-top: 4px; }

        /* Tables */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            font-weight: 600;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }
        tr:hover td { background: rgba(108, 92, 231, 0.04); }
        td code {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 13px;
            background: var(--bg-input);
            padding: 2px 8px;
            border-radius: 4px;
            color: var(--info);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-get { background: var(--success-subtle); color: var(--success); }
        .badge-post { background: var(--warning-subtle); color: var(--warning); }
        .badge-put, .badge-patch { background: var(--info-subtle); color: var(--info); }
        .badge-delete { background: var(--danger-subtle); color: var(--danger); }
        .badge-public { background: var(--success-subtle); color: var(--success); }
        .badge-protected { background: var(--accent-subtle); color: var(--accent); }
        .badge-deprecated { background: var(--danger-subtle); color: var(--danger); }
        .badge-or { background: var(--info-subtle); color: var(--info); }
        .badge-and { background: var(--warning-subtle); color: var(--warning); }
        .badge-module { background: var(--bg-input); color: var(--text-secondary); }

        /* Tag chips */
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            background: var(--accent-subtle);
            color: var(--accent);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin: 2px 4px 2px 0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover { border-color: var(--accent); }
        .btn-danger {
            background: var(--danger-subtle);
            color: var(--danger);
            border: 1px solid transparent;
        }
        .btn-danger:hover { border-color: var(--danger); }
        .btn-success {
            background: var(--success-subtle);
            color: var(--success);
            border: 1px solid transparent;
        }
        .btn-success:hover { border-color: var(--success); }
        .btn-sm { padding: 6px 12px; font-size: 13px; }

        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: border-color var(--transition);
        }
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-subtle);
        }
        textarea.form-control { resize: vertical; min-height: 80px; }
        select.form-control { cursor: pointer; }

        /* Checkbox grid */
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 8px;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all var(--transition);
        }
        .checkbox-item:hover { border-color: var(--accent); }
        .checkbox-item.checked { border-color: var(--accent); background: var(--accent-subtle); }
        .checkbox-item input[type="checkbox"] {
            accent-color: var(--accent);
            width: 16px;
            height: 16px;
        }
        .checkbox-item .cb-label { font-size: 13px; font-weight: 500; }
        .checkbox-item .cb-slug {
            font-size: 11px;
            color: var(--text-muted);
            font-family: monospace;
        }

        /* Module section */
        .module-section { margin-bottom: 20px; }
        .module-section h4 {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid var(--border);
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: var(--success-subtle); color: var(--success); border: 1px solid rgba(0, 184, 148, 0.3); }
        .alert-danger { background: var(--danger-subtle); color: var(--danger); border: 1px solid rgba(225, 112, 85, 0.3); }

        /* Toggle switch */
        .toggle-container { display: flex; align-items: center; gap: 12px; }
        .toggle {
            position: relative;
            width: 48px;
            height: 26px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 13px;
            cursor: pointer;
            transition: all var(--transition);
        }
        .toggle.active { background: var(--accent); border-color: var(--accent); }
        .toggle::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            transition: all var(--transition);
        }
        .toggle.active::after { left: 25px; }
        .toggle-label { font-size: 14px; font-weight: 500; }

        /* Filter bar */
        .filter-bar {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-bar .form-control { width: auto; min-width: 160px; }
        .filter-bar input[type="text"].form-control { min-width: 260px; }

        /* Pagination & SVG Containment */
        svg {
            max-width: 100%;
            height: auto;
        }
        nav[role="navigation"] svg {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            display: inline-block;
            vertical-align: middle;
        }

        .custom-pagination-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        .pagination-left {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        .pagination-summary {
            font-size: 13px;
            color: var(--text-muted);
        }
        .pagination-summary .fw-bold {
            font-weight: 600;
            color: var(--text-primary);
        }
        .pagination-per-page {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .per-page-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .per-page-select {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: var(--radius-sm);
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            outline: none;
            transition: all var(--transition);
        }
        .per-page-select:focus, .per-page-select:hover {
            border-color: var(--accent);
            background: var(--bg-input);
        }
        .pagination-list {
            display: flex;
            align-items: center;
            gap: 6px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .page-item .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            height: 34px;
            padding: 0 10px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            background: var(--bg-card);
            transition: all var(--transition);
        }
        .page-item:not(.disabled):not(.active) .page-link:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--bg-card-hover);
        }
        .page-item.active .page-link {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        .page-item.disabled .page-link {
            opacity: 0.35;
            cursor: not-allowed;
            background: var(--bg-primary);
        }

        /* Sync output */
        .sync-output {
            background: var(--bg-primary);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 16px;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 13px;
            white-space: pre-wrap;
            color: var(--success);
            max-height: 300px;
            overflow-y: auto;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state p { font-size: 15px; }

        /* Inline delete form */
        .inline-form { display: inline; }

        /* Actions cell */
        .actions { display: flex; gap: 8px; align-items: center; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
<div class="layout">
    {{-- Sidebar --}}
    <aside class="sidebar" id="appSidebar">
        <div class="sidebar-header">
            <a href="{{ route('acl.dashboard') }}" class="sidebar-brand">
                <span class="brand-icon">🛡️</span>
                <div class="brand-text">
                    <h1>{{ config('rolepermissionmanager.admin_panel.page_title', 'ACL Manager') }}</h1>
                    <div class="subtitle">Role & Permission Manager</div>
                </div>
            </a>
            <button type="button" id="sidebarToggle" class="sidebar-toggle-btn" title="Comprimi / Espandi menu" aria-label="Toggle Sidebar">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">{{ __('acl::nav.overview') }}</div>
            <a href="{{ route('acl.dashboard') }}" class="{{ request()->routeIs('acl.dashboard') ? 'active' : '' }}" title="{{ __('acl::nav.dashboard') }}">
                <span class="icon">📊</span>
                <span class="nav-text">{{ __('acl::nav.dashboard') }}</span>
            </a>

            <div class="nav-section">{{ __('acl::nav.manage') }}</div>
            <a href="{{ route('acl.users.index') }}" class="{{ request()->routeIs('acl.users.*') ? 'active' : '' }}" title="{{ __('acl::nav.users') }}">
                <span class="icon">👤</span>
                <span class="nav-text">{{ __('acl::nav.users') }}</span>
            </a>
            <a href="{{ route('acl.roles.index') }}" class="{{ request()->routeIs('acl.roles.*') ? 'active' : '' }}" title="{{ __('acl::nav.roles') }}">
                <span class="icon">👥</span>
                <span class="nav-text">{{ __('acl::nav.roles') }}</span>
            </a>
            <a href="{{ route('acl.permissions.index') }}" class="{{ request()->routeIs('acl.permissions.*') ? 'active' : '' }}" title="{{ __('acl::nav.permissions') }}">
                <span class="icon">🔑</span>
                <span class="nav-text">{{ __('acl::nav.permissions') }}</span>
            </a>
            <a href="{{ route('acl.matrix.index') }}" class="{{ request()->routeIs('acl.matrix.*') ? 'active' : '' }}" title="{{ __('acl::nav.matrix') }}">
                <span class="icon">🔲</span>
                <span class="nav-text">{{ __('acl::nav.matrix') }}</span>
            </a>
            <a href="{{ route('acl.routes.index') }}" class="{{ request()->routeIs('acl.routes.*') ? 'active' : '' }}" title="{{ __('acl::nav.routes') }}">
                <span class="icon">🛤️</span>
                <span class="nav-text">{{ __('acl::nav.routes') }}</span>
            </a>
            <a href="{{ route('acl.resources.index') }}" class="{{ request()->routeIs('acl.resources.*') ? 'active' : '' }}" title="{{ __('acl::nav.resources') }}">
                <span class="icon">📦</span>
                <span class="nav-text">{{ __('acl::nav.resources') }}</span>
            </a>

            <div class="nav-section">{{ __('acl::nav.tools') }}</div>
            <a href="{{ route('acl.simulator.index') }}" class="{{ request()->routeIs('acl.simulator.*') ? 'active' : '' }}" title="{{ __('acl::nav.simulator') }}">
                <span class="icon">🔍</span>
                <span class="nav-text">{{ __('acl::nav.simulator') }}</span>
            </a>
            <a href="{{ route('acl.scanner_rules.index') }}" class="{{ request()->routeIs('acl.scanner_rules.*') ? 'active' : '' }}" title="{{ __('acl::nav.scanner_rules') }}">
                <span class="icon">⚙️</span>
                <span class="nav-text">{{ __('acl::nav.scanner_rules') }}</span>
            </a>
            <a href="{{ route('acl.export_import.index') }}" class="{{ request()->routeIs('acl.export_import.*') ? 'active' : '' }}" title="{{ __('acl::nav.export_import') }}">
                <span class="icon">💾</span>
                <span class="nav-text">{{ __('acl::nav.export_import') }}</span>
            </a>
            <a href="{{ route('acl.audit_logs.index') }}" class="{{ request()->routeIs('acl.audit_logs.*') ? 'active' : '' }}" title="{{ __('acl::nav.audit_logs') }}">
                <span class="icon">📜</span>
                <span class="nav-text">{{ __('acl::nav.audit_logs') }}</span>
            </a>

            <div class="nav-section">{{ __('acl::nav.actions') }}</div>
            <form action="{{ route('acl.routes.sync') }}" method="POST" class="sync-form">
                @csrf
                <button type="submit" class="btn btn-success btn-sm" title="{{ __('acl::nav.sync_routes') }}">
                    <span class="sync-icon">🔄</span>
                    <span class="sync-text">{{ __('acl::nav.sync_routes') }}</span>
                </button>
            </form>
        </nav>
    </aside>

    {{-- Main Content --}}
    <main class="main">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">❌ {{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                ❌
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif
        @if(session('sync_output'))
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header"><h3>🔄 Sync Output</h3></div>
                <div class="sync-output">{{ session('sync_output') }}</div>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    // Sidebar toggle behavior with persistent state
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            localStorage.setItem('acl_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        });
    }

    // Toggle switch behavior
    document.querySelectorAll('.toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('active');
            const input = toggle.previousElementSibling;
            if (input && input.type === 'hidden') {
                input.value = toggle.classList.contains('active') ? '1' : '0';
            }
        });
    });

    // Checkbox item visual feedback
    document.querySelectorAll('.checkbox-item input[type="checkbox"]').forEach(cb => {
        const item = cb.closest('.checkbox-item');
        if (cb.checked) item.classList.add('checked');
        cb.addEventListener('change', () => {
            item.classList.toggle('checked', cb.checked);
        });
    });

    // Confirm delete
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
</script>
</body>
</html>
