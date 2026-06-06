<?php

namespace HasanHawary\PermissionManager\Tests;

use HasanHawary\PermissionManager\PermissionManagerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
	protected function getPackageProviders($app): array
	{
		return [
			PermissionServiceProvider::class,
			PermissionManagerServiceProvider::class,
		];
	}

	protected function defineEnvironment($app): void
	{
		$app['config']->set('database.default', 'testing');
		$app['config']->set('database.connections.testing', [
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => '',
		]);
		$app['config']->set('roles.discovery.models_path', '');
		$app['config']->set('roles.discovery.modules_path', '');
		$app['config']->set('cache.default', 'array');
	}
}
