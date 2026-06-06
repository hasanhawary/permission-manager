<?php

namespace HasanHawary\PermissionManager\Tests;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
	public function test_translation_helper_falls_back_without_laravel_application_helpers(): void
	{
		$this->assertSame('admin', pm_resolveTrans('admin', page: 'roles', lang: 'en'));
	}
}
