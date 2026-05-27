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
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();

            $table->string('session_token', 100)->unique();      // token unik sesi, disimpan di browser customer
            $table->integer('pax')->default(1);                  // jumlah tamu
            $table->string('customer_name', 100)->nullable();    // nama customer (opsional, dari form)
            $table->string('customer_phone', 30)->nullable();

            $table->enum('status', ['active', 'ordered', 'paid', 'closed'])->default('active');

            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();

            $table->index(['store_id', 'status']);
            $table->index('session_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
