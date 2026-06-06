<?php

namespace HasanHawary\PermissionManager\Services;

use Exception;
use HasanHawary\PermissionManager\Resolvers\DisplayNameResolver;
use HasanHawary\PermissionManager\Resolvers\GuardResolver;
use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use HasanHawary\PermissionManager\Support\PermissionDefinition;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class PermissionSynchronizer
{
	private PermissionManagerConfig $config;

	private PermissionNameResolver $names;

	private GuardResolver $guards;

	private DisplayNameResolver $displayNames;

	public function __construct(
		PermissionManagerConfig $config,
		PermissionNameResolver $names,
		GuardResolver $guards,
		DisplayNameResolver $displayNames
	) {
		$this->config = $config;
		$this->names = $names;
		$this->guards = $guards;
		$this->displayNames = $displayNames;
	}

	public function firstOrCreateRole(string $roleName): Model
	{
		$roleModel = $this->config->roleModel();

		return $roleModel::firstOrCreate(
			['name' => $roleName, 'guard_name' => $this->guards->forRole()],
			['display_name' => $this->displayNames->forRole($roleName)]
		);
	}

	public function createModelPermissions(string $modelName): Collection
	{
		$permissionModel = $this->config->permissionModel();
		return $this->names->definitionsForModel($modelName)
			->map(function (PermissionDefinition $definition) use ($permissionModel) {
				return $permissionModel::firstOrCreate([
					'name' => $definition->name(),
					'guard_name' => $this->guards->forModel($definition->modelName()),
				], [
					'group' => $definition->group(),
					'display_name' => $this->displayNames->forPermission(
						class_basename($definition->modelName()),
						$definition->operation()
					),
				]);
			});
	}

	/**
	 * @throws Exception
	 */
	public function syncModelPermissionsToRole(Model $role, Collection $models): Collection
	{
		$permissions = $models->flatMap(fn(string $model) => $this->createModelPermissions($model))->filter();
		$validPermissions = $this->ensurePermissionsMatchRoleGuard($permissions, $role)
			->unique(fn($permission) => $permission->name . '|' . $permission->guard_name)
			->values();

		try {
			if ($validPermissions->isNotEmpty()) {
				$role->syncPermissions($validPermissions);
			}
		} catch (Exception $exception) {
			\Log::error("Failed to assign permissions to role: {$role->name}", [
				'error' => $exception->getMessage(),
				'permissions' => $validPermissions->pluck('name')->toArray(),
			]);

			throw $exception;
		}

		app()[PermissionRegistrar::class]->forgetCachedPermissions();

		return $validPermissions;
	}

	public function syncRolePermissions(Model $role, array $permissions): void
	{
		$validPermissions = $this->ensurePermissionNamesExistForRole(
			array_values(array_unique(array_merge($permissions, $this->config->defaultPermissions()))),
			$role
		);

		$role->syncPermissions($validPermissions);
		app()[PermissionRegistrar::class]->forgetCachedPermissions();
	}

	private function ensurePermissionNamesExistForRole(array $permissionNames, Model $role): Collection
	{
		$permissionModel = $this->config->permissionModel();

		return collect($permissionNames)
			->filter(fn($permissionName) => is_string($permissionName) && trim($permissionName) !== '')
			->map(fn(string $permissionName) => $permissionModel::firstOrCreate([
				'name' => trim($permissionName),
				'guard_name' => $role->guard_name,
			]))
			->unique(fn($permission) => $permission->name . '|' . $permission->guard_name)
			->values();
	}

	private function ensurePermissionsMatchRoleGuard(Collection $permissions, Model $role): Collection
	{
		$permissionModel = $this->config->permissionModel();

		return $permissions->map(function ($permission) use ($permissionModel, $role) {
			if (is_string($permission)) {
				return $permissionModel::firstOrCreate([
					'name' => $permission,
					'guard_name' => $role->guard_name,
				]);
			}

			if ($permission->guard_name !== $role->guard_name) {
				return $permissionModel::firstOrCreate([
					'name' => $permission->name,
					'guard_name' => $role->guard_name,
				]);
			}

			return $permission;
		});
	}
}
