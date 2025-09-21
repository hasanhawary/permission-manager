<?php

namespace HasanHawary\PermissionManager\Console\Commands;

use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:reset {--skip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset all permissions, roles, and related pivot tables';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $skipReset = $this->option('skip');
        $this->info('Resetting permissions...');

        if (!$skipReset) {

            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } catch (\Throwable) {
                // ignore if not supported
            }

            DB::table('model_has_permissions')->truncate();
            DB::table('role_has_permissions')->truncate();
            DB::table('permissions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->info('Permissions reset successfully. Running PermissionManager handler...');

        Access::handle($skipReset);

        $this->info('PermissionManager executed successfully.');
    }

}
