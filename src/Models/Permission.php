<?php

namespace HasanHawary\PermissionManager\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
	public bool $inPermission = true;

	protected $fillable = [
		'name',
		'guard_name',
		'display_name',
		'group',
	];

	protected $casts = [
		'display_name' => 'array',
	];
}
