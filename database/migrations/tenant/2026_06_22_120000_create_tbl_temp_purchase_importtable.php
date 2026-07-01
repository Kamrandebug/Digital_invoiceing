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

        if (! $schema->hasTable('tbl_temp_purchase_importtable')) {
            $schema->create('tbl_temp_purchase_importtable', function (Blueprint $table) {
                $table->bigIncrements('ID');
                $table->string('InvoiceNo', 100)->nullable();
                $table->string('InvoiceType', 100)->nullable();
                $table->string('InvoiceDate', 50)->nullable();
                $table->string('BuyerRegistrationNo', 50)->nullable();
                $table->string('BuyerName', 255)->nullable();
                $table->string('TaxpayerType', 50)->nullable();
                $table->string('SellerRegistrationNo', 50)->nullable();
                $table->string('SellerName', 255)->nullable();
                $table->string('SaleType', 100)->nullable();
                $table->string('SaleOriginationProvince', 100)->nullable();
                $table->decimal('Quantity', 18, 4)->default(0);
                $table->string('ProductDescription', 500)->nullable();
                $table->string('HsCode', 50)->nullable();
                $table->string('Rate', 50)->nullable();
                $table->string('Uom', 50)->nullable();
                $table->decimal('ValueSalesExcludingST', 18, 2)->default(0);
                $table->decimal('SalesTaxFedInStMode', 18, 2)->default(0);
                $table->decimal('FixedNotifiedValueOrRetailPrice', 18, 2)->default(0);
                $table->unsignedInteger('RowNo')->nullable();
                $table->uuid('RequestID')->nullable()->index();
                $table->string('ErrMsg', 500)->nullable();
                $table->unsignedBigInteger('CreatedBy')->nullable();
                $table->dateTime('CreatedOn')->nullable();
                $table->unsignedBigInteger('UpdatedBy')->nullable();
                $table->dateTime('UpdatedOn')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('tbl_temp_purchase_importtable');
    }
};
