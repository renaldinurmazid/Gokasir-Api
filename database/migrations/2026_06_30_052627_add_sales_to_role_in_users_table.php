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
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'cashier', 'sales') DEFAULT 'cashier'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'cashier' WHERE role = 'sales'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('owner', 'cashier') DEFAULT 'cashier'");
    }
};
