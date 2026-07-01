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
        Schema::create('sales_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // activation_bonus, transaction_bonus, withdraw
            $table->decimal('amount', 15, 2);
            $table->string('reference_type')->nullable(); // e.g. App\Models\TokenTopup, App\Models\Sale
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->timestamps();
            
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_wallet_transactions');
    }
};
