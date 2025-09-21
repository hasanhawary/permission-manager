<?php

namespace HasanHawary\PermissionManager;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class RoleManager
{
    public const array BASIC_ROLES = ['root', 'admin'];

    public const array BASIC_OPERATIONS = ['create', 'read', 'update', 'delete'];

    private array $moduleList = [];

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
     * @return Role
     */
    private function firstOrCreateRole(string $roleName): Role
    {
        $translateEnabled = (bool) (config('roles.translate.enabled') ?? true);
        $file = config('roles.translate.file', 'roles');

        $displayName = [
            'en' => $translateEnabled ? pm_resolveTrans($roleName, page: $file, lang: 'en') : $roleName,
            'ar' => $translateEnabled ? pm_resolveTrans($roleName, page: $file, lang: 'ar') : $roleName,
        ];

        return Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'sanctum'],
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
        $likeRole = Role::query()->where('name', $details['like'])->with('permissions')->first();
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
        return collect(File::files(app_path('Models')))
            ->filter(fn($file) => Str::contains($file->getFilename(), 'php'))
            ->map(fn($file) => str_replace('.php', '', $file->getFilename()))
            ->filter(function ($class) {
                $className = 'App\\Models\\' . $class;
                $object = new $className();
                return is_object($object) && (bool)$object->inPermission !== false;
            })
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
     * @return Permission
     */
    private function findOrCreatePermission(string $model, string $operation): Permission
    {
        $modelName = Str::snake($model, '-');


        return Permission::firstOrCreate([
            'name' => "$operation-$modelName",
            'guard_name' => 'sanctum'
        ], [
            'group' => $modelName,
            'display_name' => (function() use ($model, $operation) {
                $translateEnabled = (bool) (config('roles.translate.enabled') ?? true);
                if (!$translateEnabled) {
                    return [
                        'en' => $model . ' (' . $operation . ')',
                        'ar' => $model . ' (' . $operation . ')',
                    ];
                }
                $file = config('roles.translate.file', 'roles');
                return [
                    'en' => pm_resolveTrans($model, page: $file, lang: 'en') . ' (' . pm_resolveTrans($operation, page: $file, lang: 'en') . ')',
                    'ar' => pm_resolveTrans($model, page: $file, lang: 'ar') . ' (' . pm_resolveTrans($operation, page: $file, lang: 'ar') . ')',
                ];
            })()
        ]);
    }

    /**
     * @param Role $role
     * @param Collection $models
     * @return Collection
     */
    private function assignModelPermissionsToRole(Role $role, Collection $models): Collection
    {
        return $models->flatMap(fn($model) => $this->createModelPermissions($model))
            ->each(fn($permission) => $role->givePermissionTo($permission));
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
}
