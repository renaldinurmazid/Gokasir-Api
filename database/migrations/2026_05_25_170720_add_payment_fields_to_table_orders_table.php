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
        Schema::table('table_orders', function (Blueprint $table) {
            $table->text('payment_no')->nullable()->after('payment_channel');
            $table->string('payment_name', 150)->nullable()->after('payment_no');
            $table->decimal('payment_fee', 15, 2)->nullable()->after('payment_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_no', 'payment_name', 'payment_fee']);
        });
    }
};
