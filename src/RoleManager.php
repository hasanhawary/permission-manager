<?php

namespace HasanHawary\PermissionManager;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class RoleManager
{
	public const array BASIC_ROLES = ['root', 'admin'];

	public const array BASIC_OPERATIONS = ['create', 'read', 'update', 'delete'];

	private array $moduleList = [];

	protected string $roleModel;

	protected string $permissionModel;

	public function __construct()
	{
		$this->roleModel = config('roles.class_paths.role');
		$this->permissionModel = config('roles.class_paths.permission');
	}

	public function handle(bool $skipReset = false): void
	{
		$service = new self();
		$service->createRole(); // Create a basic role and assign all permissions to this role

		if ($skipReset) {
			return;
		}

		$roles = collect(config('roles.roles'));

		$roles->each(function ($details, $role) use ($service) {
			$roleModel = $service->firstOrCreateRole($role);

			$permissions = $service->getRolePermissions($details);

			// Handle default permissions for every role
			$defaultPermissions = config('roles.default.permissions');

			$roleModel->syncPermissions(array_values(array_unique(array_merge($permissions, $defaultPermissions))));
		});
	}

	/**
	 * @param string $roleName
	 * @return Model
	 */
	private function firstOrCreateRole(string $roleName): Model
	{
		$translateEnabled = (bool)(config('roles.translate.enabled') ?? true);
		$file = config('roles.translate.file', 'roles');

		$displayName = [
			'en' => $translateEnabled ? pm_resolveTrans($roleName, page: $file, lang: 'en') : $roleName,
			'ar' => $translateEnabled ? pm_resolveTrans($roleName, page: $file, lang: 'ar') : $roleName,
		];

		return $this->roleModel::firstOrCreate(
			['name' => $roleName, 'guard_name' => $this->getGuardName()],
			[
				'display_name' => $displayName,
			]
		);
	}

	/**
	 * @param array $details
	 * @return array
	 */
	private function getRolePermissions(array $details): array
	{
		$permissions = $this->handleCustomOperation($details['permissions']);

		if (isset($details['like']) && $details['like']) {
			$permissions = $this->mergeLikeRolePermissions($details, $permissions);
		}

		if (isset($details['models']) && !isset($details['like'])) {
			$permissions = $this->mergeModelPermissions($details, $permissions);
		}

		return $permissions;
	}

	/**
	 * @param array $details
	 * @param array $permissions
	 * @return array
	 */
	private function mergeLikeRolePermissions(array $details, array $permissions): array
	{
		$likeRole = $this->roleModel::query()->where('name', $details['like'])->with('permissions')->first();
		$rolePermissions = $likeRole?->permissions->pluck('name')->toArray() ?? [];

		if (isset($details['type'])) {
			if ($details['type'] === 'exception') {
				return array_diff($rolePermissions, $permissions);
			}

			if ($details['type'] === 'added') {
				return array_merge($rolePermissions, $permissions);
			}

			return $rolePermissions;
		}

		return $rolePermissions;
	}

	/**
	 * @param array $details
	 * @param array $permissions
	 * @return array
	 */
	private function mergeModelPermissions(array $details, array $permissions): array
	{
		$modelPermissions = $this->handleModelPermissions($details['models']);

		if (isset($details['type'])) {
			if ($details['type'] === 'exception') {
				return array_diff($modelPermissions, $permissions);
			}

			if ($details['type'] === 'added') {
				return array_merge($modelPermissions, $permissions);
			}

			return $modelPermissions;
		}

		return $modelPermissions;
	}

	/**
	 * @param  ...$exceptions
	 * @return Collection
	 */
	public function getModels(...$exceptions): Collection
	{
		$additionalModels = array_column(config('roles.additional_operations'), 'name');
		$generalModels = $this->getGeneralModels();
		$moduleModels = $this->getModuleModels();

		return $generalModels->merge($moduleModels)->merge($additionalModels)
			->filter(fn($model) => !in_array($model, $exceptions));
	}

