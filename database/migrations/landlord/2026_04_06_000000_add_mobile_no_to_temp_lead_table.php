<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        $schema = Schema::connection('landlord');

        if (! $schema->hasTable('temp_lead')) {
            return;
        }

        if ($schema->hasColumn('temp_lead', 'MobileNo')) {
            return;
        }

        $schema->table('temp_lead', function (Blueprint $table) {
            $table->string('MobileNo', 15)->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('landlord');

        if (! $schema->hasTable('temp_lead') || ! $schema->hasColumn('temp_lead', 'MobileNo')) {
            return;
        }

        $schema->table('temp_lead', function (Blueprint $table) {
            $table->dropColumn('MobileNo');
        });
    }
};
