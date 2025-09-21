<?php

namespace HasanHawary\PermissionManager;

use HasanHawary\PermissionManager\Console\Commands\ResetPermissions;
use Illuminate\Support\ServiceProvider;

class PermissionManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with application config
        $this->mergeConfigFrom(__DIR__ . '/../config/roles.php', 'roles');

        // Bind manager into the container for Facade usage
        $this->app->singleton(RoleManager::class, function () {
            return new RoleManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load package translations
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'permission-manager');

        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/roles.php' => config_path('roles.php'),
        ], 'permission-manager-config');

        // Also allow publishing under the common 'config' tag
        $this->publishes([
            __DIR__ . '/../config/roles.php' => config_path('roles.php'),
        ], 'config');

        // Publish translations (vendor namespace)
        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/permission-manager'),
        ], 'permission-manager-translations');

        // Optionally publish app-level roles.php stubs into lang/{locale}/roles.php
        $this->publishes([
            __DIR__ . '/../lang/en/roles.php' => lang_path('en/roles.php'),
            __DIR__ . '/../lang/ar/roles.php' => lang_path('ar/roles.php'),
        ], 'permission-manager-app-translations');

        // Register package commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ResetPermissions::class,
            ]);
        }
    }
}
