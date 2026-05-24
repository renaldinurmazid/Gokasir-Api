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
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(12.00)->after('expired_at');
            $table->integer('token_balance')->default(500)->after('tax_rate');
            $table->integer('token_lifetime_used')->default(0)->after('token_balance');
            $table->integer('token_lifetime_topup')->default(0)->after('token_lifetime_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'token_balance', 'token_lifetime_used', 'token_lifetime_topup']);
        });
    }
};
