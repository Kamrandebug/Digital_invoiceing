<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantMigrationLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    public function createTenant(User $user): Tenant
    {
        // Note: We cannot use a simple DB::transaction here effectively because 'CREATE DATABASE' causes an implicit commit.
        // We must manually handle the rollback (cleanup) if steps fail.

        // 1. Generate Database Name
        $databaseName = 'tenant_' . Str::uuid()->toString();
        $databaseName = str_replace('-', '_', $databaseName);

        // 2. Create Tenant Record
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'database_name' => $databaseName,
        ]);

        try {
            // 3. Create Database
            DB::statement("CREATE DATABASE `{$databaseName}`");

            // 4. Migrate Database
            $this->migrateTenant($tenant);

            return $tenant;
        } catch (\Exception $e) {
            // Manual Rollback/Cleanup

            // Drop DB if it was created
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
            } catch (\Exception $dropError) {
                // Log this, critical failure
            }

            // Delete Tenant Record
            $tenant->delete();

            throw $e;
        }
    }

    public function migrateTenant(Tenant $tenant)
    {
        // Configure the tenant connection dynamically
        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Run migrations
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        // Sync logs to centralized table
        $this->syncMigrationLogs($tenant);
    }

    protected function syncMigrationLogs(Tenant $tenant)
    {
        // Get the migrations from the tenant DB
        $tenantMigrations = DB::connection('tenant')->table('migrations')->get();

        foreach ($tenantMigrations as $migration) {
            TenantMigrationLog::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
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
    }
}
