<?php

namespace HasanHawary\PermissionManager;

use HasanHawary\PermissionManager\Console\Commands\ResetPermissions;
use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Models\Permission;
use HasanHawary\PermissionManager\Models\Role;
use HasanHawary\PermissionManager\Resolvers\DisplayNameResolver;
use HasanHawary\PermissionManager\Resolvers\GuardResolver;
use HasanHawary\PermissionManager\Resolvers\OperationResolver;
use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use HasanHawary\PermissionManager\Services\PermissionResetter;
use HasanHawary\PermissionManager\Services\PermissionSynchronizer;
use HasanHawary\PermissionManager\Services\RolePermissionResolver;
use HasanHawary\PermissionManager\Support\ModelMetadataResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
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
        $this->registerDefaultSpatieModels();

        $this->app->singleton(PermissionManagerConfig::class);
        $this->app->singleton(ModelMetadataResolver::class);
        $this->app->singleton(ModelDiscovery::class);
        $this->app->singleton(OperationResolver::class);
        $this->app->singleton(GuardResolver::class);
        $this->app->singleton(DisplayNameResolver::class);
        $this->app->singleton(PermissionNameResolver::class);
        $this->app->singleton(PermissionSynchronizer::class);
        $this->app->singleton(RolePermissionResolver::class);
        $this->app->singleton(PermissionResetter::class);
        $this->app->singleton(RoleManager::class);
    }

    private function registerDefaultSpatieModels(): void
    {
        if (in_array(config('permission.models.role'), [null, \Spatie\Permission\Models\Role::class], true)) {
            config(['permission.models.role' => Role::class]);
        }

        if (in_array(config('permission.models.permission'), [null, \Spatie\Permission\Models\Permission::class], true)) {
            config(['permission.models.permission' => Permission::class]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('roles.migrations.load', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        // Publish the config file
        $this->publishes([
            __DIR__ . '/../config/roles.php' => config_path('roles.php'),
        ], 'permission-manager-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'permission-manager-migrations');

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
