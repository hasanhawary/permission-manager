<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\Resolvers\OperationResolver;
use HasanHawary\PermissionManager\Resolvers\PermissionNameResolver;
use PHPUnit\Framework\TestCase;

class PermissionNameResolverTest extends TestCase
{
	public function test_it_normalizes_model_permission_names_from_one_owner(): void
	{
		$resolver = new PermissionNameResolver(new class extends OperationResolver {
			public function __construct()
			{
			}

			public function forModel(string $modelName): array
			{
				return ['read', 'export'];
			}
		});

		$this->assertSame('read-report-builder', $resolver->name('ReportBuilder', 'read'));
		$this->assertSame(['read-report-builder', 'export-report-builder'], $resolver->allForModel('ReportBuilder'));
	}

	public function test_it_expands_basic_and_wildcard_custom_operations_consistently(): void
	{
		$resolver = new PermissionNameResolver(new class extends OperationResolver {
			public function __construct()
			{
			}

			public function forModel(string $modelName): array
			{
				return ['read', 'archive'];
			}
		});

		$this->assertSame([
			'create-project',
			'read-project',
			'update-project',
			'delete-project',
			'read-report-builder',
			'archive-report-builder',
		], $resolver->customOperationNames([
			'Project' => ['basic'],
			'ReportBuilder' => ['*'],
		]));
	}

	public function test_it_keeps_operation_as_explicit_data_when_operation_contains_hyphen(): void
	{
		$resolver = new PermissionNameResolver(new class extends OperationResolver {
			public function __construct()
			{
			}

			public function forModel(string $modelName): array
			{
				return ['bulk-export'];
			}
		});

		$definition = $resolver->definitionsForModel('Report')->first();

		$this->assertSame('bulk-export-report', $definition->name());
		$this->assertSame('bulk-export', $definition->operation());
		$this->assertSame('report', $definition->group());
	}
}
