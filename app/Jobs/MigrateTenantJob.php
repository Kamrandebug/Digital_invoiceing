<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantMigrationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tenant;
    public $action; // 'migrate', 'rollback', 'refresh'
    public $dryRun;
    public $options;

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant, string $action = 'migrate', bool $dryRun = false, array $options = [])
    {
        $this->tenant = $tenant;
        $this->action = $action;
        $this->dryRun = $dryRun;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Configure tenant connection
            Config::set('database.connections.tenant.database', $this->tenant->database_name);
            DB::purge('tenant');
            DB::reconnect('tenant');

            $commandOptions = [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ];

            if ($this->dryRun) {
                $commandOptions['--pretend'] = true;
            }

            // Merge additional options (like --step for rollback)
            $commandOptions = array_merge($commandOptions, $this->options);

            // Determine command
            $artisanCommand = match ($this->action) {
                'rollback' => 'migrate:rollback',
                'refresh' => 'migrate:refresh',
                'reset' => 'migrate:reset',
                default => 'migrate',
            };

            Log::info("Starting {$this->action} for tenant: {$this->tenant->id} ({$this->tenant->database_name})");

            Artisan::call($artisanCommand, $commandOptions);
            $output = Artisan::output();

            Log::info("{$this->action} output for tenant {$this->tenant->id}: " . $output);

            if ($this->dryRun) {
                 return;
            }

            $this->syncMigrationLogs();

        } catch (\Exception $e) {
            Log::error("{$this->action} failed for tenant {$this->tenant->id}: " . $e->getMessage());

            // Record failure in centralized log
            TenantMigrationLog::create([
                'tenant_id' => $this->tenant->id,
                'migration' => 'batch_execution',
                'batch' => 0,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    protected function syncMigrationLogs()
    {
        // 1. Get the current migrations from the tenant DB (source of truth)
        $tenantMigrations = DB::connection('tenant')->table('migrations')->get();

        // 2. Get what the landlord thinks we have
        $loggedMigrations = TenantMigrationLog::where('tenant_id', $this->tenant->id)->get()->keyBy('migration');

        // 3. Sync: Add/Update existing ones
        foreach ($tenantMigrations as $migration) {
            TenantMigrationLog::updateOrCreate(
                [
                    'tenant_id' => $this->tenant->id,
                    'migration' => $migration->migration,
                ],
                [
                    'batch' => $migration->batch,
                    'status' => 'success',
                    'error_message' => null,
                    'started_at' => now(), // Approximate
                    'completed_at' => now(),
                ]
            );
        }

        // 4. Sync: Mark missing ones as rolled back
        // If it's in our logs but NOT in the tenant DB, it was rolled back.
        $currentMigrationNames = $tenantMigrations->pluck('migration')->toArray();

        TenantMigrationLog::where('tenant_id', $this->tenant->id)
            ->whereNotIn('migration', $currentMigrationNames)
            ->where('status', 'success') // Only mark previously successful ones
            ->update([
                'status' => 'rolled_back',
                'batch' => 0, // Reset batch or keep it? 0 usually implies not applied.
                'completed_at' => now(),
            ]);
    }
}
