<?php

namespace SalvatoreCervone\RolePermissionManager\Services;

use Illuminate\Support\Facades\Auth;
use SalvatoreCervone\RolePermissionManager\Models\AuditLog;

class AuditLogger
{
    /**
     * Record an audit log entry.
     */
    public static function log(string $action, ?string $targetType = null, ?string $targetIdentifier = null, ?string $details = null): ?AuditLog
    {
        try {
            $user = Auth::user();
            $userId = $user ? $user->getAuthIdentifier() : null;
            $userName = $user ? ($user->name ?? $user->email ?? "User #{$userId}") : 'System';

            return AuditLog::create([
                'user_id'           => $userId,
                'user_name'         => $userName,
                'action'            => $action,
                'target_type'       => $targetType,
                'target_identifier' => $targetIdentifier,
                'details'           => $details,
                'ip_address'        => request() ? request()->ip() : null,
                'created_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            // Fail silently so audit log errors never break core ACL operations
            return null;
        }
    }
}
