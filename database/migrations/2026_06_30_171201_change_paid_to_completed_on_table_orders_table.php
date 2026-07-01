<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan 'completed' ke enum sementara masih ada 'paid'
        DB::statement("ALTER TABLE table_orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'paid', 'completed') DEFAULT 'pending'");
        
        // 2. Update data lama
        DB::table('table_orders')->where('status', 'paid')->update(['status' => 'completed']);
        
        // 3. Hapus 'paid' dari enum
        DB::statement("ALTER TABLE table_orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Tambahkan 'paid' ke enum sementara masih ada 'completed'
        DB::statement("ALTER TABLE table_orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'paid', 'completed') DEFAULT 'pending'");
        
        // 2. Rollback data lama
        DB::table('table_orders')->where('status', 'completed')->update(['status' => 'paid']);
        
        // 3. Hapus 'completed' dari enum
        DB::statement("ALTER TABLE table_orders MODIFY COLUMN status ENUM('pending', 'confirmed', 'cancelled', 'paid') DEFAULT 'pending'");
    }
};
