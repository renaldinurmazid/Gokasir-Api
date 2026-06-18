<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TesterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create Tenant
        $tenant = Tenant::firstOrCreate([
            'email' => 'corporate@gokasir.net',
        ], [
            'business_name' => 'GoKasir Group',
            'business_type' => 'Retail & Grosir',
            'phone' => '021-1234567',
            'subscription_plan' => 'free',
            'status' => 'active',
            'expired_at' => now()->addYears(5),
        ]);

        // 2. Get or create Store
        $store = Store::firstOrCreate([
            'tenant_id' => $tenant->id,
            'email' => 'toko.utama@gokasir.net',
        ], [
            'name' => 'Toko GoKasir Utama',
            'address' => 'Jl. Jenderal Sudirman No. 45',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'phone' => '0812-3456-7890',
            'receipt_footer' => 'Terima kasih telah berbelanja di Toko GoKasir Utama!',
        ]);

        // 3. Create or update Tester User
        User::updateOrCreate([
            'email' => 'tester@gokasir.net',
        ], [
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'role' => 'owner',
            'name' => 'Tester GoKasir',
            'phone' => '08999999999',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);
    }
}
