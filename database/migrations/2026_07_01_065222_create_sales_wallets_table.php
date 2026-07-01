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
        Schema::create('sales_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
            
            $table->unique('sales_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_wallets');
    }
};
