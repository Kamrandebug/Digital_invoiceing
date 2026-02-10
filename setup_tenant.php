<?php

use Illuminate\Contracts\Console\Kernel;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

echo "--- DIAGNOSTIC START ---\n";

// 1. Check/Setup Central Database
$centralDbName = config('database.connections.central.database');
echo "Central Database Name: $centralDbName\n";

try {
    // Create central DB if not exists (using landlord connection to execute CREATE DATABASE)
    DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `$centralDbName`");
    
    // Check if crm_customer exists in central
    $tables = DB::connection('central')->select("SHOW TABLES LIKE 'crm_customer'");
    if (count($tables) == 0) {
        echo "crm_customer missing in central. Running migrations...\n";
        Artisan::call('migrate', [
            '--database' => 'central',
            '--path' => 'database/migrations/central',
            '--force' => true,
        ]);
        echo Artisan::output();
    } else {
        echo "crm_customer exists in central.\n";
    }
} catch (\Exception $e) {
    echo "Error with Central Database: " . $e->getMessage() . "\n";
}

// 2. Setup Tenant for User 5
$user = User::find(5);
if (!$user) {
    echo "User 5 not found.\n";
    exit(1);
}

if ($user->tenant) {
    echo "User 5 has tenant: " . $user->tenant->database_name . "\n";
    
    // Check if tenant DB exists and has table
    $tenantDbName = $user->tenant->database_name;
    try {
        DB::connection('landlord')->statement("CREATE DATABASE IF NOT EXISTS `$tenantDbName`"); // Ensure DB exists
        
        config(['database.connections.tenant.database' => $tenantDbName]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        $tables = DB::connection('tenant')->select("SHOW TABLES LIKE 'crm_customer'");
        if (count($tables) == 0) {
            echo "crm_customer missing in tenant DB. Copying from central...\n";
            // Manually trigger copy since tenant record exists but DB might be partial
            $service = new TenantProvisioningService();
            // Reflection to access protected method or just re-run creation logic?
            // TenantProvisioningService::migrateTenant is public and calls copySchemaFromCentral
            $service->migrateTenant($user->tenant);
            echo "Schema copied.\n";
        } else {
            echo "crm_customer exists in tenant DB.\n";
        }
    } catch (\Exception $e) {
        echo "Error fixing tenant DB: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Creating tenant for User 5...\n";
    $service = new TenantProvisioningService();
    try {
        $tenant = $service->createTenant($user);
        echo "Tenant created: " . $tenant->database_name . "\n";
    } catch (\Exception $e) {
        echo "Failed to create tenant: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "--- DIAGNOSTIC END ---\n";
