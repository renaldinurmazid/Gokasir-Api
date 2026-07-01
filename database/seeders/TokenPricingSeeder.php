<?php

namespace Database\Seeders;

use App\Models\TokenPricing;
use Illuminate\Database\Seeder;

class TokenPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TokenPricing::insert([
            [
                'type'         => 'unit',
                'name'         => 'Harga Satuan Token',
                'description'  => 'Rp 100 per token, minimum 10 token',
                'price'        => 100.00,
                'token_amount' => 1,
                'token_bonus'  => 0,
                'is_active'    => true,
                'sort_order'   => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'type'         => 'activation',
                'name'         => 'Paket Aktivasi Basic',
                'description'  => 'Mendapatkan 500 Token',
                'price'        => 50000.00,
                'token_amount' => 500,
                'token_bonus'  => 0,
                'is_active'    => true,
                'sort_order'   => 2,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'type'         => 'activation',
                'name'         => 'Paket Aktivasi Pro (Printer)',
                'description'  => 'Mendapatkan 500 Token + Printer Thermal',
                'price'        => 250000.00,
                'token_amount' => 500,
                'token_bonus'  => 0,
                'is_active'    => true,
                'sort_order'   => 3,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        ]);
    }
}
