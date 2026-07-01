<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasTable('purchase_breakup_jobs')) {
            $schema->create('purchase_breakup_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->unsignedInteger('version_no')->default(1);
                $table->unsignedTinyInteger('breakup_status')->default(0);
                $table->json('settings_json')->nullable();
                $table->unsignedInteger('total_rows')->default(0);
                $table->string('file_name', 255)->nullable();
                $table->text('file_path')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['purchase_id', 'version_no']);
            });
        }

        if (! $schema->hasTable('purchase_breakup_invoice_data')) {
            $schema->create('purchase_breakup_invoice_data', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_id')->index();
                $table->unsignedBigInteger('job_id')->index();
                $table->string('invoice_no', 100)->nullable();
                $table->date('invoice_date')->nullable();
                $table->string('supplier_name', 255)->nullable();
                $table->string('product_name', 255)->nullable();
                $table->decimal('qty', 18, 4)->default(0);
                $table->decimal('rate', 18, 4)->default(0);
                $table->decimal('amount', 18, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! $schema->hasTable('purchase_breakup_excel_columns')) {
            $schema->create('purchase_breakup_excel_columns', function (Blueprint $table) {
                $table->id();
                $table->string('column_label', 255);
                $table->string('db_column_name', 255);
                $table->unsignedInteger('sequence_no')->default(1);
                $table->unsignedTinyInteger('is_visible')->default(1);
                $table->unsignedInteger('excel_width')->default(20);
                $table->enum('alignment', ['left', 'center', 'right'])->default('left');
            });
        }

        if (DB::connection('tenant')->table('purchase_breakup_excel_columns')->count() === 0) {
            $columns = [
                ['column_label' => 'Invoice No', 'db_column_name' => 'invoice_no', 'sequence_no' => 1, 'is_visible' => 1, 'excel_width' => 18, 'alignment' => 'left'],
                ['column_label' => 'Date', 'db_column_name' => 'invoice_date', 'sequence_no' => 2, 'is_visible' => 1, 'excel_width' => 14, 'alignment' => 'center'],
                ['column_label' => 'Customer', 'db_column_name' => 'supplier_name', 'sequence_no' => 3, 'is_visible' => 1, 'excel_width' => 25, 'alignment' => 'left'],
                ['column_label' => 'Product', 'db_column_name' => 'product_name', 'sequence_no' => 4, 'is_visible' => 1, 'excel_width' => 25, 'alignment' => 'left'],
                ['column_label' => 'Qty', 'db_column_name' => 'qty', 'sequence_no' => 5, 'is_visible' => 1, 'excel_width' => 12, 'alignment' => 'right'],
                ['column_label' => 'Rate', 'db_column_name' => 'rate', 'sequence_no' => 6, 'is_visible' => 1, 'excel_width' => 14, 'alignment' => 'right'],
                ['column_label' => 'Amount', 'db_column_name' => 'amount', 'sequence_no' => 7, 'is_visible' => 1, 'excel_width' => 16, 'alignment' => 'right'],
                ['column_label' => 'Remarks', 'db_column_name' => 'remarks', 'sequence_no' => 8, 'is_visible' => 1, 'excel_width' => 20, 'alignment' => 'left'],
            ];

            foreach ($columns as $col) {
                DB::connection('tenant')->table('purchase_breakup_excel_columns')->insert($col);
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');
        $schema->dropIfExists('purchase_breakup_invoice_data');
        $schema->dropIfExists('purchase_breakup_jobs');
        $schema->dropIfExists('purchase_breakup_excel_columns');
    }
};
