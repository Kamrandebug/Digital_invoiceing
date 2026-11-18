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
        if (Schema::hasTable('tbl_product')) {
            Schema::table('tbl_product', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_product', 'ExtraTax')) {
                    $table->decimal('ExtraTax', 18, 2)->default(0.00)->after('TPRate');
                }
                if (! Schema::hasColumn('tbl_product', 'FedPayable')) {
                    $table->decimal('FedPayable', 18, 2)->default(0.00)->after('ExtraTax');
                }
            });
        }

        if (Schema::hasTable('temp_product')) {
            Schema::table('temp_product', function (Blueprint $table) {
                if (! Schema::hasColumn('temp_product', 'ExtraTax')) {
                    $table->decimal('ExtraTax', 18, 2)->default(0.00)->after('TPRate');
                }
                if (! Schema::hasColumn('temp_product', 'FedPayable')) {
                    $table->decimal('FedPayable', 18, 2)->default(0.00)->after('ExtraTax');
                }
            });
        }

        if (Schema::hasTable('tbl_product_history')) {
            Schema::table('tbl_product_history', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_product_history', 'ExtraTax')) {
                    $table->decimal('ExtraTax', 18, 2)->default(0.00)->after('TPRate');
                }
                if (! Schema::hasColumn('tbl_product_history', 'FedPayable')) {
                    $table->decimal('FedPayable', 18, 2)->default(0.00)->after('ExtraTax');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tbl_product')) {
            Schema::table('tbl_product', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_product', 'FedPayable')) {
                    $table->dropColumn('FedPayable');
                }
                if (Schema::hasColumn('tbl_product', 'ExtraTax')) {
                    $table->dropColumn('ExtraTax');
                }
            });
        }

        if (Schema::hasTable('temp_product')) {
            Schema::table('temp_product', function (Blueprint $table) {
                if (Schema::hasColumn('temp_product', 'FedPayable')) {
                    $table->dropColumn('FedPayable');
                }
                if (Schema::hasColumn('temp_product', 'ExtraTax')) {
                    $table->dropColumn('ExtraTax');
                }
            });
        }

        if (Schema::hasTable('tbl_product_history')) {
            Schema::table('tbl_product_history', function (Blueprint $table) {
                if (Schema::hasColumn('tbl_product_history', 'FedPayable')) {
                    $table->dropColumn('FedPayable');
                }
                if (Schema::hasColumn('tbl_product_history', 'ExtraTax')) {
                    $table->dropColumn('ExtraTax');
                }
            });
        }
    }
};
