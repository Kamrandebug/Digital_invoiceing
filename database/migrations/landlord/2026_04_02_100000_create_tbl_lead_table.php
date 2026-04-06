<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        $schema = Schema::connection('landlord');
        if ($schema->hasTable('tbl_lead')) {
            return;
        }

        $schema->create('tbl_lead', function (Blueprint $table) {
            $table->increments('ID');
            $table->string('BusinessName', 100)->nullable();
            $table->string('FirstName', 100)->nullable();
            $table->string('LastName', 100)->nullable();
            $table->string('Email', 100)->nullable();
            $table->string('City', 100)->nullable();
            $table->tinyInteger('Statusi')->nullable();
            $table->integer('CreatedBy')->default(1);
            $table->timestamp('CreatedOn')->useCurrent();
            $table->integer('UpdatedBy')->nullable();
            $table->dateTime('UpdatedOn')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tbl_lead');
    }
};
