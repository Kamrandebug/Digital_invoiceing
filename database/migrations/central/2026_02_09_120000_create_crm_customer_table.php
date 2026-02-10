<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('central')->create('crm_customer', function (Blueprint $table) {
            $table->id('ID');
            $table->string('AccountNo')->nullable()->unique();
            $table->string('Title', 50)->nullable();
            $table->string('FirstName', 100)->nullable();
            $table->string('LastName', 100)->nullable();
            $table->string('BusinessName', 200)->nullable();
            $table->string('DisplayName', 200)->nullable();
            
            // Contact
            $table->string('Email', 500)->nullable();
            $table->string('Mobile', 100)->nullable();
            $table->string('Phone', 100)->nullable();
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
            $table->boolean('SameAsBilling')->default(0);
            
            // Tax
            $table->string('NTN', 20)->nullable();
            $table->string('SalesTaxNumber', 50)->nullable();
            $table->boolean('IsFiler')->default(1);
            $table->string('CNIC', 50)->nullable();
            
            // Financial
            $table->decimal('CreditLimit', 15, 2)->default(0);
            $table->string('PaymentTerms', 20)->nullable();
            
            // Additional
            $table->string('Notes', 500)->nullable();
            $table->integer('Status')->default(1); // 0 or 1
            $table->decimal('PartyBalance', 15, 2)->default(0);
            $table->integer('Type')->default(1);
            $table->integer('Typei')->default(1);
            $table->boolean('IsDashboard')->default(0);
            
            // Audit
            $table->unsignedBigInteger('CreatedBy')->nullable();
            $table->timestamp('CreatedOn')->useCurrent();
            $table->unsignedBigInteger('UpdatedBy')->nullable();
            $table->timestamp('UpdatedOn')->nullable();
            $table->integer('IsArchive')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('crm_customer');
    }
};
