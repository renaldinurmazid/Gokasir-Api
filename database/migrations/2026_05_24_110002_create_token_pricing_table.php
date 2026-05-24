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
        Schema::create('token_pricing', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['unit', 'package'])->default('unit');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(100);
            $table->integer('token_amount')->default(1);
            $table->integer('token_bonus')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('token_pricing');
    }
};
