<?php

namespace HasanHawary\PermissionManager\Facades;

use HasanHawary\PermissionManager\RoleManager as Manager;
use Illuminate\Support\Facades\Facade;

/**
 * Access facade for interacting with the PermissionManager.
 *
 * @method static void handle(bool $skipReset = false)
 * @see \HasanHawary\PermissionManager\RoleManager
 */
class Access extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Manager::class;
    }
}
