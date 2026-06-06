<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Services\PermissionResetter;
use PHPUnit\Framework\TestCase;

class PermissionResetterTest extends TestCase
{
	public function test_it_owns_the_complete_spatie_reset_table_list(): void
	{
		$resetter = new class extends PermissionResetter {
			protected function tableName(string $default): string
			{
				return 'custom_' . $default;
			}
		};

		$this->assertSame([
			'custom_model_has_permissions',
			'custom_model_has_roles',
			'custom_role_has_permissions',
			'custom_permissions',
			'custom_roles',
		], $resetter->tables());
	}
}
