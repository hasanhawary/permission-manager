<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Resolvers\OperationResolver;
use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use HasanHawary\PermissionManager\RoleManager;
use HasanHawary\PermissionManager\Services\PermissionResetter;
use HasanHawary\PermissionManager\Services\PermissionSynchronizer;
use HasanHawary\PermissionManager\Services\RolePermissionResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RoleManagerTest extends TestCase
{
	public function test_skip_reset_does_not_skip_configured_role_generation(): void
	{
		$config = new class extends PermissionManagerConfig {
			public function roles(): array
			{
				return [
					'manager' => [
						'permissions' => ['Report' => ['read']],
					],
				];
			}
		};

		$models = new class($config) extends ModelDiscovery {
			public function names(...$exceptions): Collection
			{
				return collect();
			}
		};

		$names = new PermissionNameResolver(new class($config, $models) extends OperationResolver {
		});

		$permissions = new class extends PermissionSynchronizer {
			public array $createdRoles = [];

			public array $syncedRoles = [];

			public function __construct()
			{
			}

			public function firstOrCreateRole(string $roleName): Model
			{
				$this->createdRoles[] = $roleName;
				$role = new class extends Model {
				};
				$role->setAttribute('name', $roleName);

				return $role;
			}

			public function syncModelPermissionsToRole(Model $role, Collection $models): Collection
			{
				return collect();
			}

			public function syncRolePermissions(Model $role, array $permissions): void
			{
				$this->syncedRoles[] = $role->name;
			}
		};

		$rolePermissions = new class($config, $names, $permissions) extends RolePermissionResolver {
			public function resolve(array $details): array
			{
				return ['read-report'];
			}
		};

		$resetter = new class extends PermissionResetter {
			public int $calls = 0;

			public function reset(): void
			{
				$this->calls++;
			}
		};

		$manager = new RoleManager($config, $models, $names, $permissions, $rolePermissions, $resetter);

		$manager->handle(skipReset: true);

		$this->assertSame(0, $resetter->calls);
		$this->assertContains('root', $permissions->createdRoles);
		$this->assertContains('admin', $permissions->createdRoles);
		$this->assertContains('manager', $permissions->createdRoles);
		$this->assertContains('manager', $permissions->syncedRoles);
	}

	public function test_handle_resets_before_generating_when_reset_is_not_skipped(): void
	{
		$config = new class extends PermissionManagerConfig {
			public function roles(): array
			{
				return [];
			}
		};
		$models = new class($config) extends ModelDiscovery {
			public function names(...$exceptions): Collection
			{
				return collect();
			}
		};
		$names = new PermissionNameResolver(new class($config, $models) extends OperationResolver {
		});
		$permissions = new class extends PermissionSynchronizer {
			public array $events = [];

			public function __construct()
			{
			}

			public function firstOrCreateRole(string $roleName): Model
			{
				$this->events[] = 'role:' . $roleName;
				$role = new class extends Model {
				};
				$role->setAttribute('name', $roleName);

				return $role;
			}

			public function syncModelPermissionsToRole(Model $role, Collection $models): Collection
			{
				return collect();
			}

			public function syncRolePermissions(Model $role, array $permissions): void
			{
			}
		};
		$rolePermissions = new class($config, $names, $permissions) extends RolePermissionResolver {
		};
		$resetter = new class($permissions) extends PermissionResetter {
			public function __construct(private PermissionSynchronizer $permissions)
			{
			}

			public function reset(): void
			{
				$this->permissions->events[] = 'reset';
			}
		};

		$manager = new RoleManager($config, $models, $names, $permissions, $rolePermissions, $resetter);

		$manager->handle();

		$this->assertSame('reset', $permissions->events[0]);
		$this->assertContains('role:root', $permissions->events);
		$this->assertContains('role:admin', $permissions->events);
	}
}
