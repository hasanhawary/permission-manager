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
* Auto-discovers your **models** and generates permissions (`create-user`, `update-project`, etc.).
* **Config-driven roles**: inheritance (`like`), add/remove (`added`, `exception`), and custom permission sets.
* **Additional operations** for global actions not tied to models.
* **Translation-ready**: multilingual `display_name` for roles & permissions (e.g., English & Arabic).
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
}
```

Generated permissions:

```
read-report
update-report
export-report
```

---

## ⚙️ Config Example (`config/roles.php`)

```php
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
* Translate-ready permissions (`display_name` in multiple languages)
* Config-based role inheritance (`like`, `exception`, `added`)
* Extra operations beyond models (`ReportBuilder`, etc.)
* Supports Laravel Modules out of the box

---

## ✅ Version Support

- **PHP**: 8.0 – 8.5
- **Laravel**: 8 – 12

---

## 📜 License

MIT © [Hasan Hawary](https://github.com/hasanhawary)
