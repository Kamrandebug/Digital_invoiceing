<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantMigrationLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TenantProvisioningService
{
    public function createTenant(User $user): Tenant
    {
        // Generate Database Name
        $databaseName = 'tenant_' . Str::uuid()->toString();
        $databaseName = str_replace('-', '_', $databaseName);

        // Create Tenant Record
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'database_name' => $databaseName,
        ]);

        try {
            // Create Database
            DB::statement("CREATE DATABASE `{$databaseName}`");

            // Copy schema from CENTRAL database (not running migrations)
            $this->copySchemaFromCentral($tenant);

            // Log the schema copy operation
            $this->logSchemaCopy($tenant);

            return $tenant;
        } catch (\Exception $e) {
            // Manual Rollback/Cleanup
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
            } catch (\Exception $dropError) {
                // Log this critical failure
                Log::error('Failed to drop database during rollback: ' . $dropError->getMessage());
            }

            // Delete Tenant Record
            $tenant->delete();

            throw $e;
        }
    }

    /**
     * Copy schema from central database to tenant database
     */
    protected function copySchemaFromCentral(Tenant $tenant)
    {
        $centralDb = config('database.connections.central.database');
        $tenantDb = $tenant->database_name;

        // Get all tables from central database
        $tables = DB::connection('central')
            ->select('SHOW TABLES');

        $tableKey = "Tables_in_{$centralDb}";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            // Skip system/framework tables that shouldn't be copied
            if ($this->shouldSkipTable($tableName)) {
                continue;
            }

            // Get CREATE TABLE statement from central
            $createTableResult = DB::connection('central')
                ->select("SHOW CREATE TABLE `{$tableName}`");

            $createStatement = $createTableResult[0]->{'Create Table'};

            // Switch to tenant database and create table
            DB::statement("USE `{$tenantDb}`");
            DB::statement($createStatement);
        }

        // Reset to default database
        DB::statement("USE `" . config('database.connections.landlord.database') . "`");
    }

    /**
     * Determine if a table should be skipped during schema copy
     */
    protected function shouldSkipTable(string $tableName): bool
    {
        $skipTables = [
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'failed_jobs',
            'jobs',
            'job_batches',
            'users',
            'tenants',
            'tenant_migration_logs',
        ];

        return in_array($tableName, $skipTables);
    }

    /**
     * Log the schema copy operation
     */
    protected function logSchemaCopy(Tenant $tenant)
    {
        // Get the tables that were copied
        $centralDb = config('database.connections.central.database');
        $tables = DB::connection('central')->select('SHOW TABLES');
        $tableKey = "Tables_in_{$centralDb}";

        $copiedTables = [];
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            if (!$this->shouldSkipTable($tableName)) {
                $copiedTables[] = $tableName;
            }
        }

        // Log to tenant_migration_logs
        TenantMigrationLog::create([
            'tenant_id' => $tenant->id,
            'migration' => 'schema_copy_from_central',
            'batch' => 1,
            'status' => 'success',
            'error_message' => 'Copied tables: ' . implode(', ', $copiedTables),
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    /**
     * Switch to tenant database connection
     */
    public function switchToTenant(Tenant $tenant)
    {
        config(['database.connections.tenant.database' => $tenant->database_name]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Legacy method - kept for backward compatibility
     * Now it just calls copySchemaFromCentral instead of running migrations
     */
    public function migrateTenant(Tenant $tenant)
    {
        $this->copySchemaFromCentral($tenant);
        $this->logSchemaCopy($tenant);
    }
}