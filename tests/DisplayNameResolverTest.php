<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Resolvers\DisplayNameResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use PHPUnit\Framework\TestCase;

class DisplayNameResolverTest extends TestCase
{
	public function test_it_builds_display_names_without_translation_when_disabled(): void
	{
		$resolver = new DisplayNameResolver(new class extends PermissionManagerConfig {
			public function translationEnabled(): bool
			{
				return false;
			}
		});

		$this->assertSame([
			'en' => 'admin',
			'ar' => 'admin',
		], $resolver->forRole('admin'));

		$this->assertSame([
			'en' => 'Report (bulk-export)',
			'ar' => 'Report (bulk-export)',
		], $resolver->forPermission('Report', 'bulk-export'));
	}
}
