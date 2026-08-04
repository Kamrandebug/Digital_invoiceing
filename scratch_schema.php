<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Blade;

$files = [
    'resources/views/pages/dashboards/Product/_product-form-partial.blade.php',
    'resources/views/pages/dashboards/Customer/_customer-form-partial.blade.php',
    'resources/views/pages/dashboards/Supplier/_supplier-form-partial.blade.php',
    'resources/views/pages/dashboards/Users/_user-form-partial.blade.php',
    'resources/views/pages/dashboards/Roles/_role-form-partial.blade.php',
    'resources/views/layouts/master.blade.php',
    'resources/views/pages/dashboards/Users/users-list.blade.php',
    'resources/views/pages/dashboards/Roles/roles-list.blade.php',
    'resources/views/pages/dashboards/Product/products.blade.php',
    'resources/views/pages/dashboards/Customer/customers-list.blade.php',
    'resources/views/pages/dashboards/Supplier/suppliers-list.blade.php',
];

foreach ($files as $file) {
    try {
        echo "Compiling {$file}... ";
        $content = file_get_contents($file);
        $compiled = Blade::compileString($content);
        
        // Use PHP lint on the compiled content
        $tmpFile = tempnam(sys_get_temp_dir(), 'blade_');
        file_put_contents($tmpFile, $compiled);
        
        $output = [];
        $returnVar = 0;
        exec("php -l " . escapeshellarg($tmpFile), $output, $returnVar);
        
        unlink($tmpFile);
        
        if ($returnVar === 0) {
            echo "SUCCESS!\n";
        } else {
            echo "FAILED!\n";
            echo implode("\n", $output) . "\n";
        }
    } catch (\Exception $e) {
        echo "FAILED!\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
}
