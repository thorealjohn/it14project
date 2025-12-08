<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('house_number')->nullable()->after('phone');
            $table->string('street')->nullable()->after('house_number');
            $table->string('barangay')->nullable()->after('street');
            $table->string('city')->nullable()->after('barangay');
            $table->string('province')->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('province');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['house_number', 'street', 'barangay', 'city', 'province', 'postal_code']);
        });
    }
};

