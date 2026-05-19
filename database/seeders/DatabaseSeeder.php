<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Store;
use App\Models\User;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Tenant
        $tenant = Tenant::create([
            'business_name' => 'GoKasir Group',
            'business_type' => 'Retail & Grosir',
            'email' => 'corporate@gokasir.net',
            'phone' => '021-1234567',
            'subscription_plan' => 'pro',
            'status' => 'active',
            'expired_at' => now()->addYears(5),
        ]);

        // 2. Create Store
        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Toko GoKasir Utama',
            'address' => 'Jl. Jenderal Sudirman No. 45',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'phone' => '0812-3456-7890',
            'email' => 'toko.utama@gokasir.net',
            'receipt_footer' => 'Terima kasih telah berbelanja di Toko GoKasir Utama!',
        ]);

        // 3. Create Owner User
        $owner = User::create([
            'tenant_id' => $tenant->id,
            'store_id' => null,
            'role' => 'owner',
            'name' => 'Owner GoKasir',
            'email' => 'owner@gokasir.net',
            'phone' => '08111111111',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        // 4. Create Cashier User
        $cashier = User::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'role' => 'cashier',
            'name' => 'Kasir GoKasir',
            'email' => 'cashier@gokasir.net',
            'phone' => '08222222222',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        // 5. Create Categories
        $catFood = Category::create(['tenant_id' => $tenant->id, 'name' => 'Makanan']);
        $catDrink = Category::create(['tenant_id' => $tenant->id, 'name' => 'Minuman']);
        $catSnack = Category::create(['tenant_id' => $tenant->id, 'name' => 'Snack']);

        // 6. Create Units
        $unitPcs = Unit::create(['tenant_id' => $tenant->id, 'name' => 'Pcs', 'code' => 'PCS']);
        $unitBox = Unit::create(['tenant_id' => $tenant->id, 'name' => 'Box', 'code' => 'BOX']);

        // 7. Create Products
        $p1 = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $catDrink->id,
            'unit_id' => $unitPcs->id,
            'sku' => 'AQUA-600ML',
            'barcode' => '8886008101053',
            'name' => 'Aqua Botol 600ml',
            'description' => 'Air mineral berkualitas kemasan botol sedang.',
            'purchase_price' => 2500.00,
            'selling_price' => 3500.00,
            'min_stock' => 10,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $catFood->id,
            'unit_id' => $unitPcs->id,
            'sku' => 'INDOMIE-GORENG',
            'barcode' => '070662011036',
            'name' => 'Indomie Goreng Spesial',
            'description' => 'Mi instan goreng rasa spesial original.',
            'purchase_price' => 2800.00,
            'selling_price' => 3500.00,
            'min_stock' => 15,
            'is_active' => true,
        ]);

        $p3 = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $catSnack->id,
            'unit_id' => $unitPcs->id,
            'sku' => 'CHITATO-BEEF',
            'barcode' => '8992696404313',
            'name' => 'Chitato Sapi Panggang 68g',
            'description' => 'Keripik kentang Chitato rasa sapi panggang.',
            'purchase_price' => 8500.00,
            'selling_price' => 10500.00,
            'min_stock' => 5,
            'is_active' => true,
        ]);

        // 8. Create Stocks
        Stock::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'product_id' => $p1->id,
            'qty' => 150.00,
        ]);

        Stock::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'product_id' => $p2->id,
            'qty' => 200.00,
        ]);

        Stock::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'product_id' => $p3->id,
            'qty' => 80.00,
        ]);
    }
}
