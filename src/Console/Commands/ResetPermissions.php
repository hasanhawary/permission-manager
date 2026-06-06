<?php

namespace HasanHawary\PermissionManager\Console\Commands;

use HasanHawary\PermissionManager\Facades\Access;
use Illuminate\Console\Command;

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

        $this->info($skipReset
            ? 'Skipping reset. Running PermissionManager handler...'
            : 'Resetting permissions and running PermissionManager handler...'
        );

        Access::handle($skipReset);

        $this->info('PermissionManager executed successfully.');
    }

}
