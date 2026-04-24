<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if ($schema->hasTable('tbl_temp_invoice_detail') && ! $schema->hasColumn('tbl_temp_invoice_detail', 'ErrMsg')) {
            $schema->table('tbl_temp_invoice_detail', function (Blueprint $table) {
                $table->string('ErrMsg', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');

        if ($schema->hasTable('tbl_temp_invoice_detail') && $schema->hasColumn('tbl_temp_invoice_detail', 'ErrMsg')) {
            $schema->table('tbl_temp_invoice_detail', function (Blueprint $table) {
                $table->dropColumn('ErrMsg');
            });
        }
    }
};
