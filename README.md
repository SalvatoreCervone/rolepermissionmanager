# Role Permission Manager

A dynamic, database-driven Role & Permission Manager for Laravel that replaces static middleware-based permission checks with a centralized, zero-hardcoding ACL system.

## Why This Package?

With traditional packages like Spatie Permission, you need to scatter `middleware('permission:...')` calls across your route files and controllers. Every permission change requires a code change, a pull request, and a deploy.

**This package eliminates that entirely.** Permissions are mapped to routes dynamically via a database table. You can change who can access what from a database panel, admin UI, or seeder — without touching a single line of application code.

## Features

- 🚀 **Zero Hardcoding** — No `middleware('permission:...')` in your route files
- 🔍 **Auto-Discovery** — `php artisan acl:sync` scans your Laravel routes automatically
- ⏰ **Scheduler Support** — Automatic daily route sync at a configurable time
- 🛡️ **Dynamic Middleware** — A single middleware intercepts all requests and checks permissions from cache
- ⚡ **High Performance** — Full cache layer (Redis/File) with automatic invalidation
- 🔗 **AND / OR Logic** — Configure whether a route needs all or any of its permissions
- 👑 **Super Admin Bypass** — Configurable super admin role that bypasses all checks
- 🎨 **Blade Directives** — `@role`, `@haspermission`, `@canRoute` for your views
- 🔌 **Laravel Gate Integration** — Works with `$user->can()` and `@can` out of the box
- 📦 **Polymorphic** — Attach roles/permissions to any model, not just `User`

## Requirements

- PHP 8.2+
- Laravel 10.x / 11.x / 12.x

## Installation

```bash
composer require salvatore/role-permission-manager
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=rolepermissionmanager-config
```

Run the migrations:

```bash
php artisan migrate
```

## Setup

### 1. Add the Trait to Your User Model

```php
use Salvatore\RolePermissionManager\Traits\HasAcl;

class User extends Authenticatable
{
    use HasAcl;
}
```

### 2. Sync Your Routes

```bash
php artisan acl:sync --notify
```

This scans all your registered Laravel routes and creates entries in the `acl_secured_resources` table.

### 3. Configure Permissions

Assign permissions to routes and roles to users — via seeders, Tinker, or an admin UI:

```php
use Salvatore\RolePermissionManager\Models\Role;
use Salvatore\RolePermissionManager\Models\Permission;
use Salvatore\RolePermissionManager\Models\SecuredResource;

// Create roles and permissions
$admin = Role::findOrCreate('admin', 'Administrator');
$editor = Role::findOrCreate('editor', 'Editor');

$viewUsers = Permission::findOrCreate('users.view', 'View Users', 'Users');
$deleteUsers = Permission::findOrCreate('users.delete', 'Delete Users', 'Users');

// Give permissions to roles
$admin->givePermissionTo('users.view', 'users.delete');
$editor->givePermissionTo('users.view');

// Link permissions to routes
$resource = SecuredResource::findByIdentifier('users.destroy');
$resource->permissions()->attach($deleteUsers->id);

// Assign roles to users
$user->assignRole('admin');
```

## Usage

### Route Protection (Automatic)

Just write your routes normally — **no middleware needed**:

```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
```

The `DynamicAclGuard` middleware is automatically registered globally and checks every request against the cached permission rules.

### Role Management

```php
$user->assignRole('editor');
$user->assignRole('editor', 'writer');    // Multiple roles
$user->removeRole('editor');
$user->syncRoles('admin');                // Replace all roles

$user->hasRole('admin');                  // true/false
$user->hasAnyRole('admin', 'editor');     // true if has at least one
$user->hasAllRoles('admin', 'editor');    // true if has all
```

### Permission Management

```php
// Direct permissions (bypassing roles)
$user->givePermissionTo('users.export');
$user->revokePermissionTo('users.export');

// Check permissions (from roles + direct)
$user->hasPermission('users.delete');
$user->hasAnyPermission('users.view', 'users.edit');
$user->hasAllPermissions('users.view', 'users.edit');

// Check route access
$user->canAccessRoute('users.destroy');
```

### Blade Directives

```blade
@role('admin')
    <a href="/admin">Admin Panel</a>
@endrole

@haspermission('users.export')
    <button>Export Users</button>
@endhaspermission

@canRoute('invoices.destroy')
    <button class="btn-danger">Delete Invoice</button>
@endcanRoute
```

### Laravel Gate Integration

The package integrates with Laravel's Gate, so native `@can` and `$user->can()` work automatically:

```blade
@can('users.delete')
    <button>Delete</button>
@endcan
```

```php
if ($user->can('invoices.export')) {
    // ...
}
```

## Configuration

### User Model & Search Autocomplete

Configure how users are resolved, searched, and displayed in the ACL Admin Panel:

```php
'models' => [
    'user' => App\Models\User::class,
    // ...
],

'users' => [
    'table'             => 'users',
    'searchable_fields' => ['name', 'email'], // Columns searched by autocomplete
    'display_field'     => 'name',            // Primary label column
    'secondary_field'   => 'email',           // Sub-label in autocomplete
    'per_page'          => 25,
],
```

### Scheduler (Automatic Daily Sync)

Enable automatic route syncing in `config/rolepermissionmanager.php`:

```php
'scheduler' => [
    'enabled' => true,
    'time'    => '06:00',
    'options' => [
        'clean'            => false,
        'auto_permissions' => false,
        'notify'           => true,
    ],
],
```

### Artisan Command Options

```bash
# Basic sync
php artisan acl:sync

# Remove routes that no longer exist in code
php artisan acl:sync --clean

# Auto-create permissions for new routes
php artisan acl:sync --auto-permissions

# Verbose output with detailed logging
php artisan acl:sync --notify

# All options combined
php artisan acl:sync --clean --auto-permissions --notify
```

### Middleware Behavior

Control what happens when a route is not registered in the ACL system:

```php
'middleware' => [
    'register_globally'    => true,     // Auto-register on web & api groups
    'guard'                => null,     // Auth guard (null = default)
    'unprotected_behavior' => 'allow',  // 'allow' or 'deny'
],
```

### Manual Middleware Usage

If you prefer not to register globally, disable it and use the `acl` alias:

```php
// config/rolepermissionmanager.php
'middleware' => ['register_globally' => false],

// routes/web.php
Route::middleware('acl')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
# rolepermissionmanager
