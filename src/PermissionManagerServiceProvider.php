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
        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/roles.php' => config_path('roles.php'),
        ], 'permission-manager-config');

        // Also allow publishing under the common 'config' tag
        $this->publishes([
            __DIR__ . '/../config/roles.php' => config_path('roles.php'),
        ], 'config');

        // Register package commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ResetPermissions::class,
            ]);
        }

        // Load helpers
        if (file_exists(__DIR__ . '/helpers.php')) {
            require_once __DIR__ . '/helpers.php';
        }
    }
}
