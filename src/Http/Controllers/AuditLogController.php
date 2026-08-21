<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SalvatoreCervone\RolePermissionManager\Models\AuditLog;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs with search and filtering.
     */
    public function index(Request $request)
    {
        $query = AuditLog::orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('target_identifier', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->get('action'));
        }

        $logs = $query->paginate(30)->appends($request->query());
        $actions = AuditLog::distinct()->pluck('action')->sort();

        return view('acl::audit_logs.index', compact('logs', 'actions'));
    }

    /**
     * Clear all audit logs.
     */
    public function clear()
    {
        AuditLog::truncate();

        return redirect()->route('acl.audit_logs.index')->with('success', __('acl::audit_logs.cleared_success'));
    }
}
