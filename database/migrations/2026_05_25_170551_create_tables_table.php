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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('name', 50);                          // "Meja A", "Meja 1", "VIP 1"
            $table->string('code', 30)->unique();                // kode unik untuk QR: "TBL-ABC123"
            $table->integer('capacity')->default(4);             // kapasitas kursi
            $table->string('location', 100)->nullable();         // "Lantai 1", "Outdoor", "VIP Room"
            $table->boolean('is_active')->default(true);

            // QR Code disimpan sebagai URL atau base64
            $table->text('qr_url')->nullable();                  // URL ke halaman order customer
            $table->text('qr_image')->nullable();                // base64 / path gambar QR

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
