# 🛡️ Laravel Role & Permission Manager

[![Latest Version on Packagist](https://img.shields.io/packagist/v/salvatorecervone/rolepermissionmanager.svg?style=flat-square)](https://packagist.org/packages/salvatorecervone/rolepermissionmanager)
[![Total Downloads](https://img.shields.io/packagist/dt/salvatorecervone/rolepermissionmanager.svg?style=flat-square)](https://packagist.org/packages/salvatorecervone/rolepermissionmanager)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue.svg?style=flat-square)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-10.x%20%7C%2011.x%20%7C%2012.x-red.svg?style=flat-square)](https://laravel.com)

A modern, dynamic, database-driven Role & Permission Manager for Laravel that completely replaces static middleware annotations with a centralized, **zero-hardcoding** Access Control Layer (ACL).

---

## 💡 100% Automatic — Zero Code Changes & Coexistence

In traditional authorization setups, permissions are hardcoded into route definitions, controller constructors, or method calls:

```php
// ❌ Hardcoded permissions in code
Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])
    ->middleware('permission:delete-invoices');
```

When business rules change, developers must edit controllers, routes, commit, and deploy.

**RolePermissionManager eliminates this entirely:**

- ⚡ **Zero Code Modifications** — No need to add traits, modify controllers, or annotate routes.
- 🛡️ **Coexists with Existing Systems** — No need to remove or refactor your existing roles, permissions, or database tables. RolePermissionManager uses isolated tables (`acl_*`) and a non-intrusive dynamic interceptor.
- 🌐 **Instant Runtime Updates** — Change any permission rule from the built-in Web Admin Panel in seconds with **zero code changes and zero downtime.**

---

## ✨ Features

- 🚀 **Zero Hardcoding** — Define clean routes without cluttering them with `permission:...` middleware
- 🛡️ **Zero-Trust Auto-Discovery (`AclRegistry::protect()`)** — Protect any controller method, service, or business action with a single line of code. Automatically detects the caller and locks access (HTTP 403 for everyone, including Super Admin) until configured in the panel
- 🎯 **Granular Route Parameter Rules** — Secure URI placeholders (e.g. `/reports/{page}`) with specific permissions per parameter value and customizable fallback policies (404 Not Found, 403 Forbidden, or Pass-through)
- 🔍 **Route Auto-Discovery** — `php artisan acl:sync` scans your routes and registers new endpoints automatically
- 📦 **Custom Resources Support** — Create, manage, and protect arbitrary classes, methods, services, or UI actions from the panel
- 🔲 **Interactive Role-Permission Matrix** — Spreadsheet-style pivot matrix (`/acl-admin/matrix`) with real-time AJAX permission toggling
- 🔍 **Access Simulator & Diagnostic Tester** — Test user authorization step-by-step from both the UI (`/acl-admin/simulator`) and CLI (`php artisan acl:check`)
- 💾 **JSON Export & Import** — Seamlessly transfer entire ACL configurations across environments (*Local ➔ Staging ➔ Production*) via web UI or CLI (`php artisan acl:export`/`acl:import`)
- ⚡ **Bulk Actions on Routes & Resources** — Mass-assign permissions, set Super Admin only flags, or change visibility with one click
- 📜 **Audit Trail & Activity Log** — Complete history tracking of who changed which role, permission, or route (`/acl-admin/audit-logs`)
- ⏰ **Automated Scheduler** — Configurable daily route synchronization to catch new endpoints
- 🛡️ **Single Dynamic Interceptor** — `DynamicAclGuard` middleware evaluates requests against cached ACL rules
- ⚡ **High Performance & Low Latency** — Complete cache layer (Redis/File/Memory) with automatic invalidation on Eloquent events (0 DB queries per request)
- 🎛️ **AND / OR Permission Operators** — Choose whether a resource requires _all_ or _at least one_ of the linked permissions
- 👑 **Super Admin Bypass & Dedicated Protection** — Configurable super admin role that bypasses checks, plus a single-click toggle to reserve any route/resource exclusively for Super Admins
- 👤 **User Access Management** — Manage user roles and direct permissions with multi-column display support (e.g. `['name', 'cognome']`) and live autocomplete
- 🎨 **Blade Directives** — `@role`, `@haspermission`, `@canRoute`, and `@canResource` for views
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

| Component                     | Publish Command                                                     | Target Location                             |
| :---------------------------- | :------------------------------------------------------------------ | :------------------------------------------ |
| **Config** (Required)         | `php artisan vendor:publish --tag=rolepermissionmanager-config`     | `config/rolepermissionmanager.php`          |
| **Migrations** (Required)     | `php artisan vendor:publish --tag=rolepermissionmanager-migrations` | `database/migrations/`                      |
| **Language Files** (Optional) | `php artisan vendor:publish --tag=rolepermissionmanager-lang`       | `lang/vendor/acl/`                          |
| **Blade Views** (Optional)    | `php artisan vendor:publish --tag=rolepermissionmanager-views`      | `resources/views/vendor/acl/`               |
| **Routes** (Optional)         | `php artisan vendor:publish --tag=rolepermissionmanager-routes`     | `routes/acl-web.php` & `routes/acl-api.php` |

### 3. Run Database Migrations

```bash
php artisan migrate
```

This creates 9 tables (customizable in config):

- `acl_roles`
- `acl_permissions`
- `acl_secured_resources`
- `acl_route_parameter_rules` (granular parameter authorization & fallbacks)
- `acl_scanner_rules` (dynamic route exclusions & inclusions)
- `acl_model_has_roles` (polymorphic pivot)
- `acl_model_has_permissions` (polymorphic pivot)
- `acl_role_has_permissions` (pivot)
- `acl_permission_has_resources` (pivot)
- `acl_audit_logs` (audit history)

---

## 🚀 Quick Start

### 1. (Optional) Add the Trait to your User Model

Adding `HasAcl` to your `User` model is **optional** — it provides direct convenience helper methods on the user instance (e.g., `$user->assignRole()`, `$user->hasPermission()`, `$user->canAccessRoute()`):

```php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use SalvatoreCervone\RolePermissionManager\Traits\HasAcl;

class User extends Authenticatable
{
    use HasAcl;
}
```

> **Note:** The dynamic middleware `DynamicAclGuard`, the Admin Panel, and `AclRegistry` work seamlessly even without modifying your User model.

### 2. Synchronize Application Routes

```bash
php artisan acl:sync --notify
```

Output:

```
🔍 Scanning routes...
+-----------------------+--------+----------------------+------------------------------------------------+
| Identifier            | Method | URI                  | Action                                         |
+-----------------------+--------+----------------------+------------------------------------------------+
| dashboard             | GET    | /dashboard           | App\Http\Controllers\DashboardController@index |
| invoices.index        | GET    | /invoices            | App\Http\Controllers\InvoiceController@index   |
| invoices.create       | GET    | /invoices/create     | App\Http\Controllers\InvoiceController@create  |
| invoices.store        | POST   | /invoices            | App\Http\Controllers\InvoiceController@store   |
| invoices.destroy      | DELETE | /invoices/{id}       | App\Http\Controllers\InvoiceController@destroy |
+-----------------------+--------+----------------------+------------------------------------------------+
✔ Discovered 5 new routes. Cache refreshed.
```

### 3. Open the Web Admin Panel

Navigate to `https://your-domain.com/acl-admin` (protected by `['web', 'auth']` middleware by default).

From the panel you can:
- **🔲 Role-Permission Matrix** (`/acl-admin/matrix`): Toggle permissions per role in real time.
- **🔍 Access Simulator** (`/acl-admin/simulator`): Select any User and Route to run real-time authorization diagnostics.
- **⚡ Bulk Actions** (`/acl-admin/routes`): Select multiple routes to apply permissions or Super Admin flags in bulk.
- **💾 Export & Import** (`/acl-admin/export-import`): Download JSON backups or import ACL settings from other environments.
- **📜 Audit Logs** (`/acl-admin/audit-logs`): Review full historical tracking of authorization changes.
- **👤 Users & Access** (`/acl-admin/users`): Assign roles and direct permissions to users with multi-model polymorphic support.

---

### 4. 👥 Multi-Model / Polymorphic Support (Optional)

If your application has multiple authenticatable models (e.g. `User`, `Admin`, `Staff`), configure `user_models` in `config/rolepermissionmanager.php`:

```php
'user_models' => [
    'users' => [
        'label'             => 'Users',
        'model'             => App\Models\User::class,
        'searchable_fields' => ['name', 'email'],
        'display_field'     => ['name', 'cognome'],
        'secondary_field'   => 'email',
    ],
    'admins' => [
        'label'             => 'Administrators',
        'model'             => App\Models\Admin::class,
        'searchable_fields' => ['nome', 'cognome', 'email'],
        'display_field'     => ['nome', 'cognome'],
        'secondary_field'   => 'email',
    ],
],
```

The Web Admin Panel will automatically render model selector tabs on `/acl-admin/users` and within the Access Simulator!
- **⚙️ Scanner Rules** (`/acl-admin/scanner-rules`): Manage route exclusions and auto-registration patterns.

---

## 🛠️ Artisan Commands

```bash
# 1. Route Synchronization
php artisan acl:sync                                         # Basic route scan and cache rebuild
php artisan acl:sync --clean                                 # Remove deprecated routes from DB
php artisan acl:sync --auto-permissions                      # Auto-create permissions for new routes
php artisan acl:sync --clean --auto-permissions --notify     # Complete sync with verbose log

# 2. Access Diagnostics & Simulation
php artisan acl:check 1 invoices.destroy                     # Check user ID 1 against a route
php artisan acl:check admin@company.com "CorsoController@dettagliocorsi"

# 3. Export & Import Configuration
php artisan acl:export                                       # Export to storage/app/acl-export-*.json
php artisan acl:export --path=/path/to/custom-backup.json
php artisan acl:import /path/to/acl-export.json              # Merge configuration
php artisan acl:import /path/to/acl-export.json --overwrite  # Overwrite existing associations
```

---

## 🎨 Blade Directives

```blade
{{-- 1. Check Role --}}
@role('admin')
    <a href="/admin/settings">Admin Settings</a>
@endrole

{{-- 2. Check Permission (Direct or via Role) --}}
@haspermission('invoices.export')
    <button>Export CSV</button>
@endhaspermission

{{-- 3. Check Route Access --}}
@canRoute('invoices.destroy')
    <button class="btn-delete">Delete Invoice</button>
@endcanRoute

{{-- 4. Check Custom Resource Access --}}
@canResource('CorsoController@dettagliocorsi')
    <button class="btn-info">View Details</button>
@endcanResource
```

---

## 🛡️ Zero-Trust Auto-Discovery & Fail-Closed Protection

Protect internal controller methods, services, or sensitive business actions with a **single line of code**, completely eliminating human error (forgetting to register the resource or leaving methods unprotected):

```php
use SalvatoreCervone\RolePermissionManager\Services\AclRegistry;

class CorsoController extends Controller
{
    public function esportaAnagrafica()
    {
        // 1. One line: auto-detects 'CorsoController@esportaAnagrafica'
        AclRegistry::protect();

        // Or specify an explicit alias:
        // AclRegistry::protect('anagrafica.export');

        // Business logic runs ONLY if authorized...
    }
}
```

### How the Zero-Trust Fail-Closed Flow Works:

1. **Auto-Discovery via `debug_backtrace`**: When `AclRegistry::protect()` is called for the first time, it captures the calling class, method, and source file.
2. **Instant Fail-Closed (HTTP 403 for EVERYONE)**:
   - If the resource has not yet been configured in the database, it is automatically created with `is_unconfigured = true`.
   - **Access is strictly blocked with HTTP 403 Forbidden for all users, EVEN Super Admin** with `all_access = true`.
   - No sensitive code can ever execute accidentally without explicit authorization rules in place.
3. **Admin Panel Notification**:
   - An alert banner immediately appears on the Custom Resources page (`/acl-admin/resources`), indicating how many auto-discovered resources are awaiting configuration.
   - Filter by status `🔒 Awaiting Configuration (Blocked)` to locate them.
4. **Configuration & Unlocking**:
   - When an administrator assigns permissions (or marks the resource as public or super-admin only) and clicks **Save**, `is_unconfigured` is set to `false`.
   - The ACL cache is instantly refreshed, unlocking normal access for authorized users.

---

## 🎯 Granular Route Parameter Rules (URI Placeholders)

Secure dynamic route parameters without writing custom controller logic or custom regex middleware. For example, on a route like `/server-reports/{page}/{destination}`:

1. **Automatic Placeholder Detection**:
   - In `/acl-admin/routes/{id}/edit`, the system automatically parses and displays badges for all URI placeholders (e.g. `{page}`, `{destination}`).
2. **Per-Value Authorization**:
   - Define granular permission rules for specific parameter values:
     - `page = financial` ➔ Requires `reports.view_financial`
     - `page = audits` ➔ Requires `reports.view_audits` (or Super Admin only)
     - `page = overview` ➔ Mark as public access
3. **Unmatched Parameter Policy (Fallback)**:
   - Choose how the system reacts when a user requests an unlisted parameter value (e.g. `/server-reports/malicious_page/1`):
     - **404 Not Found** (`deny_404`): Blocks probe attempts early before hitting your controller.
     - **403 Forbidden** (`deny_403`): Rejects unauthorized values.
     - **Pass-through** (`allow`): Passes unlisted values to your controller.
4. **Zero Performance Overhead**:
   - Parameter rules are compiled directly into the cached resources map and evaluated in-memory by `DynamicAclGuard`.

---

## ⚙️ Configuration Reference

Key settings in `config/rolepermissionmanager.php`:

```php
return [

    // User Model & Search Configuration
    'users' => [
        'table'             => 'users',
        'searchable_fields' => ['name', 'cognome', 'email'],
        
        // Single column or array of columns concatenated with space:
        'display_field'     => ['name', 'cognome'],           // e.g. "Mario Rossi"
        'secondary_field'   => ['matricola', 'email'],        // e.g. "MAT12345 mario@company.it"
        'per_page'          => 25,
    ],

    // Super Admin Settings & Bypass
    'super_admin' => [
        'role'       => 'super-admin', // Role slug or null to disable
        'all_access' => true,          // true / 'all' for full bypass, false/null to require explicit permissions
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

## 🧪 Testing & Standalone Preview

Run the test suite:

```bash
composer test
# or
./vendor/bin/phpunit
```

### Standalone Workbench Preview (No full app required!)

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

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
