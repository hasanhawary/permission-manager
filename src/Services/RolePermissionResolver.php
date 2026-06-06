<?php

namespace HasanHawary\PermissionManager\Services;

use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;

class RolePermissionResolver
{
	private PermissionManagerConfig $config;

	private PermissionNameResolver $names;

	private PermissionSynchronizer $permissions;

	public function __construct(
		PermissionManagerConfig $config,
		PermissionNameResolver $names,
		PermissionSynchronizer $permissions
	) {
		$this->config = $config;
		$this->names = $names;
		$this->permissions = $permissions;
	}

	public function resolve(array $details): array
	{
		$permissions = $this->names->customOperationNames((array) ($details['permissions'] ?? []));

		if (!empty($details['like'])) {
			return $this->mergeLikeRolePermissions($details, $permissions);
		}

		if (isset($details['models'])) {
			return $this->mergeModelPermissions($details, $permissions);
		}

		return $permissions;
	}

	private function mergeLikeRolePermissions(array $details, array $permissions): array
	{
		$roleModel = $this->config->roleModel();
		$likeRole = $roleModel::query()->where('name', $details['like'])->with('permissions')->first();
		$rolePermissions = $likeRole?->permissions->pluck('name')->toArray() ?? [];

		return $this->mergeByType($rolePermissions, $permissions, $details['type'] ?? null);
	}

	private function mergeModelPermissions(array $details, array $permissions): array
	{
		$modelPermissions = collect((array) $details['models'])
			->flatMap(fn(string $model) => $this->permissions->createModelPermissions($model)->pluck('name')->toArray())
			->unique()
			->values()
			->all();

		return $this->mergeByType($modelPermissions, $permissions, $details['type'] ?? null);
	}

	private function mergeByType(array $basePermissions, array $permissions, ?string $type): array
	{
		if ($type === 'exception') {
			return array_values(array_diff($basePermissions, $permissions));
		}

		if ($type === 'added') {
			return array_values(array_unique(array_merge($basePermissions, $permissions)));
		}

		return $basePermissions;
	}
}
