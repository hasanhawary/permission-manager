<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Discovery\ModelDiscovery;
use HasanHawary\PermissionManager\Resolvers\OperationResolver;
use HasanHawary\PermissionManager\Support\PermissionManagerConfig;
use HasanHawary\PermissionManager\Support\PermissionSubject;
use PHPUnit\Framework\TestCase;

class OperationResolverTest extends TestCase
{
	public function test_additional_operations_replace_basic_operations_by_default(): void
	{
		$resolver = $this->resolver([
			[
				'name' => 'Report',
				'operations' => [' read ', 'export', 'read', '', null],
			],
		]);

		$this->assertSame(['read', 'export'], $resolver->forModel('Report'));
	}

	public function test_additional_operations_merge_basic_operations_only_when_basic_is_truthy(): void
	{
		$resolver = $this->resolver([
			[
				'name' => 'Report',
				'operations' => ['export'],
				'basic' => false,
			],
			[
				'name' => 'Dashboard',
				'operations' => ['main'],
				'basic' => true,
			],
		]);

		$this->assertSame(['export'], $resolver->forModel('Report'));
		$this->assertSame(['create', 'read', 'update', 'delete', 'main'], $resolver->forModel('Dashboard'));
	}

	public function test_missing_additional_operation_list_is_handled_explicitly(): void
	{
		$resolver = $this->resolver([
			[
				'name' => 'Report',
				'basic' => true,
			],
			[
				'name' => 'Dashboard',
			],
		]);

		$this->assertSame(['create', 'read', 'update', 'delete'], $resolver->forModel('Report'));
		$this->assertSame([], $resolver->forModel('Dashboard'));
	}

	public function test_string_additional_operation_is_normalized_without_type_errors(): void
	{
		$resolver = $this->resolver([
			[
				'name' => 'Report',
				'operations' => ' export ',
			],
		]);

		$this->assertSame(['export'], $resolver->forModel('Report'));
	}

	private function resolver(array $additionalOperations): OperationResolver
	{
		$config = new class($additionalOperations) extends PermissionManagerConfig {
			private array $additionalOperations;

			public function __construct(array $additionalOperations)
			{
				$this->additionalOperations = $additionalOperations;
			}

			public function additionalOperations(): array
			{
				return $this->additionalOperations;
			}
		};

		$models = new class($config) extends ModelDiscovery {
			public function subjectFor(string $modelName): PermissionSubject
			{
				return PermissionSubject::additionalOperation($modelName);
			}
		};

		return new OperationResolver($config, $models);
	}
}
