<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Support\ModelMetadataResolver;
use HasanHawary\PermissionManager\Support\PermissionSubject;
use HasanHawary\PermissionManager\Tests\Fixtures\PermissionDisabledModel;
use HasanHawary\PermissionManager\Tests\Fixtures\PermissionEnabledModel;
use PHPUnit\Framework\TestCase;

class ModelMetadataResolverTest extends TestCase
{
	public function test_it_resolves_permission_model_metadata_from_one_owner(): void
	{
		$resolver = new ModelMetadataResolver();
		$subject = new PermissionSubject('PermissionEnabledModel', PermissionEnabledModel::class);

		$this->assertTrue($resolver->canBeUsed($subject));
		$this->assertSame(['read', 'export'], $resolver->operations($subject, ['create', 'read']));
		$this->assertSame('api', $resolver->guardName($subject));
	}

	public function test_it_handles_disabled_or_missing_models_with_safe_defaults(): void
	{
		$resolver = new ModelMetadataResolver();

		$this->assertFalse($resolver->canBeUsed(new PermissionSubject('Disabled', PermissionDisabledModel::class)));
		$this->assertFalse($resolver->canBeUsed(new PermissionSubject('Missing', 'Missing\\Model')));
		$this->assertSame(['read'], $resolver->operations(new PermissionSubject('Missing', 'Missing\\Model'), ['read']));
		$this->assertNull($resolver->guardName(new PermissionSubject('Missing', 'Missing\\Model')));
	}
}
