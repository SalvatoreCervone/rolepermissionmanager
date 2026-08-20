<?php

namespace SalvatoreCervone\RolePermissionManager\Tests\Unit;

use SalvatoreCervone\RolePermissionManager\Exceptions\UnauthorizedException;
use SalvatoreCervone\RolePermissionManager\Tests\TestCase;

class UnauthorizedExceptionTest extends TestCase
{
    public function test_for_permissions_returns_generic_message(): void
    {
        $exception = UnauthorizedException::forPermissions(['disciplina.leggi', 'disciplina.scrivi']);

        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('User is not authorized.', $exception->getMessage());
        $this->assertSame(['disciplina.leggi', 'disciplina.scrivi'], $exception->getRequiredPermissions());
    }

    public function test_for_roles_returns_generic_message(): void
    {
        $exception = UnauthorizedException::forRoles(['admin', 'editor']);

        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('User is not authorized.', $exception->getMessage());
        $this->assertSame(['admin', 'editor'], $exception->getRequiredRoles());
    }

    public function test_for_resource_returns_generic_message(): void
    {
        $exception = UnauthorizedException::forResource('protected.page');

        $this->assertSame(403, $exception->getStatusCode());
        $this->assertSame('User is not authorized.', $exception->getMessage());
        $this->assertSame('protected.page', $exception->getResourceIdentifier());
    }

    public function test_not_logged_in_returns_401_message(): void
    {
        $exception = UnauthorizedException::notLoggedIn();

        $this->assertSame(401, $exception->getStatusCode());
        $this->assertSame('User is not authenticated.', $exception->getMessage());
    }
}
