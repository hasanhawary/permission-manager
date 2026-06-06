<?php

namespace HasanHawary\PermissionManager\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
	public bool $inPermission = true;

	public array $basicOperations = ['create', 'update', 'delete'];

	public array $specialOperations = ['view-all', 'view-own', 'toggle-active'];

	protected $fillable = [
		'name',
		'guard_name',
		'display_name',
		'is_active',
	];

	protected $casts = [
		'display_name' => 'array',
		'is_active' => 'boolean',
	];
}
