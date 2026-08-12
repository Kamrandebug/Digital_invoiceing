<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── tbl_main_modules: category-level icons ──
        $mainModuleIcons = [
            1 => 'ri-shopping-bag-line',     // Sales
            2 => 'ri-shopping-cart-line',     // Purchases
            3 => 'ri-box-3-line',             // Products
            4 => 'ri-bar-chart-2-line',       // Reports
            5 => 'ri-settings-3-line',        // Management
        ];

        foreach ($mainModuleIcons as $id => $icon) {
            DB::connection('tenant')->table('tbl_main_modules')
                ->where('ID', $id)
                ->update(['MainModuleIcon' => $icon]);
        }

        // ── tbl_module: individual menu-item icons ──
        $moduleIcons = [
            // ID => icon class
            1 => 'ri-box-3-line',             // Products
            2 => 'ri-group-line',             // Customers
            3 => 'ri-truck-line',             // Suppliers
            4 => 'ri-receipt-line',           // Invoices
            5 => 'ri-shopping-cart-line',     // Purchases
            6 => 'ri-user-settings-line',     // Users
            7 => 'ri-shield-keyhole-line',    // Roles
        ];

        foreach ($moduleIcons as $id => $icon) {
            DB::connection('tenant')->table('tbl_module')
                ->where('ID', $id)
                ->update(['MainModuleIcon' => $icon]);
        }
    }

    public function down(): void
    {
        DB::connection('tenant')->table('tbl_main_modules')->update(['MainModuleIcon' => null]);
        DB::connection('tenant')->table('tbl_module')->update(['MainModuleIcon' => null]);
    }
};
