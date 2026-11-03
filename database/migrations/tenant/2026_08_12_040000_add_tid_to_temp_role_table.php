<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('temp_role', function (Blueprint $table) {
            $table->integer('TID')->nullable()->after('ID');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('temp_role', function (Blueprint $table) {
            $table->dropColumn('TID');
        });
    }
};
