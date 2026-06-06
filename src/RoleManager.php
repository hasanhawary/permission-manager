<?php

namespace HasanHawary\PermissionManager;

use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Resolvers\OperationResolver;
use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use HasanHawary\PermissionManager\Services\PermissionResetter;
use HasanHawary\PermissionManager\Services\PermissionSynchronizer;
use HasanHawary\PermissionManager\Services\RolePermissionResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use Illuminate\Support\Collection;
use RuntimeException;

class RoleManager
{
	public const BASIC_ROLES = ['root', 'admin'];

	public const BASIC_OPERATIONS = OperationResolver::BASIC_OPERATIONS;

	private PermissionManagerConfig $config;

	private ModelDiscovery $models;

	private PermissionNameResolver $names;

	private PermissionSynchronizer $permissions;

	private RolePermissionResolver $rolePermissions;

	private PermissionResetter $resetter;

	public function __construct(
		?PermissionManagerConfig $config = null,
		?ModelDiscovery $models = null,
		?PermissionNameResolver $names = null,
		?PermissionSynchronizer $permissions = null,
		?RolePermissionResolver $rolePermissions = null,
		?PermissionResetter $resetter = null
	) {
		$this->config = $config ?? $this->resolveFromContainer(PermissionManagerConfig::class);
		$this->models = $models ?? $this->resolveFromContainer(ModelDiscovery::class);
		$this->names = $names ?? $this->resolveFromContainer(PermissionNameResolver::class);
		$this->permissions = $permissions ?? $this->resolveFromContainer(PermissionSynchronizer::class);
		$this->rolePermissions = $rolePermissions ?? $this->resolveFromContainer(RolePermissionResolver::class);
		$this->resetter = $resetter ?? new PermissionResetter();
	}

	public function handle(bool $skipReset = false): void
	{
		if (!$skipReset) {
			$this->resetter->reset();
		}

		$this->createRole();

		collect($this->config->roles())->each(function ($details, $role) {
			$roleModel = $this->permissions->firstOrCreateRole($role);
			$permissions = $this->rolePermissions->resolve((array) $details);

			$this->permissions->syncRolePermissions($roleModel, $permissions);
		});
	}

	public function getModels(...$exceptions): Collection
	{
		return $this->models->names(...$exceptions);
	}

	public function createRole(array $roles = [null], ?Collection $models = null): void
	{
		collect(array_filter(array_merge(self::BASIC_ROLES, $roles)))
			->each(function ($roleName) use ($models) {
				$roleModel = $this->permissions->firstOrCreateRole($roleName);
				$modelNames = $models ?? $this->getModels();
				$permissions = $this->permissions
					->syncModelPermissionsToRole($roleModel, $modelNames)
					->pluck('name')
					->toArray();

				$this->permissions->syncRolePermissions($roleModel, $permissions);
			});
	}

	public function createModelPermissions(string $modelName): Collection
	{
		return $this->permissions->createModelPermissions($modelName);
	}

	public function getModelOperationsMapping(string $modelName): array
	{
		return $this->names->allForModel($modelName);
	}

	private function resolveFromContainer(string $class)
	{
		if (!function_exists('app')) {
			throw new RuntimeException('RoleManager requires Laravel container services. In plain PHP, pass all dependencies explicitly or use the framework-independent resolver/support classes directly.');
		}

		return app($class);
	}
}