	/**
	 * @return Collection
	 */
	private function getGeneralModels(): Collection
	{
		return collect(File::allFiles(app_path('Models')))
			->filter(fn($file) => $file->getExtension() === 'php')
			->map(function ($file) {
				// Get the relative path from Models directory and build namespace
				$relativePath = str_replace(
					[app_path('Models') . DIRECTORY_SEPARATOR, '.php'],
					['', ''],
					$file->getRealPath()
				);

				// Convert path separators to namespace separators
				$namespaceClass = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

				return [
					'class' => $namespaceClass, // e.g., "Admin\User"
					'full_namespace' => 'App\\Models\\' . $namespaceClass, // e.g., "App\Models\Admin\User"
					'simple_name' => class_basename($namespaceClass) // e.g., "User"
				];
			})
			->filter(function ($data) {
				$className = $data['full_namespace'];

				if (!class_exists($className)) {
					return false;
				}

				$reflection = new \ReflectionClass($className);
				if ($reflection->isAbstract() || $reflection->isInterface()) {
					return false;
				}

				try {
					$object = new $className();
					return is_object($object) && (bool)($object->inPermission ?? true) !== false;
				} catch (\Throwable $e) {
					return false;
				}
			})
			->map(fn($data) => $data['class']) // Return just the class part like "Admin\User" or "User"
			->values();
	}

	/**
	 * @return Collection
	 */
	private function getModuleModels(): Collection
	{
		$modulesPath = base_path('Modules');

		if (!File::isDirectory($modulesPath)) {
			return collect();
		}

		$moduleDirectories = collect(File::directories($modulesPath))
			->filter(fn($directory) => !Str::startsWith(basename($directory), '.'));
		return $moduleDirectories->flatMap(function ($moduleDirectory) {
			$modelsPath = $moduleDirectory . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Models';

			if (File::isDirectory($modelsPath)) {
				return collect(File::files($modelsPath))
					->filter(fn($file) => Str::endsWith($file->getFilename(), '.php'))
					->map(function ($file) use ($moduleDirectory) {
						$className = $file->getBasename('.php');
						$moduleName = basename($moduleDirectory);
						$classNamespace = "Modules\\$moduleName\\App\\Models\\$className";
						$object = new $classNamespace();

						if (is_object($object) && $object->inPermission !== false) {
							$this->moduleList[$className] = $moduleName;
							return $className;
						}

						return null;
					})
					->filter();
			}

			return collect();
		});
	}

	/**
	 * @param array $roles
	 * @param Collection|null $models
	 * @return void
	 */
	public function createRole(array $roles = [null], Collection $models = null): void
	{
		collect(array_filter(array_merge(self::BASIC_ROLES, $roles)))->each(function ($roleName) use ($models) {
			$roleModel = $this->firstOrCreateRole($roleName);

			$models = $models ?? $this->getModels();
			$permissions = $this->assignModelPermissionsToRole($roleModel, $models)->pluck('name')->toArray();

			$defaultPermissions = config('roles.default.permissions');
			$roleModel->syncPermissions(array_values(array_unique(array_merge($permissions, $defaultPermissions))));
		});
	}

	/**
	 * @param string $modelName
	 * @return Collection
	 */
	public function createModelPermissions(string $modelName): Collection
	{
		return collect($this->prepareOperations($modelName))
			->map(fn($operation) => $this->findOrCreatePermission($modelName, $operation));
	}

	/**
	 * @param string $model
	 * @param string $operation
	 * @return Model
	 */
	private function findOrCreatePermission(string $model, string $operation): Model
	{
		$baseModel = class_basename($model);
		$modelName = Str::snake($baseModel, '-');

		return $this->permissionModel::firstOrCreate([
			'name' => "$operation-$modelName",
			'guard_name' => $this->getGuardName($model)
		], [
			'group' => $modelName,
			'display_name' => (function () use ($baseModel, $operation) {
				$translateEnabled = (bool)(config('roles.translate.enabled') ?? true);
				if (!$translateEnabled) {
					return [
						'en' => $baseModel . ' (' . $operation . ')',
						'ar' => $baseModel . ' (' . $operation . ')',
					];
				}
				$file = config('roles.translate.file', 'roles');
				return [
					'en' => pm_resolveTrans($baseModel, page: $file, lang: 'en') . ' (' . pm_resolveTrans($operation, page: $file, lang: 'en') . ')',
					'ar' => pm_resolveTrans($baseModel, page: $file, lang: 'ar') . ' (' . pm_resolveTrans($operation, page: $file, lang: 'ar') . ')',
				];
			})()
		]);
	}

