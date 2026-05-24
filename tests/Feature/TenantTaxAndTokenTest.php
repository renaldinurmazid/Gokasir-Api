<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\TaxSetting;
use App\Models\Tenant;
use App\Models\TokenUsageLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantTaxAndTokenTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $owner;
    protected Store $store;
    protected Category $category;
    protected Unit $unit;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create a tenant (automatically observer will run, welcome gift 500, tax setting 12% PPN)
        $this->tenant = Tenant::create([
            'business_name' => 'Test Tenant',
            'business_type' => 'retail',
            'email' => 'test@tenant.com',
            'phone' => '0812345678',
            'status' => 'active',
        ]);

        // 2. Create an owner
        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
            'name' => 'Owner Test',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // 3. Create a store
        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Toko Test',
            'address' => 'Test Address',
        ]);

        // 4. Create master data
        $this->category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Makanan',
        ]);

        $this->unit = Unit::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Pcs',
            'code' => 'PCS',
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'TEST-SKU',
            'name' => 'Product Test',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'is_active' => true,
        ]);
    }

    public function test_tenant_registration_welcome_gift_and_default_tax()
    {
        // Assert token balance starts at 500
        $this->tenant->refresh();
        $this->assertEquals(500, $this->tenant->token_balance);

        // Assert tax setting is provisioned
        $taxSetting = TaxSetting::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($taxSetting);
        $this->assertEquals(12.00, $taxSetting->tax_rate);
        $this->assertTrue($taxSetting->tax_enabled);
        $this->assertFalse($taxSetting->tax_inclusive);

        // Assert welcome gift log exists
        $log = TokenUsageLog::where('tenant_id', $this->tenant->id)
            ->where('type', 'gift')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals(500, $log->amount);
        $this->assertEquals(500, $log->balance_after);
    }

    public function test_sale_deducts_token_successfully()
    {
        Sanctum::actingAs($this->owner);

        // Prepare sale request body
        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 2,
                    'price' => 2000,
                ]
            ]
        ];

        $response = $this->postJson('/api/sales', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => ['token_balance_remaining']]);
        
        // Assert balance remaining in response is 499
        $response->assertJsonPath('data.token_balance_remaining', 499);

        // Assert in DB
        $this->tenant->refresh();
        $this->assertEquals(499, $this->tenant->token_balance);

        // Assert token log exists
        $log = TokenUsageLog::where('tenant_id', $this->tenant->id)
            ->where('type', 'deduct')
            ->first();
        $this->assertNotNull($log);
        $this->assertEquals(-1, $log->amount);
        $this->assertEquals(499, $log->balance_after);
    }

    public function test_sale_blocked_when_token_balance_is_zero()
    {
        // Deplete token balance
        $this->tenant->update(['token_balance' => 0]);

        Sanctum::actingAs($this->owner);

        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 1,
                    'price' => 2000,
                ]
            ]
        ];

        $response = $this->postJson('/api/sales', $payload);

        $response->assertStatus(402);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('Saldo token habis', $response->json('message'));
    }

    public function test_tax_settings_crud_endpoints()
    {
        Sanctum::actingAs($this->owner);

        // 1. Get current settings
        $response = $this->getJson('/api/tax-settings');
        $response->assertStatus(200)
            ->assertJsonPath('data.tax_rate', '12.00')
            ->assertJsonPath('data.tax_inclusive', false);

        // 2. Update settings
        $updateResponse = $this->putJson('/api/tax-settings', [
            'tax_rate' => 10.00,
            'tax_inclusive' => true,
            'tax_name' => 'VAT',
        ]);

        $updateResponse->assertStatus(200);
        $this->assertEquals(10.00, $this->tenant->fresh()->tax_rate);

        // Verify update persisted in DB
        $setting = TaxSetting::where('tenant_id', $this->tenant->id)->first();
        $this->assertEquals(10.00, $setting->tax_rate);
        $this->assertTrue($setting->tax_inclusive);
        $this->assertEquals('VAT', $setting->tax_name);
    }

    public function test_exclusive_tax_calculation()
    {
        Sanctum::actingAs($this->owner);

        // Configure exclusive tax at 10%
        TaxSetting::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'tax_rate' => 10.00,
                'tax_enabled' => true,
                'tax_inclusive' => false,
            ]
        );

        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 2,
                    'price' => 2000, // Subtotal 4000
                ]
            ]
        ];

        // Exclusive tax 10% of 4000 = 400. Grand total = 4400.
        $response = $this->postJson('/api/sales', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.subtotal', 4000);
        $response->assertJsonPath('data.tax_amount', 400);
        $response->assertJsonPath('data.grand_total', 4400);
    }

    public function test_inclusive_tax_calculation()
    {
        Sanctum::actingAs($this->owner);

        // Configure inclusive tax at 10%
        TaxSetting::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'tax_rate' => 10.00,
                'tax_enabled' => true,
                'tax_inclusive' => true,
            ]
        );

        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 2,
                    'price' => 2200, // Subtotal 4400
                ]
            ]
        ];

        // Inclusive tax 10% on 4400:
        // tax = 4400 - (4400 / 1.1) = 4400 - 4000 = 400. Grand total = 4400.
        $response = $this->postJson('/api/sales', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.subtotal', 4400);
        $response->assertJsonPath('data.tax_amount', 400);
        $response->assertJsonPath('data.grand_total', 4400);
    }

    public function test_disabled_tax_calculation()
    {
        Sanctum::actingAs($this->owner);

        // Disable tax
        TaxSetting::updateOrCreate(
            ['tenant_id' => $this->tenant->id],
            [
                'tax_enabled' => false,
            ]
        );

        $payload = [
            'store_id' => $this->store->id,
            'payment_method' => 'cash',
            'paid_amount' => 5000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 2,
                    'price' => 2000, // Subtotal 4000
                ]
            ]
        ];

        $response = $this->postJson('/api/sales', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.subtotal', 4000);
        $response->assertJsonPath('data.tax_amount', 0);
        $response->assertJsonPath('data.grand_total', 4000);
    }
}
