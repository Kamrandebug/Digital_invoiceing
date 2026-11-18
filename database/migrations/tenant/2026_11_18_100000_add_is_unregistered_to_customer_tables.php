<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('tbl_customer') && ! Schema::hasColumn('tbl_customer', 'IsUnregistered')) {
            Schema::table('tbl_customer', function (Blueprint $table) {
                $table->tinyInteger('IsUnregistered')->default(0)->after('IsFiler');
            });
        }

        if (Schema::hasTable('temp_customer') && ! Schema::hasColumn('temp_customer', 'IsUnregistered')) {
            Schema::table('temp_customer', function (Blueprint $table) {
                $table->tinyInteger('IsUnregistered')->default(0)->after('IsFiler');
            });
        }

        if (Schema::hasTable('tbl_customer_history') && ! Schema::hasColumn('tbl_customer_history', 'IsUnregistered')) {
            Schema::table('tbl_customer_history', function (Blueprint $table) {
                $table->tinyInteger('IsUnregistered')->default(0)->after('IsFiler');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_customer') && Schema::hasColumn('tbl_customer', 'IsUnregistered')) {
            Schema::table('tbl_customer', function (Blueprint $table) {
                $table->dropColumn('IsUnregistered');
            });
        }

        if (Schema::hasTable('temp_customer') && Schema::hasColumn('temp_customer', 'IsUnregistered')) {
            Schema::table('temp_customer', function (Blueprint $table) {
                $table->dropColumn('IsUnregistered');
            });
        }

        if (Schema::hasTable('tbl_customer_history') && Schema::hasColumn('tbl_customer_history', 'IsUnregistered')) {
            Schema::table('tbl_customer_history', function (Blueprint $table) {
                $table->dropColumn('IsUnregistered');
            });
        }
    }
};
