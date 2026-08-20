# 🛡️ Laravel Role & Permission Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/salvatorecervone/rolepermissionmanager.svg?style=flat-square)](https://packagist.org/packages/salvatorecervone/rolepermissionmanager)
[![Total Downloads](https://img.shields.io/packagist/dt/salvatorecervone/rolepermissionmanager.svg?style=flat-square)](https://packagist.org/packages/salvatorecervone/rolepermissionmanager)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg?style=flat-square)](https://laravel.com)

A modern, dynamic, database-driven Role & Permission Manager for Laravel that completely replaces static middleware annotations with a centralized, **zero-hardcoding** Access Control Layer (ACL).

---

## 💡 The Problem with Traditional RBAC (e.g. Spatie Permission)

In traditional authorization setups, permissions are hardcoded into route definitions, controller constructors, or method calls:

```php
// ❌ Traditional approach: Hardcoded permissions in code
Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])
    ->middleware('permission:delete-invoices');
```

When business rules change (e.g. *"Only Finance Supervisors can delete invoices now"*, or *"Split permission into draft vs finalized invoices"*), you must:
1. Modify source code files across controllers and routes
2. Commit changes to Git
3. Create a pull request, run CI/CD, and deploy a new release to production

**RolePermissionManager eliminates this bottleneck entirely.**

Routes and functions are registered as **Secured Resources** in the database. Permissions, roles, and route access rules are mapped dynamically and cached in memory/Redis. You can change any permission rule from the built-in Web Admin Panel or database in **seconds — with zero code changes and zero downtime.**

---

## ✨ Features

- 🚀 **Zero Hardcoding** — Define clean routes without cluttering them with `permission:...` middleware
- 🔍 **Route Auto-Discovery** — `php artisan acl:sync` scans your routes and registers new endpoints automatically
- ⏰ **Automated Scheduler** — Configurable daily route synchronization to catch new endpoints
- 🛡️ **Single Dynamic Interceptor** — `DynamicAclGuard` middleware evaluates requests against cached ACL rules
- ⚡ **High Performance & Low Latency** — Complete cache layer (Redis/File/Memory) with automatic invalidation on Eloquent events
- 🎛️ **AND / OR Permission Operators** — Choose whether a route requires *all* or *at least one* of the linked permissions
- 👑 **Super Admin Bypass** — Configurable super admin role that bypasses all permission checks automatically
- 👤 **User Access Management** — Manage user roles and direct permissions with live autocomplete search
- 🖥️ **Built-in Web Admin Panel** — Modern, dark-themed dashboard for managing Roles, Permissions, Routes, and Users (no external JS dependencies)
- 🎨 **Blade Directives** — `@role`, `@haspermission`, and `@canRoute` for views
- 🔌 **Native Laravel Gate Integration** — Works seamlessly with `$user->can()` and `@can`
- 📦 **Polymorphic Architecture** — Works with any Authenticatable model (User, Admin, Member, etc.)

---

## 📋 Requirements

- **PHP**: `^8.2`
- **Laravel**: `^10.0 | ^11.0 | ^12.0`

---

## 📦 Installation

### 1. Require the package via Composer

```bash
composer require salvatorecervone/rolepermissionmanager
```

### 2. Publish Assets

You can publish all assets at once:

```bash
php artisan vendor:publish --provider="SalvatoreCervone\RolePermissionManager\RolePermissionManagerServiceProvider"
```

Or publish individual components using specific tags:

| Component | Publish Command | Target Location |
|:----------|:----------------|:----------------|
| **Config** (Required) | `php artisan vendor:publish --tag=rolepermissionmanager-config` | `config/rolepermissionmanager.php` |
| **Migrations** (Required) | `php artisan vendor:publish --tag=rolepermissionmanager-migrations` | `database/migrations/` |
| **Language Files** (Optional) | `php artisan vendor:publish --tag=rolepermissionmanager-lang` | `lang/vendor/acl/` |
| **Blade Views** (Optional) | `php artisan vendor:publish --tag=rolepermissionmanager-views` | `resources/views/vendor/acl/` |
| **Routes** (Optional) | `php artisan vendor:publish --tag=rolepermissionmanager-routes` | `routes/acl-web.php` & `routes/acl-api.php` |

```bash
# 1. Config file (custom table names, cache TTL, super admin, locale, etc.)
php artisan vendor:publish --tag=rolepermissionmanager-config

# 2. Database migrations (7 ACL tables)
php artisan vendor:publish --tag=rolepermissionmanager-migrations

# 3. (Optional) Language files for custom translations (EN & IT included)
php artisan vendor:publish --tag=rolepermissionmanager-lang

# 4. (Optional) Admin panel Blade views for custom branding & UI styling
php artisan vendor:publish --tag=rolepermissionmanager-views

# 5. (Optional) Custom route files for extending Web & API endpoints
php artisan vendor:publish --tag=rolepermissionmanager-routes
```

### 3. Run Database Migrations

```bash
php artisan migrate
```

This creates 8 tables (customizable in config):
- `acl_roles`
- `acl_permissions`
- `acl_secured_resources`
- `acl_scanner_rules` (dynamic route exclusions & inclusions)
- `acl_model_has_roles` (polymorphic pivot)
- `acl_model_has_permissions` (polymorphic pivot)
- `acl_role_has_permissions` (pivot)
- `acl_permission_has_resources` (pivot)

---

## 🚀 Quick Start

### 1. Add the Trait to your User Model

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SalvatoreCervone\RolePermissionManager\Traits\HasAcl;

class User extends Authenticatable
{
    use HasAcl;
}
```

### 2. Synchronize Application Routes

```bash
php artisan acl:sync --notify
```

Output:
```
🔍 Scanning routes...

+-------------------------------+-------+
| Action                        | Count |
+-------------------------------+-------+
| 📗 New routes registered      | 16    |
| 📘 Existing routes updated    | 0     |
| 📙 Routes deprecated (soft)   | 0     |
| 📕 Routes removed (hard)      | 0     |
| ⏭️  Routes skipped (excluded)  | 4     |
+-------------------------------+-------+

✅ ACL route sync completed. Cache refreshed.
```

### 3. Write Clean Routes (No Middleware Hardcoding!)

```php
// routes/web.php or routes/api.php
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
```

The global `DynamicAclGuard` middleware intercepts every request and verifies access dynamically against the cached ACL registry.

---

## 🖥️ Web Administration Panel

The package includes a modern, zero-dependency admin dashboard accessible at `/acl-admin`:

```
/acl-admin
├── /                       → Dashboard (KPIs, statistics, recent resources, sync trigger)
├── /users                  → Users list with live autocomplete search & filter by role
├── /users/{id}/edit        → Assign/remove roles & direct permissions for a user
├── /roles                  → Roles list with permission counts
├── /roles/create           → Create role (auto-slug generator)
├── /roles/{id}/edit        → Edit role & assign permissions (grouped by module)
├── /permissions            → Permissions list with module filters
├── /permissions/create     → Create permission with module classification
├── /permissions/{id}/edit  → Edit permission & view linked roles/resources
├── /resources              → Secured routes list with method/status/search filters
└── /resources/{id}/edit    → Configure route (Public/Protected, OR/AND operator, permissions)
```

---

## 📖 Usage & API Reference

### Role Management

```php
use SalvatoreCervone\RolePermissionManager\Models\Role;

// Create or retrieve roles
$admin = Role::findOrCreate('admin', 'Administrator');
$editor = Role::findOrCreate('editor', 'Content Editor');

// Assign roles to a user
$user->assignRole('admin');
$user->assignRole('editor', 'writer'); // Multiple roles
$user->assignRole($admin);            // By model instance

// Remove roles
$user->removeRole('editor');

// Replace all roles
$user->syncRoles('admin', 'finance');

// Role checks
$user->hasRole('admin');              // bool
$user->hasAnyRole('admin', 'editor'); // bool
$user->hasAllRoles('admin', 'editor');// bool
```

### Permission Management

```php
use SalvatoreCervone\RolePermissionManager\Models\Permission;

// Create permissions with module grouping
$viewUsers = Permission::findOrCreate('users.view', 'View Users', 'Users');
$deleteUsers = Permission::findOrCreate('users.delete', 'Delete Users', 'Users');

// Give permissions to roles
$admin->givePermissionTo('users.view', 'users.delete');
$admin->revokePermissionTo('users.delete');
$admin->syncPermissions('users.view');

// Direct permissions on users (independent of roles)
$user->givePermissionTo('reports.export');
$user->revokePermissionTo('reports.export');
$user->syncPermissions('reports.export', 'logs.view');

// Permission checks (inherits from roles + direct permissions)
$user->hasPermission('users.view');              // bool
$user->hasAnyPermission('users.view', 'users.edit'); // bool
$user->hasAllPermissions('users.view', 'users.delete'); // bool

// Get all permission slugs for user
$permissions = $user->getAllPermissions(); // array of slugs
```

### Route-Level Access Verification

```php
// Check if user has permission to access a specific route
if ($user->canAccessRoute('invoices.destroy')) {
    // Show delete button or perform action
}
```

### Blade Directives

```blade
{{-- Check Role --}}
@role('admin')
    <a href="/admin">Admin Area</a>
@endrole

{{-- Check Permission --}}
@haspermission('users.export')
    <button>Export Users</button>
@endhaspermission

{{-- Check Route Access dynamically --}}
@canRoute('invoices.destroy')
    <button class="btn-danger">Delete Invoice</button>
@endcanRoute
```

### Native Laravel Gate Integration

The package hooks into Laravel's `Gate::before`, allowing standard `@can` and `$user->can()` checks:

```blade
@can('users.delete')
    <button>Delete</button>
@endcan
```

```php
if ($request->user()->can('invoices.export')) {
    // Authorized
}
### Menu & Navigation Tree Filtering

Filter dynamic, nested sidebar/menu structures (e.g., PrimeVue, PrimeReact, Admin menus) automatically based on the user's permissions and roles:

```php
$menu = [
    [
        'label'    => 'Uff. Valutazioni',
        'icon'     => 'pi pi-fw pi-home',
        'permessi' => ['scrivi_rapportoinformativo', 'scrivi_anagraficarelazionedirigenziale'],
        'items'    => [
            [
                'label'    => 'Organico RI',
                'url'      => '/rapportiinformativi/match',
                'permessi' => ['scrivi_rapportoinformativo'],
            ],
            [
                'label'    => 'Organico RD',
                'url'      => '/relazionidirigenziali/match',
                'permessi' => ['scrivi_anagraficarelazionedirigenziale'],
            ],
        ],
    ],
];

// Returns only the items and sub-items the user is authorized to see
$filteredMenu = auth()->user()->filterNavigation($menu);
// Or via static helper:
$filteredMenu = \SalvatoreCervone\RolePermissionManager\Services\AclRegistry::filterMenu($menu);
```

---

## ⚙️ Configuration Reference

File: `config/rolepermissionmanager.php`

```php
return [

    // Customizable database table names
    'tables' => [
        'roles'                    => 'acl_roles',
        'permissions'              => 'acl_permissions',
        'secured_resources'        => 'acl_secured_resources',
        'model_has_roles'          => 'acl_model_has_roles',
        'model_has_permissions'    => 'acl_model_has_permissions',
        'role_has_permissions'     => 'acl_role_has_permissions',
        'permission_has_resources' => 'acl_permission_has_resources',
    ],

    // Model classes
    'models' => [
        'user'              => App\Models\User::class,
        'role'              => SalvatoreCervone\RolePermissionManager\Models\Role::class,
        'permission'        => SalvatoreCervone\RolePermissionManager\Models\Permission::class,
        'secured_resource'  => SalvatoreCervone\RolePermissionManager\Models\SecuredResource::class,
    ],

    // User model and autocomplete search configuration
    'users' => [
        'table'             => 'users',
        'searchable_fields' => ['name', 'email'], // Columns searched by autocomplete
        'display_field'     => 'name',            // Primary label column
        'secondary_field'   => 'email',           // Sub-label in autocomplete
        'per_page'          => 25,
    ],

    // Super Admin role slug (bypasses all checks)
    'super_admin_role' => 'super-admin',

    // Cache settings
    'cache' => [
        'store'  => null,    // null = default store (Redis, Memcached, File)
        'ttl'    => 86400,   // 24 hours (0 = forever)
        'prefix' => 'acl_',
    ],

    // Route scanner settings
    'scanner' => [
        'excluded_prefixes' => [
            '_ignition', '_debugbar', 'sanctum', 'telescope', 'horizon', 'livewire',
        ],
        'excluded_names' => [
            'login', 'logout', 'register', 'password.request', 'password.reset',
        ],
        'default_is_public'       => false, // Secure by default
        'default_operator'        => 'OR',  // 'OR' or 'AND'
        'auto_create_permissions' => false,
    ],

    // Middleware settings
    'middleware' => [
        'register_globally'    => true,    // Applied to 'web' and 'api' groups
        'guard'                => null,    // null = default guard
        'unprotected_behavior' => 'allow', // 'allow' or 'deny'
    ],

    // Automated Scheduler
    'scheduler' => [
        'enabled' => false,
        'time'    => '06:00',
        'options' => [
            'clean'            => false,
            'auto_permissions' => false,
            'notify'           => true,
        ],
    ],

    // Web Admin Panel
    'admin_panel' => [
        'enabled'    => true,
        'prefix'     => 'acl-admin',
        'middleware' => ['web', 'auth'],
        'page_title' => 'ACL Manager',
        'per_page'   => 25,
    ],

];
```

---

## 🛠️ Artisan Commands

```bash
# Basic route scan and cache rebuild
php artisan acl:sync

# Remove routes from DB that no longer exist in code
php artisan acl:sync --clean

# Automatically create permissions for all newly discovered routes
php artisan acl:sync --auto-permissions

# Detailed verbose logging of scanned routes
php artisan acl:sync --notify

# Complete sync with cleanup, permission creation, and notification
php artisan acl:sync --clean --auto-permissions --notify
```

---

## 🧪 Testing & Workbench Preview

Run the PHPUnit test suite:

```bash
composer test
# or
./vendor/bin/phpunit
```

### Standalone Workbench Preview (No full app required!)

You can run and test the complete package and admin panel in an isolated SQLite workbench:

```bash
# 1. Run migrations and seed demo data
php vendor/bin/testbench migrate:fresh --seed --class="Workbench\Database\Seeders\DatabaseSeeder"

# 2. Start the local server
php vendor/bin/testbench serve --port=8080
```

Open [http://127.0.0.1:8080](http://127.0.0.1:8080) and log in with:
- **Email**: `admin@demo.test`
- **Password**: `password`

---

## 🏷️ Versioning & Git Tags

This package follows [Semantic Versioning (SemVer)](https://semver.org/).

To create and publish a new release:

```bash
# 1. Tag the release
git tag -a v1.0.0 -m "Release v1.0.0: Initial release of RolePermissionManager"

# 2. Push commits and tags to GitHub
git push origin main --tags
```

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
