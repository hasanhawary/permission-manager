# Permission Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/permission-manager.svg)](https://packagist.org/packages/hasanhawary/permission-manager)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/permission-manager.svg)](https://packagist.org/packages/hasanhawary/permission-manager)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/permission-manager.svg)](https://packagist.org/packages/hasanhawary/permission-manager)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A simple but powerful **role and permission manager** for Laravel, built on top of [spatie/laravel-permission](https://github.com/spatie/laravel-permission).

The package is self-contained: it ships its own Role and Permission models, migrations, config, command, facade, helpers, and service bindings. Host projects may override the models/config, but no `App\...` classes are required by default.

---

## ✨ Features

* **One-line setup:** `Access::handle()` builds roles & permissions automatically.
* Ships with **default roles** (`root`, `admin`).
* Auto-discovers your **models** from `app/Models` and **nested directories** automatically.
* **Config-driven roles**: inheritance (`like`), add/remove (`added`, `exception`), and custom permission sets.
* **Additional operations** for global actions not tied to models.
* **Translation-ready**: multilingual `display_name` for roles & permissions (e.g., English & Arabic).
* **Multiple guard support**: configure default guard and per-model guard overrides.
* **Package-owned models** for roles and permissions, with optional custom model overrides.
* **Package-owned migrations** for Spatie tables plus `display_name`, `group`, and `is_active` metadata.
* Works with **Laravel Modules** as well as `app/Models`.

---

## 📦 Installation

```bash
composer require hasanhawary/permission-manager
```

The service provider is auto-discovered.

Run migrations after installing. The package loads idempotent migrations for the Spatie permission tables plus package metadata columns:

```bash
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=permission-manager-config
```

Optionally publish migrations if you want to edit them before running:

```bash
php artisan vendor:publish --tag=permission-manager-migrations
```

If your app already owns equivalent Spatie/permission-manager migrations, disable automatic package migration loading:

```php
'migrations' => [
    'load' => false,
],
```

---

## ⚡ Quick Start: Build Everything

Use the **facade** for the simplest bootstrap:

```php
use HasanHawary\PermissionManager\Facades\Access;

// Full rebuild: reset tables, then regenerate roles and permissions
Access::handle();

// Regenerate without truncating existing tables
Access::handle(skipReset: true);
```

Or run the artisan command:

```bash
php artisan permissions:reset
php artisan permissions:reset --skip
```

The command delegates to `Access::handle()`.

---

## 🗂 Model-level Permissions

Define permissions **directly in your models**:

```php
class Report
{
    public bool $inPermission = true;

    // Override CRUD (defaults: create/read/update/delete)
    public array $basicOperations = ['read', 'update'];

    // Add custom operations
    public array $specialOperations = ['export'];
    
    // Optional: Set specific guard for this model's permissions
    // This overrides the default_guard from config
    protected string $guard_name = 'api';
}
```

Generated permissions:

```
read-report
update-report
export-report
```

The package automatically discovers models in `app/Models` and all **nested subdirectories**. Laravel Modules are also supported by default.

---

## ⚙️ Config Example (`config/roles.php`)

```php
// Package-owned model paths. Override only if your app has custom Spatie models.
'class_paths' => [
    'role' => \HasanHawary\PermissionManager\Models\Role::class,
    'permission' => \HasanHawary\PermissionManager\Models\Permission::class,
],

// Default guard for permissions (default: 'sanctum')
'default_guard' => 'sanctum',

'roles' => [
    'manager' => [
        'like' => 'admin',      // inherit from admin
        'type' => 'exception',  // remove selected permissions
        'permissions' => [
            'Project' => ['delete'], // manager cannot delete projects
        ],
    ],
    'auditor' => [
        'permissions' => [
            'Report' => ['read', 'export'],
        ],
    ],
],

'additional_operations' => [
    [
        'name' => 'ReportBuilder',
        'operations' => ['main'], // generates "main-report-builder"
        'basic' => true           // also add CRUD ops
    ]
],

'default' => [
    'permissions' => ['dashboard-access'],
],
```

### Configuration Options

