<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('replace_gallon')->default(false)->after('notes');
            $table->boolean('replace_caps')->default(false)->after('replace_gallon');
            $table->decimal('replacement_cost', 10, 2)->default(0)->after('replace_caps');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['replace_gallon', 'replace_caps', 'replacement_cost']);
        });
    }
};
