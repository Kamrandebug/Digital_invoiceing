<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('crm_product', function (Blueprint $table) {
            $table->id('ID');
            $table->integer('VoucherID')->nullable();

            // Basic Info
            $table->string('Type', 50)->nullable();
            $table->string('Name', 200);
            $table->text('Image');
            $table->string('Code', 50);
            $table->integer('Category');
            $table->string('SaleInformation', 500)->charset('utf8')->collation('utf8_unicode_ci');

            // Pricing
            $table->double('SalePrice', 20, 6)->default(0);
            $table->double('Cost', 20, 6)->default(0);
            $table->double('Discount', 11, 4)->default(0);
            $table->double('PurchaseDiscount', 11, 4)->default(0);
            $table->double('TPRate', 20, 6)->default(0);
            $table->double('TPMargin', 20, 6)->default(0);
            $table->double('TrialRate', 20, 6)->default(0);
            $table->double('Weight', 20, 6)->default(0);

            // Tax
            $table->string('TaxCode', 20)->nullable();
            $table->integer('TaxRate');
            $table->integer('NFTaxRate')->nullable();
            $table->tinyInteger('TaxSchID')->default(4);

            // Stock
            $table->double('LowStockLevel', 20, 6);
            $table->double('CurrentQuantity', 20, 6)->default(0);
            $table->double('OpeningQuantity', 20, 6)->default(0);
            $table->double('OpeningRate', 20, 6)->default(0);
            $table->date('Date')->nullable();
            $table->tinyInteger('StockStatus')->default(1)->comment('0=Low Stock, 1=Stock In, 2=Out of Stock');
            $table->tinyInteger('purchaseStatus')->default(0);

            // Accounts
            $table->integer('IncomeAccount')->default(0);
            $table->integer('StockAccount')->default(0);
            $table->integer('ExpenseAccount')->default(0);
            $table->integer('PreferredSupplier')->default(0);

            // Turnover
            $table->double('TOSale', 20, 6)->default(0);
            $table->double('TOPurchase', 20, 6)->default(0);
            $table->double('RMSale', 20, 6)->default(0);
            $table->double('RMPurchase', 20, 6)->default(0);
            $table->double('TPSale', 20, 6)->default(0);
            $table->double('TPPurchase', 20, 6)->default(0);

            // Unit
            $table->integer('Unit')->default(0);

            // Flags
            $table->tinyInteger('IsSelltoCustomer')->nullable();
            $table->tinyInteger('IsShipping')->default(0);
            $table->tinyInteger('IsPurchasing')->default(0);
            $table->tinyInteger('IsRawMaterial')->default(0);

            // Optional Fields
            $table->string('OptionalField1', 100)->nullable();
            $table->string('OptionalField2', 100)->nullable();
            $table->integer('OptionalField3')->default(0);

            // Status
            $table->integer('Status')->default(1);

            // Audit
            $table->integer('CreatedBy');
            $table->timestamp('CreatedOn')->useCurrent();
            $table->integer('UpdatedBy')->nullable();
            $table->dateTime('UpdatedOn')->nullable();
            $table->string('RequestID', 50)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('crm_product');
    }
};