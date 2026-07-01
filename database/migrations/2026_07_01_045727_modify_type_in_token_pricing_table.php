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
        Schema::table('token_pricing', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE token_pricing MODIFY COLUMN type ENUM('unit', 'package', 'activation') DEFAULT 'unit'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('token_pricing', function (Blueprint $table) {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE token_pricing MODIFY COLUMN type ENUM('unit', 'package') DEFAULT 'unit'");
        });
    }
};
