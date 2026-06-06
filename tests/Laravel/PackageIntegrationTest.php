<?php

namespace HasanHawary\PermissionManager\Tests\Laravel;

use HasanHawary\PermissionManager\Facades\Access;
use HasanHawary\PermissionManager\Models\Permission;
use HasanHawary\PermissionManager\Models\Role;
use HasanHawary\PermissionManager\RoleManager;
use HasanHawary\PermissionManager\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class PackageIntegrationTest extends TestCase
{
	public function test_service_provider_registers_bindings_and_model_defaults(): void
	{
		$this->assertInstanceOf(RoleManager::class, app(RoleManager::class));
		$this->assertSame(Role::class, config('permission.models.role'));
		$this->assertSame(Permission::class, config('permission.models.permission'));
		$this->assertSame(Role::class, config('roles.class_paths.role'));
		$this->assertSame(Permission::class, config('roles.class_paths.permission'));
	}

	public function test_migrations_are_loaded_and_command_builds_permissions(): void
	{
		$this->artisan('migrate')->assertSuccessful();

		$this->assertTrue(Schema::hasColumn('roles', 'display_name'));
		$this->assertTrue(Schema::hasColumn('roles', 'is_active'));
		$this->assertTrue(Schema::hasColumn('permissions', 'display_name'));
		$this->assertTrue(Schema::hasColumn('permissions', 'group'));

		$this->artisan('permissions:reset')->assertSuccessful();

		$this->assertDatabaseHas('roles', ['name' => 'root']);
		$this->assertDatabaseHas('roles', ['name' => 'admin']);
		$this->assertDatabaseHas('roles', ['name' => 'default_role']);
		$this->assertDatabaseHas('permissions', ['name' => 'home-report']);
	}

	public function test_skip_reset_keeps_existing_rows_and_still_regenerates(): void
	{
		$this->artisan('migrate')->assertSuccessful();

		Access::handle();
		$roleCount = Role::query()->count();

		$this->artisan('permissions:reset --skip')->assertSuccessful();

		$this->assertSame($roleCount, Role::query()->count());
		$this->assertDatabaseHas('permissions', ['name' => 'home-report']);
	}

	public function test_route_list_works_without_package_routes(): void
	{
		$this->artisan('route:list')->assertSuccessful();
	}
}
