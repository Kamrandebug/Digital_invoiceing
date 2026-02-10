<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('customer_id'); // Changed from foreignId
            $table->decimal('amount', 10, 2);
            $table->date('invoice_date');
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamps();
            
            // No foreign key constraint for now
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('invoices');
    }
};