<?php

namespace SalvatoreCervone\RolePermissionManager\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    protected array $requiredRoles = [];
    protected array $requiredPermissions = [];
    protected ?string $resourceIdentifier = null;

    /**
     * Create a new UnauthorizedException for missing roles.
     */
    public static function forRoles(array $roles): self
    {
        $exception = new self(403, 'User is not authorized.');
        $exception->requiredRoles = $roles;

        return $exception;
    }

    /**
     * Create a new UnauthorizedException for missing permissions.
     */
    public static function forPermissions(array $permissions): self
    {
        $exception = new self(403, 'User is not authorized.');
        $exception->requiredPermissions = $permissions;

        return $exception;
    }

    /**
     * Create a new UnauthorizedException for a protected resource.
     */
    public static function forResource(string $identifier, ?string $customMessage = null): self
    {
        $msg = $customMessage ?: 'User is not authorized.';
        $exception = new self(403, $msg);
        $exception->resourceIdentifier = $identifier;

        return $exception;
    }

    /**
     * Create a new UnauthorizedException for an unconfigured (pending/locked) resource.
     */
    public static function forUnconfiguredResource(string $identifier, ?string $customMessage = null): self
    {
        if ($customMessage) {
            $msg = $customMessage;
        } else {
            $translated = function_exists('__') ? __('acl::resources.unconfigured_denied_message', ['resource' => $identifier]) : null;
            $msg = ($translated && $translated !== 'acl::resources.unconfigured_denied_message')
                ? $translated
                : "Resource '{$identifier}' is unconfigured and locked.";
        }

        $exception = new self(403, $msg);
        $exception->resourceIdentifier = $identifier;

        return $exception;
    }

    /**
     * Create a new UnauthorizedException for unauthenticated users.
     */
    public static function notLoggedIn(): self
    {
        return new self(401, 'User is not authenticated.');
    }

    /**
     * Get the required roles that caused the exception.
     */
    public function getRequiredRoles(): array
    {
        return $this->requiredRoles;
    }

    /**
     * Get the required permissions that caused the exception.
     */
    public function getRequiredPermissions(): array
    {
        return $this->requiredPermissions;
    }

    /**
     * Get the resource identifier that caused the exception.
     */
    public function getResourceIdentifier(): ?string
    {
        return $this->resourceIdentifier;
    }

    /**
     * Render the exception into an HTTP response for AJAX / JSON requests.
     */
    public function render($request)
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => false,
                'error'    => 'unauthorized',
                'message'  => $this->getMessage(),
                'resource' => $this->resourceIdentifier,
            ], $this->getStatusCode(), [
                'X-ACL-Denied'   => '1',
                'X-ACL-Resource' => (string) ($this->resourceIdentifier ?? ''),
            ]);
        }

        return false;
    }
}
