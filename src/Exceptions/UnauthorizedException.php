<?php

namespace SalvatoreCervone\RolePermissionManager\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedException extends HttpException
{
    /**
     * Create a new UnauthorizedException for missing roles.
     */
    public static function forRoles(array $roles): self
    {
        $message = sprintf(
            'User does not have the required role(s): [%s].',
            implode(', ', $roles)
        );

        return new self(403, $message);
    }

    /**
     * Create a new UnauthorizedException for missing permissions.
     */
    public static function forPermissions(array $permissions): self
    {
        $message = sprintf(
            'User does not have the required permission(s): [%s].',
            implode(', ', $permissions)
        );

        return new self(403, $message);
    }

    /**
     * Create a new UnauthorizedException for a protected resource.
     */
    public static function forResource(string $identifier): self
    {
        $message = sprintf(
            'User does not have access to the resource: [%s].',
            $identifier
        );

        return new self(403, $message);
    }

    /**
     * Create a new UnauthorizedException for unauthenticated users.
     */
    public static function notLoggedIn(): self
    {
        return new self(401, 'User is not authenticated.');
    }
}
