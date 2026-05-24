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
        Schema::create('token_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_id')->nullable()->constrained('token_pricing')->nullOnDelete();
            $table->string('order_number', 100)->unique();
            $table->integer('token_amount');
            $table->decimal('price', 15, 2);
            $table->integer('qty')->default(1);
            $table->string('ipaymu_trx_id', 100)->nullable()->index();
            $table->string('ipaymu_reference', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_channel', 50)->nullable();
            $table->text('payment_url')->nullable();
            $table->text('ipaymu_raw_response')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->integer('balance_before')->nullable();
            $table->integer('balance_after')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_topups');
    }
};
