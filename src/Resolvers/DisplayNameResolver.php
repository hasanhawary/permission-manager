<?php

namespace HasanHawary\PermissionManager\Resolvers;

use HasanHawary\PermissionManager\Support\PermissionManagerConfig;

class DisplayNameResolver
{
	private PermissionManagerConfig $config;

	public function __construct(PermissionManagerConfig $config)
	{
		$this->config = $config;
	}

	public function forRole(string $roleName): array
	{
		if (!$this->config->translationEnabled()) {
			return [
				'en' => $roleName,
				'ar' => $roleName,
			];
		}

		return [
			'en' => $this->translate($roleName, 'en'),
			'ar' => $this->translate($roleName, 'ar'),
		];
	}

	public function forPermission(string $baseModel, string $operation): array
	{
		if (!$this->config->translationEnabled()) {
			return [
				'en' => $baseModel . ' (' . $operation . ')',
				'ar' => $baseModel . ' (' . $operation . ')',
			];
		}

		return [
			'en' => $this->translate($baseModel, 'en') . ' (' . $this->translate($operation, 'en') . ')',
			'ar' => $this->translate($baseModel, 'ar') . ' (' . $this->translate($operation, 'ar') . ')',
		];
	}

	private function translate(string $value, string $lang): string
	{
		return pm_resolveTrans($value, page: $this->config->translationFile(), lang: $lang);
	}
}
