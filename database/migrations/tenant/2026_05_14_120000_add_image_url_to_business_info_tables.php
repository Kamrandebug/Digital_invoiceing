<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tbl_business_info', 'ImageUrl')) {
            Schema::table('tbl_business_info', function (Blueprint $table) {
                $table->string('ImageUrl', 500)->nullable();
            });
        }

        if (! Schema::hasColumn('temp_business_info', 'ImageUrl')) {
            Schema::table('temp_business_info', function (Blueprint $table) {
                $table->string('ImageUrl', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tbl_business_info', 'ImageUrl')) {
            Schema::table('tbl_business_info', function (Blueprint $table) {
                $table->dropColumn('ImageUrl');
            });
        }

        if (Schema::hasColumn('temp_business_info', 'ImageUrl')) {
            Schema::table('temp_business_info', function (Blueprint $table) {
                $table->dropColumn('ImageUrl');
            });
        }
    }
};
