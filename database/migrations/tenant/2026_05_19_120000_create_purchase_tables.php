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

        if (! $schema->hasTable('tbl_purchase_header')) {
            $schema->create('tbl_purchase_header', function (Blueprint $table) {
                $table->unsignedBigInteger('ID')->primary();
                $table->unsignedBigInteger('PurchaseID')->nullable()->index();
                $table->string('DocNo', 100)->nullable();
                $table->date('PurchaseDate')->nullable();
                $table->unsignedBigInteger('SupplierID')->nullable()->index();
                $table->text('Notes')->nullable();
                $table->decimal('SubTotal', 18, 2)->default(0);
                $table->decimal('TotalAmount', 18, 2)->default(0);
                $table->unsignedTinyInteger('Status')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->dateTime('CreatedOn')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->dateTime('UpdatedOn')->nullable();
            });
        }

        if (! $schema->hasTable('tbl_purchase_detail')) {
            $schema->create('tbl_purchase_detail', function (Blueprint $table) {
                $table->bigIncrements('ID');
                $table->unsignedBigInteger('PurchaseID')->index();
                $table->unsignedBigInteger('ProductID')->nullable()->index();
                $table->decimal('Quantity', 18, 4)->default(0);
                $table->decimal('Rate', 18, 4)->default(0);
                $table->decimal('Amount', 18, 2)->default(0);
                $table->unsignedBigInteger('UomID')->nullable();
                $table->string('ProductDescription', 500)->nullable();
                $table->text('Notes')->nullable();
                $table->unsignedTinyInteger('Status')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->dateTime('CreatedOn')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->dateTime('UpdatedOn')->nullable();
            });
        }

        if (! $schema->hasTable('tbl_temp_purchase_detail')) {
            $schema->create('tbl_temp_purchase_detail', function (Blueprint $table) {
                $table->bigIncrements('ID');
                $table->unsignedBigInteger('TID')->nullable()->index();
                $table->unsignedBigInteger('PurchaseID')->nullable();
                $table->unsignedBigInteger('SupplierID')->nullable();
                $table->date('PurchaseDate')->nullable();
                $table->string('DocNo', 100)->nullable();
                $table->unsignedBigInteger('ProductID')->nullable();
                $table->decimal('Quantity', 18, 4)->default(0);
                $table->decimal('Rate', 18, 4)->default(0);
                $table->decimal('Amount', 18, 2)->default(0);
                $table->unsignedBigInteger('UomID')->nullable();
                $table->string('ProductDescription', 500)->nullable();
                $table->text('Notes')->nullable();
                $table->unsignedTinyInteger('Status')->default(1);
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->dateTime('CreatedOn')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->dateTime('UpdatedOn')->nullable();
                $table->uuid('RequestID')->nullable()->index();
                $table->string('ErrMsg', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');
        $schema->dropIfExists('tbl_temp_purchase_detail');
        $schema->dropIfExists('tbl_purchase_detail');
        $schema->dropIfExists('tbl_purchase_header');
    }
};
