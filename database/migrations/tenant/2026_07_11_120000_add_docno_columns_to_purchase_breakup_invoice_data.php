<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('purchase_breakup_invoice_data', function (Blueprint $table) {
            $table->string('DocNo2', 50)->nullable()->after('invoice_no');
            $table->string('DocNo3', 50)->nullable()->after('DocNo2');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('purchase_breakup_invoice_data', function (Blueprint $table) {
            $table->dropColumn(['DocNo2', 'DocNo3']);
        });
    }
};
