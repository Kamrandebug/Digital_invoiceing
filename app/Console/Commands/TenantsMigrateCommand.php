<?php

namespace App\Console\Commands;

use App\Jobs\MigrateTenantJob;
use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantsMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:migrate 
                            {--tenant= : The ID of a specific tenant to migrate}
                            {--dry-run : Preview which migrations would run without executing them}
                            {--batch-size=10 : The number of tenants to process per chunk}
                            {--rollback : Rollback the last migration operation}
                            {--refresh : Reset and re-run all migrations}
                            {--reset : Rollback all database migrations}
                            {--step= : The number of migrations to rollback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for tenant databases (migrate, rollback, refresh, reset)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        
        // Determine action
        $action = 'migrate';
        if ($this->option('rollback')) $action = 'rollback';
        if ($this->option('refresh')) $action = 'refresh';
        if ($this->option('reset')) $action = 'reset';

        // Collect extra options
        $options = [];
        if ($this->option('step')) {
            $options['--step'] = (int) $this->option('step');
        }

        $query = Tenant::query();

        if ($tenantId) {
            $query->where('id', $tenantId);
        }

        $count = $query->count();
        $this->info("Found {$count} tenants to process for action: {$action}");

        if ($dryRun) {
            $this->warn("Running in DRY-RUN mode. No changes will be made.");
        }

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $query->chunk($batchSize, function ($tenants) use ($dryRun, $bar, $action, $options) {
            foreach ($tenants as $tenant) {
                // Dispatch job for each tenant
                // We pass the action and options down to the job
                
                if ($dryRun) {
                    MigrateTenantJob::dispatchSync($tenant, $action, true, $options);
                } else {
                    MigrateTenantJob::dispatch($tenant, $action, false, $options);
                }
                
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Migration jobs ({$action}) dispatched successfully.");
    }
}
