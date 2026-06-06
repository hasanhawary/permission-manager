<?php

namespace HasanHawary\PermissionManager\Tests\Fixtures;

class PermissionEnabledModel
{
	public bool $inPermission = true;

	public array $basicOperations = ['read'];

	public array $specialOperations = ['export'];

	protected string $guard_name = 'api';
}
