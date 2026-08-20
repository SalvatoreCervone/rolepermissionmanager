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
    public static function forResource(string $identifier): self
    {
        $exception = new self(403, 'User is not authorized.');
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
}