	/**
	 * @param Model $role
	 * @param Collection $models
	 * @return Collection
	 * @throws Exception
	 */
	private function assignModelPermissionsToRole(Model $role, Collection $models): Collection
	{
		// Step 1: Create all permissions from models
		$permissions = $models->flatMap(function ($model) {
			return $this->createModelPermissions($model);
		})->filter(); // Remove any null/empty values

		// Step 2: Ensure permissions exist and match guard
		$validPermissions = $permissions->map(function ($permission) use ($role) {
			// Handle both string and Permission object
			if (is_string($permission)) {
				return $this->permissionModel::firstOrCreate(
					['name' => $permission, 'guard_name' => $role->guard_name]
				);
			}

			// If it's already a Permission object, ensure guard matches
			if ($permission->guard_name !== $role->guard_name) {
				return $this->permissionModel::firstOrCreate(
					['name' => $permission->name, 'guard_name' => $role->guard_name]
				);
			}

			return $permission;
		});

		// Step 3: Assign all permissions to role (bulk assignment)
		try {
			if ($validPermissions->isNotEmpty()) {
				$role->syncPermissions($validPermissions); // Use sync instead of give
				// Or use: $role->givePermissionTo($validPermissions->pluck('name')->toArray());
			}
		} catch (Exception $e) {
			\Log::error("Failed to assign permissions to role: {$role->name}", [
				'error' => $e->getMessage(),
				'permissions' => $validPermissions->pluck('name')->toArray()
			]);
			throw $e;
		}

		// Step 4: Clear permission cache
		app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

		return $validPermissions;
	}

	/**
	 * @param string $modelName
	 * @return string[]
	 */
	private function prepareOperations(string $modelName): array
	{
		$operations = self::BASIC_OPERATIONS;
		$additionalOperations = config('roles.additional_operations');

		$className = 'App\\Models\\' . $modelName;
		if (isset($this->moduleList[$modelName])) {
			$className = "Modules\\{$this->moduleList[$modelName]}\\App\\Models\\$modelName";
		}

		try {
			$object = new $className();
			if (is_object($object) && $object->inPermission !== false) {
				$operations = $object->basicOperations ?? $operations;
				if (!empty($object->specialOperations)) {
					$operations = array_unique(array_merge($operations, $object->specialOperations));
				}
			}
		} catch (Throwable) {
			// Log error or handle accordingly
		}

		if ($additionalOperation = collect($additionalOperations)->firstWhere('name', $modelName)) {
			$operations = isset($additionalOperation['basic'])
				? array_unique(array_merge($operations, $additionalOperation['operations']))
				: $additionalOperation['operations'];
		}

		return $operations;
	}

	/**
	 * @param string $modelName
	 * @return array
	 */
	public function getModelOperationsMapping(string $modelName): array
	{
		$modelOperationsMapping = [];
		$operations = $this->prepareOperations($modelName);
		$modelName = strtolower($modelName);
		foreach ($operations as $operation) {
			$modelOperationsMapping[] = "$operation-$modelName";
		}

		return $modelOperationsMapping;
	}

	/**
	 * @param array $models
	 * @return array
	 */
	private function handleCustomOperation(array $models): array
	{
		return collect($models)->flatMap(function ($operations, $model) {
			return collect($operations)->flatMap(function ($operation) use ($model) {
				if ($operation === 'basic') {
					return collect(self::BASIC_OPERATIONS)->map(fn($op) => "$op-$model");
				}

				if ($operation === '*') {
					return $this->getModelOperationsMapping($model);
				}

				return ["$operation-$model"];
			});
		})->unique()->toArray();
	}

	/**
	 * @param array $models
	 * @return array
	 */
	private function handleModelPermissions(array $models): array
	{
		return collect($models)->flatMap(fn($model) => $this->createModelPermissions($model)->pluck('name')->toArray())
			->unique()
			->toArray();
	}


	/**
	 * Get guard name from model or fallback to default config
	 *
	 * @param string|null $modelName
	 * @return string
	 */
	private function getGuardName(string $modelName = null): string
	{
		if (!$modelName) {
			return config('roles.default_guard', 'sanctum');
		}
		$className = 'App\\Models\\' . $modelName;

		if (isset($this->moduleList[$modelName])) {
			$className = "Modules\\{$this->moduleList[$modelName]}\\App\\Models\\$modelName";
		}

		try {
			if (class_exists($className)) {
				$object = new $className();

				if (property_exists($object, 'guard_name')) {
					$reflection = new \ReflectionProperty($className, 'guard_name');
					$reflection->setAccessible(true);
					$guardName = $reflection->getValue($object);

					if (!empty($guardName)) {
						return $guardName;
					}
				}
			}
		} catch (Throwable) {
			// Fall through to default
		}

		return config('roles.default_guard', 'sanctum');
	}
}
