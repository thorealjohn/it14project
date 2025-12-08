<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('delivery_notified_at')->nullable()->after('delivery_date');
            $table->timestamp('late_alert_sent_at')->nullable()->after('delivery_notified_at');
            $table->timestamp('follow_up_sent_at')->nullable()->after('late_alert_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_notified_at', 'late_alert_sent_at', 'follow_up_sent_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};

