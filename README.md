# Permission Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/hasanhawary/permission-manager.svg)](https://packagist.org/packages/hasanhawary/permission-manager)
[![Total Downloads](https://img.shields.io/packagist/dm/hasanhawary/permission-manager.svg)](https://packagist.org/packages/hasanhawary/permission-manager)
[![PHP Version](https://img.shields.io/packagist/php-v/hasanhawary/permission-manager.svg)](https://packagist.org/packages/hasanhawary/permission-manager)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

A simple but powerful **role & permission manager** for Laravel, built on top of [spatie/laravel-permission](https://github.com/spatie/laravel-permission).

---

## ✨ Features

* **One-line setup:** `Access::handle()` builds roles & permissions automatically.
* Ships with **default roles** (`root`, `admin`).
* Auto-discovers your **models** from `app/Models` and **nested directories** automatically.
* **Config-driven roles**: inheritance (`like`), add/remove (`added`, `exception`), and custom permission sets.
* **Additional operations** for global actions not tied to models.
* **Translation-ready**: multilingual `display_name` for roles & permissions (e.g., English & Arabic).
* **Multiple guard support**: configure default guard and per-model guard overrides.
* **Custom model paths**: define your own Role and Permission model classes.
* Works with **Laravel Modules** as well as `app/Models`.

---

## 📦 Installation

```bash
composer require hasanhawary/permission-manager
```

The service provider is auto-discovered.

Optionally publish the config:

```bash
php artisan vendor:publish --tag=permission-manager-config
```

This creates `config/roles.php`.

---

## ⚡ Quick Start: Build Everything

Use the **facade** for the simplest bootstrap:

```php
use HasanHawary\PermissionManager\Facades\Access;

// Full rebuild (truncates & regenerates roles/permissions)
Access::handle();

// Just regenerate without resetting
Access::handle(skipReset: true);
```

Or run the artisan command:

```bash
php artisan permissions:reset
```

Both do the same thing under the hood.

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

The package automatically discovers models in `app/Models` and all **nested subdirectories**.

---

## ⚙️ Config Example (`config/roles.php`)

```php
// Custom model paths (optional)
'class_paths' => [
    'role' => \App\Models\Role::class,
    'permission' => \App\Models\Permission::class,
],

// Default guard for permissions (default: 'sanctum')
'default_guard' => 'sanctum',

'roles' => [
    'manager' => [
        'like' => 'admin',      // inherit from admin
        'type' => 'exception',  // remove selected permissions
        'permissions' => [
            'project' => ['delete'], // manager cannot delete projects
        ],
    ],
    'auditor' => [
        'permissions' => [
            'report' => ['read', 'export'],
        ],
    ],
],

'additional_operations' => [
    [
        'name' => 'ReportBuilder',
        'operations' => ['main'], // generates "main-reportbuilder"
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
    'role' => \App\Models\Role::class,
    'permission' => \App\Models\Permission::class,
],
```

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
use HasanHawary\PermissionManager\Access;

Access::handle('admin'); // المدير
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

- **PHP**: 8.0 – 8.5
- **Laravel**: 8 – 12

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
