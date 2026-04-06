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

        if (! $schema->hasTable('tbl_lead')) {
            return;
        }

        if ($schema->hasColumn('tbl_lead', 'RequestID')) {
            return;
        }

        $schema->table('tbl_lead', function (Blueprint $table) {
            $table->string('RequestID', 36)->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('landlord');

        if (! $schema->hasTable('tbl_lead') || ! $schema->hasColumn('tbl_lead', 'RequestID')) {
            return;
        }

        $schema->table('tbl_lead', function (Blueprint $table) {
            $table->dropColumn('RequestID');
        });
    }
};
