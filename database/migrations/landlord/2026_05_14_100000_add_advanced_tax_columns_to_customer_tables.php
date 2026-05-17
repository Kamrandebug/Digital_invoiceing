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

        foreach (['tbl_customer', 'temp_customer'] as $table) {
            if (! $schema->hasTable($table)) {
                continue;
            }

            if (! $schema->hasColumn($table, 'IsAdvanceTax')) {
                $schema->table($table, function (Blueprint $t) {
                    $t->boolean('IsAdvanceTax')->default(false);
                });
            }

            if (! $schema->hasColumn($table, 'AdvanceTax')) {
                $schema->table($table, function (Blueprint $t) {
                    $t->decimal('AdvanceTax', 10, 2)->nullable();
                });
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('landlord');

        foreach (['tbl_customer', 'temp_customer'] as $table) {
            if (! $schema->hasTable($table)) {
                continue;
            }

            $cols = [];
            if ($schema->hasColumn($table, 'AdvanceTax')) {
                $cols[] = 'AdvanceTax';
            }
            if ($schema->hasColumn($table, 'IsAdvanceTax')) {
                $cols[] = 'IsAdvanceTax';
            }
            if ($cols !== []) {
                $schema->table($table, function (Blueprint $t) use ($cols) {
                    $t->dropColumn($cols);
                });
            }
        }
    }
};
