<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_customer') && ! Schema::hasColumn('tbl_customer', 'PartyType')) {
            Schema::table('tbl_customer', function (Blueprint $table) {
                $table->unsignedTinyInteger('PartyType')->nullable()->after('AccountNo');
            });
        }

        if (Schema::hasTable('temp_customer') && ! Schema::hasColumn('temp_customer', 'PartyType')) {
            Schema::table('temp_customer', function (Blueprint $table) {
                $table->unsignedTinyInteger('PartyType')->nullable()->after('AccountNo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_customer') && Schema::hasColumn('tbl_customer', 'PartyType')) {
            Schema::table('tbl_customer', function (Blueprint $table) {
                $table->dropColumn('PartyType');
            });
        }

        if (Schema::hasTable('temp_customer') && Schema::hasColumn('temp_customer', 'PartyType')) {
            Schema::table('temp_customer', function (Blueprint $table) {
                $table->dropColumn('PartyType');
            });
        }
    }
};
