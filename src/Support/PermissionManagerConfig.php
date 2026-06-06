<?php

namespace HasanHawary\PermissionManager\Support;

class PermissionManagerConfig
{
	public function roleModel(): string
	{
		return (string) $this->configValue('roles.class_paths.role', \HasanHawary\PermissionManager\Models\Role::class);
	}

	public function permissionModel(): string
	{
		return (string) $this->configValue('roles.class_paths.permission', \HasanHawary\PermissionManager\Models\Permission::class);
	}

	public function defaultGuard(): string
	{
		return (string) $this->configValue('roles.default_guard', 'sanctum');
	}

	public function roles(): array
	{
		return (array) $this->configValue('roles.roles', []);
	}

	public function defaultPermissions(): array
	{
		return $this->normalizeStringList($this->configValue('roles.default.permissions', []));
	}

	public function additionalOperations(): array
	{
		return (array) $this->configValue('roles.additional_operations', []);
	}

	public function modelsPath(): ?string
	{
		return $this->basePath($this->configValue('roles.discovery.models_path', 'app/Models'));
	}

	public function modelsNamespace(): string
	{
		return trim((string) $this->configValue('roles.discovery.models_namespace', 'App\\Models'), '\\');
	}

	public function modulesPath(): ?string
	{
		return $this->basePath($this->configValue('roles.discovery.modules_path', 'Modules'));
	}

	public function modulesModelsPath(): string
	{
		return trim((string) $this->configValue('roles.discovery.modules_models_path', 'App/Models'), '/\\');
	}

	public function modulesNamespace(): string
	{
		return trim((string) $this->configValue('roles.discovery.modules_namespace', 'Modules'), '\\');
	}

	public function translationEnabled(): bool
	{
		return (bool) $this->configValue('roles.translate.enabled', true);
	}

	public function translationFile(): string
	{
		return (string) $this->configValue('roles.translate.file', 'roles');
	}

	protected function normalizeStringList($values): array
	{
		return collect(is_array($values) ? $values : [$values])
			->filter(fn($value) => is_string($value) && trim($value) !== '')
			->map(fn(string $value) => trim($value))
			->unique()
			->values()
			->all();
	}

	private function basePath($path): ?string
	{
		if (!is_string($path) || trim($path) === '') {
			return null;
		}

		$path = trim($path);

		if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1) {
			return $path;
		}

		if (function_exists('base_path')) {
			return base_path($path);
		}

		return null;
	}

	protected function configValue(string $key, $default = null)
	{
		if (!function_exists('config')) {
			return $default;
		}

		try {
			return config($key, $default);
		} catch (\Throwable $exception) {
			return $default;
		}
	}
}
