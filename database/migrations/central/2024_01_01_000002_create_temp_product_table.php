<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('temp_product', function (Blueprint $table) {
            $table->id('ID');
            $table->integer('TID')->nullable();
            $table->integer('VoucherID')->nullable();
            $table->integer('ProductID')->nullable();

            // Basic Info
            $table->string('Type', 50)->nullable();
            $table->string('Name', 200)->nullable();
            $table->text('Image')->nullable();
            $table->string('Code', 50)->nullable();
            $table->string('CategoryName', 200)->nullable();
            $table->integer('CategoryID')->nullable();
            $table->integer('ParentCategoryID')->nullable();
            $table->integer('ParentLevel1')->default(0);
            $table->integer('ParentLevel2')->default(0);
            $table->integer('ParentLevel3')->default(0);
            $table->string('SaleInformation', 500)->nullable();

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
            $table->string('TaxRegion', 10)->nullable();
            $table->string('TaxCode', 20)->nullable();
            $table->integer('TaxRate')->nullable();
            $table->integer('NFTaxRate')->nullable();
            $table->string('NFTaxCode', 20)->nullable();
            $table->tinyInteger('TaxSchID')->default(4);

            // Stock
            $table->double('LowStockLevel', 20, 6)->nullable();
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
            $table->string('PreferredSupplierAccount', 50)->nullable();

            // Turnover
            $table->double('TOSale', 20, 6)->default(0);
            $table->double('TOPurchase', 20, 6)->default(0);
            $table->double('RMSale', 20, 6)->default(0);
            $table->double('RMPurchase', 20, 6)->default(0);
            $table->double('TPSale', 20, 6)->default(0);
            $table->double('TPPurchase', 20, 6)->default(0);

            // Unit
            $table->integer('Unit')->default(0);
            $table->string('UnitName', 200)->nullable();
            $table->integer('UMUnitID')->default(0);
            $table->string('UMUnitName', 200)->nullable();
            $table->decimal('FactorRate', 20, 10)->default(0);

            // Batch
            $table->string('BatchNo', 50)->default('0');
            $table->date('BatchExpiryDate')->nullable();

            // Location
            $table->integer('ToLocationID')->nullable();
            $table->string('Location', 50)->nullable();

            // Flags
            $table->tinyInteger('IsSelltoCustomer')->nullable();
            $table->tinyInteger('IsShipping')->default(0);
            $table->tinyInteger('IsPurchasing')->default(0);
            $table->tinyInteger('IsRawMaterial')->default(0);

            // Optional Fields
            $table->string('OptionalField1', 100)->nullable();
            $table->string('OptionalField2', 100)->nullable();
            $table->integer('OptionalField3')->default(0);

            // Status & Audit
            $table->integer('Status')->default(1);
            $table->integer('CreatedBy')->nullable();
            $table->timestamp('CreatedOn')->nullable()->useCurrent();
            $table->integer('UpdatedBy')->nullable();
            $table->dateTime('UpdatedOn')->nullable();

            // Import/Error tracking
            $table->string('ErrMsg', 200)->nullable();
            $table->integer('ExcelRow')->nullable();
            $table->tinyInteger('Flag')->default(0);
            $table->string('RequestID', 25)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('temp_product');
    }
};