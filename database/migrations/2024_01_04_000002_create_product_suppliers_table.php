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
        Schema::create('product_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();

            $table->decimal('purchase_price', 15, 2)->default(0);  // harga beli dari supplier ini
            $table->string('supplier_sku', 100)->nullable();        // kode produk versi supplier
            $table->integer('min_order_qty')->default(1);           // minimum order
            $table->boolean('is_preferred')->default(false);        // supplier utama untuk produk ini

            $table->timestamps();

            $table->unique(['store_id', 'product_id', 'supplier_id']);
            $table->index(['store_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_suppliers');
    }
};
