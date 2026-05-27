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
        Schema::create('table_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('table_sessions')->cascadeOnDelete();

            $table->string('order_number', 100)->unique();       // "ORD-20240101-0001"

            // Status alur pesanan
            $table->enum('status', [
                'pending',      // baru masuk dari customer, belum dikonfirmasi
                'confirmed',    // kasir/waiter sudah konfirmasi
                'cancelled',    // dibatalkan
                'paid',         // sudah dibayar (sudah jadi sale)
            ])->default('pending');

            // Pembayaran
            $table->enum('payment_type', ['cash', 'cashless'])->nullable();
            $table->enum('payment_status', ['unpaid', 'pending_payment', 'paid'])->default('unpaid');
            $table->string('payment_method', 50)->nullable();    // qris, va, dll (jika cashless)
            $table->string('payment_channel', 50)->nullable();

            // iPaymu
            $table->string('ipaymu_trx_id', 100)->nullable()->index();
            $table->text('payment_url')->nullable();
            $table->timestamp('payment_expired_at')->nullable();

            // Total
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // Relasi ke sales (setelah paid)
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();

            $table->text('notes')->nullable();                   // catatan dari customer
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_orders');
    }
};
