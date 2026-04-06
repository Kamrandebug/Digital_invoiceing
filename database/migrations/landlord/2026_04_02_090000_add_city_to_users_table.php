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
        if ($schema->hasColumn('users', 'city')) {
            return;
        }
        $schema->table('users', function (Blueprint $table) {
            $table->string('city')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('landlord');
        if (! $schema->hasColumn('users', 'city')) {
            return;
        }
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn('city');
        });
    }
};
