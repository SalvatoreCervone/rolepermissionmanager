<?php

namespace SalvatoreCervone\RolePermissionManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException;
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;
use Symfony\Component\HttpFoundation\Response;

class DynamicAclGuard
{
    /**
     * Handle an incoming request.
     *
     * This middleware intercepts every request and dynamically checks
     * the user's permissions against the secured_resources registry
     * stored in cache/database — no hardcoded permission strings needed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        // If no route is resolved (e.g., 404), skip ACL checks.
        if (!$route) {
            return $next($request);
        }

        // 1. Resolve the route identifier.
        $routeName = $route->getName();
        $routeSignature = $request->method() . ':' . $route->uri();

        // 2. Look up the resource rule in the ACL registry (cached).
        $rule = AclRegistry::getResourceRule($routeName, $routeSignature);

        // 3. If the route is not registered in the ACL system.
        if (!$rule) {
            $behavior = config('rolepermissionmanager.middleware.unprotected_behavior', 'allow');

            if ($behavior === 'deny') {
                $identifier = $routeName ?? $routeSignature;
                throw UnauthorizedException::forResource($identifier);
            }

            // 'allow': Route is not managed by ACL, let it through.
            return $next($request);
        }

        // 4. If the resource is marked as public, skip all checks.
        if ($rule->is_public) {
            return $next($request);
        }

        // 5. Retrieve the authenticated user.
        $guard = config('rolepermissionmanager.middleware.guard');
        $user = $request->user($guard);

        if (!$user) {
            throw UnauthorizedException::notLoggedIn();
        }

        // 6. Super Admin bypass.
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // 7. Check permissions based on the operator (AND / OR).
        $requiredPermissions = $rule->permission_slugs ?? [];

        // If no permissions are configured for this resource,
        // only SuperAdmin can access (which was already checked above).
        if (empty($requiredPermissions)) {
            $identifier = $routeName ?? $routeSignature;
            throw UnauthorizedException::forResource($identifier);
        }

        // Get the user's permission slugs (cached).
        $userPermissions = AclRegistry::getUserPermissions($user);

        $hasAccess = $this->evaluateAccess(
            $rule->operator ?? 'OR',
            $requiredPermissions,
            $userPermissions
        );

        if (!$hasAccess) {
            throw UnauthorizedException::forPermissions($requiredPermissions);
        }

        return $next($request);
    }

    /**
     * Determine if the user has the Super Admin role.
     */
    protected function isSuperAdmin($user): bool
    {
        $superAdminSlug = config('rolepermissionmanager.super_admin_role');

        if (!$superAdminSlug) {
            return false;
        }

        // If the user model has the HasAcl trait, use the hasRole method.
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($superAdminSlug);
        }

        return false;
    }

    /**
     * Evaluate access based on the operator and permission sets.
     *
     * @param  string  $operator  'AND' or 'OR'
     * @param  array   $required  The permission slugs required by the resource.
     * @param  array   $user      The permission slugs the user possesses.
     */
    protected function evaluateAccess(string $operator, array $required, array $user): bool
    {
        if ($operator === 'AND') {
            // All required permissions must be present.
            return empty(array_diff($required, $user));
        }

        // OR: At least one required permission must be present.
        return !empty(array_intersect($required, $user));
    }
}
