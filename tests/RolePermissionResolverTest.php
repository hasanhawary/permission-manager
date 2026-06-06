<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Resolvers\OperationResolver;
use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use HasanHawary\PermissionManager\Services\PermissionSynchronizer;
use HasanHawary\PermissionManager\Services\RolePermissionResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RolePermissionResolverTest extends TestCase
{
	public function test_it_adds_custom_permissions_to_model_permissions(): void
	{
		$resolver = $this->resolverWithModelPermissions(['read-project', 'update-project']);

		$this->assertSame([
			'read-project',
			'update-project',
			'delete-project',
		], $resolver->resolve([
			'models' => ['Project'],
			'type' => 'added',
			'permissions' => [
				'Project' => ['delete'],
			],
		]));
	}

	public function test_it_removes_exception_permissions_from_model_permissions(): void
	{
		$resolver = $this->resolverWithModelPermissions(['read-project', 'delete-project']);

		$this->assertSame([
			'read-project',
		], $resolver->resolve([
			'models' => ['Project'],
			'type' => 'exception',
			'permissions' => [
				'Project' => ['delete'],
			],
		]));
	}

	public function test_it_resolves_direct_custom_permissions_without_models_or_inheritance(): void
	{
		$resolver = $this->resolverWithModelPermissions([]);

		$this->assertSame([
			'read-dashboard',
		], $resolver->resolve([
			'permissions' => [
				'Dashboard' => ['read'],
			],
		]));
	}

	private function resolverWithModelPermissions(array $permissionNames): RolePermissionResolver
	{
		$names = new PermissionNameResolver(new class extends OperationResolver {
			public function __construct()
			{
			}

			public function forModel(string $modelName): array
			{
				return ['read', 'update', 'delete'];
			}
		});

		$permissions = new class($permissionNames) extends PermissionSynchronizer {
			private array $permissionNames;

			public function __construct(array $permissionNames)
			{
				$this->permissionNames = $permissionNames;
			}

			public function createModelPermissions(string $modelName): Collection
			{
				return collect($this->permissionNames)
					->map(fn(string $name) => (object) ['name' => $name]);
			}
		};

		return new RolePermissionResolver(new PermissionManagerConfig(), $names, $permissions);
	}
}
