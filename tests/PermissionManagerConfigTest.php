<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use PHPUnit\Framework\TestCase;

class PermissionManagerConfigTest extends TestCase
{
	public function test_it_normalizes_configured_permission_lists_explicitly(): void
	{
		$config = new class extends PermissionManagerConfig {
			public function normalize($values): array
			{
				return $this->normalizeStringList($values);
			}
		};

		$this->assertSame([
			'read-dashboard',
			'0',
		], $config->normalize([
			' read-dashboard ',
			'read-dashboard',
			'',
			null,
			false,
			'0',
		]));

		$this->assertSame(['export-report'], $config->normalize(' export-report '));
	}

	public function test_it_has_package_model_defaults_without_laravel_config_helper(): void
	{
		$config = new PermissionManagerConfig();

		$this->assertSame(\HasanHawary\PermissionManager\Models\Role::class, $config->roleModel());
		$this->assertSame(\HasanHawary\PermissionManager\Models\Permission::class, $config->permissionModel());
	}
}
