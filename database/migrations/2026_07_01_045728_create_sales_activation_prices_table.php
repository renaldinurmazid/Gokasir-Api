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
        Schema::create('sales_activation_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('token_pricing_id')->constrained('token_pricing')->cascadeOnDelete();
            $table->decimal('custom_price', 15, 2);
            $table->timestamps();
            
            $table->unique(['sales_id', 'token_pricing_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_activation_prices');
    }
};
