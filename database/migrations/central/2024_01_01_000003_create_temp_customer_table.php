<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('temp_customer', function (Blueprint $table) {

            $table->id('ID'); // AUTO_INCREMENT PRIMARY KEY

            $table->tinyInteger('IsArchive')->default(0);
            $table->integer('VoucherID')->nullable();

            // Basic Info
            $table->string('AccountNo', 50)->nullable();
            $table->string('Title', 50)->nullable();
            $table->string('FirstName', 100)->nullable();
            $table->string('LastName', 100)->nullable();
            $table->string('Email', 500)->nullable();
            $table->string('BusinessName', 200)->nullable();
            $table->decimal('PartyBalance', 15, 2)->default(0);
            $table->string('Mobile', 100)->nullable();
            $table->string('Phone', 100)->nullable();
            $table->string('DisplayName', 200)->nullable();
            $table->string('Website', 200)->nullable();

            // Shipping Address
            $table->string('Address', 500)->nullable();
            $table->string('City', 100)->nullable();
            $table->string('Country', 100)->nullable();
            $table->string('County', 100)->nullable();
            $table->string('PostalCode', 20)->nullable();

            // Billing Address
            $table->string('BillingAddress', 500)->nullable();
            $table->string('BillingCity', 100)->nullable();
            $table->string('BillingCountry', 100)->nullable();
            $table->string('BillingCounty', 100)->nullable();
            $table->string('BillingPostalCode', 20)->nullable();
            $table->string('FullBillingAddress', 1000)->nullable();

            // Tax & Finance
            $table->string('NTN', 20)->nullable();
            $table->string('SalesTtaxNumber', 50)->nullable(); // double 't' matches DB column
            $table->tinyInteger('IsFiler')->default(1);
            $table->string('CNIC', 50)->nullable();
            $table->string('Notes', 500)->nullable();
            $table->string('PaymentTerms', 20)->nullable();
            $table->integer('Status')->default(1);
            $table->decimal('CreditLimit', 15, 2)->default(0);
            $table->tinyInteger('SameAsBilling')->nullable();

            // Optional Fields
            $table->string('FieldA', 100)->nullable();
            $table->string('FieldB', 100)->nullable();
            $table->string('FieldC', 100)->nullable();
            $table->string('FieldD', 100)->nullable();
            $table->integer('Field1')->default(0);
            $table->integer('Field2')->default(0);
            $table->integer('Field3')->default(0);
            $table->integer('Field4')->default(0);

            // Flags
            $table->tinyInteger('Typei')->default(1);
            $table->tinyInteger('Type')->default(1);
            $table->tinyInteger('IsDashboard')->default(0);

            // Audit
            $table->integer('CreatedBy');
            $table->timestamp('CreatedOn')->useCurrent();
            $table->integer('UpdatedBy')->nullable();
            $table->dateTime('UpdatedOn')->nullable();
            $table->string('RequestID', 25)->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('temp_customer');
    }
};