#### `class_paths`
Override the default Role and Permission model classes. This is useful if you have custom implementations or use different namespaces:

```php
'class_paths' => [
    'role' => \HasanHawary\PermissionManager\Models\Role::class,
    'permission' => \HasanHawary\PermissionManager\Models\Permission::class,
],
```

The service provider also aligns Spatie's `permission.models.role` and `permission.models.permission` with these package defaults unless the host project already customized Spatie's model config.

#### `default_guard`
Set the default authentication guard for permissions. This guard will be used when checking user permissions and roles:

```php
'default_guard' => 'sanctum', // or 'web', 'api', etc.
```

#### Per-Model Guard Override
You can override the default guard for specific models using the `$guard_name` property in your model:

```php
class ApiResource
{
    public bool $inPermission = true;
    protected string $guard_name = 'api'; // Use 'api' guard instead of default
}
```

#### Discovery Paths
Model discovery defaults to Laravel apps and Laravel Modules, but paths and namespaces are configurable:

```php
'discovery' => [
    'models_path' => 'app/Models',
    'models_namespace' => 'App\\Models',
    'modules_path' => 'Modules',
    'modules_models_path' => 'App/Models',
    'modules_namespace' => 'Modules',
],
```

Set these to empty values to disable filesystem discovery and rely only on configured `additional_operations` / explicit role permissions.

#### Role Permission Rules

Role entries support direct permissions, inherited permissions, model-generated permissions, and merge modes:

```php
'roles' => [
    'auditor' => [
        'permissions' => [
            'Report' => ['read', 'export'],
        ],
    ],

    'manager' => [
        'like' => 'admin',
        'type' => 'exception',
        'permissions' => [
            'Project' => ['delete'],
        ],
    ],

    'operator' => [
        'models' => ['Report'],
        'type' => 'added',
        'permissions' => [
            'Dashboard' => ['read'],
        ],
    ],
],
```

Supported operation shortcuts:

```php
'permissions' => [
    'Report' => ['basic'], // create/read/update/delete
    'User' => ['*'],       // all operations resolved for User
],
```

---

## 🌍 Translations

This makes it easy to show localized names in dashboards, logs, or admin panels.

Example language file `lang/ar/roles.php`:

```php
<?php

return [
    'root'  => 'المدير الافتراضى',
    'admin' => 'المدير',
];
```

Usage:

```php
use HasanHawary\PermissionManager\Facades\Access;

Access::handle();
```

---

## ✅ Why this package?

* `Access::handle()` = full automation
* Default roles always exist (`root`, `admin`)
* Auto-discovers models from `app/Models` including **nested directories**
* Translate-ready permissions (`display_name` in multiple languages)
* Config-based role inheritance (`like`, `exception`, `added`)
* **Multiple guard support** with per-model override capability
* **Custom model class paths** for Role and Permission
* Extra operations beyond models (`ReportBuilder`, etc.)
* Supports Laravel Modules out of the box

---

## ✅ Version Support

- **PHP**: 8.1 – 8.5
- **Laravel**: 10 – 13

---

## Plain PHP Boundary

The database sync, artisan command, migrations, facade, and filesystem model discovery are Laravel-only. In non-Laravel PHP, use the framework-independent support/resolver classes directly and pass dependencies explicitly. Laravel-only orchestration fails with a clear exception instead of relying on a host app.

---

## Validation

Useful checks before publishing or after installing in a host app:

```bash
composer test
php artisan package:discover
php artisan route:list
php artisan migrate
php artisan permissions:reset
php artisan permissions:reset --skip
```

This package does not register routes. `route:list` should work without adding package routes.

CI runs the package test suite against PHP 8.1-8.4 and Laravel 10-13 with matching Orchestra Testbench versions.

---

## Troubleshooting

If `permissions:reset` reports a missing permission, clear cached config and rerun:

```bash
php artisan config:clear
php artisan permissions:reset
```

If you use custom Role/Permission models, make sure both `config/roles.php` and Spatie's `config/permission.php` point at compatible models, or let this package's defaults handle both.

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
