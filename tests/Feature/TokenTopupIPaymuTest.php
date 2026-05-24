<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TokenPricing;
use App\Models\TokenTopup;
use App\Models\TokenUsageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokenTopupIPaymuTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $owner;
    protected TokenPricing $pricingUnit;
    protected TokenPricing $pricingPackage;
    protected string $va = '000077771111';
    protected string $apiKey = 'sandbox-test-api-key';

    protected function setUp(): void
    {
        parent::setUp();

        // Configure dummy values for iPaymu during tests
        config([
            'ipaymu.sandbox' => true,
            'ipaymu.va' => $this->va,
            'ipaymu.api_key' => $this->apiKey,
        ]);

        // Create Tenant
        $this->tenant = Tenant::create([
            'business_name' => 'Topup Tenant',
            'business_type' => 'minimarket',
            'email' => 'topup@tenant.com',
            'phone' => '089999999',
            'status' => 'active',
        ]);

        // Create Owner
        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'role' => 'owner',
            'name' => 'Owner Topup',
            'email' => 'owner.topup@test.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        // Seed some pricing packages
        $this->pricingUnit = TokenPricing::create([
            'type' => 'unit',
            'name' => 'Harga Satuan Token',
            'description' => 'Rp 100 per token',
            'price' => 100.00,
            'token_amount' => 1,
            'token_bonus' => 0,
            'is_active' => true,
        ]);

        $this->pricingPackage = TokenPricing::create([
            'type' => 'package',
            'name' => 'Paket Hemat 500K',
            'description' => '5.000 token + bonus 500',
            'price' => 500000.00,
            'token_amount' => 5000,
            'token_bonus' => 500,
            'is_active' => true,
        ]);
    }

    public function test_get_token_pricing_endpoint()
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/token-pricing');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.type', 'unit')
            ->assertJsonPath('data.1.type', 'package')
            ->assertJsonPath('data.1.total_token', 5500);
    }

    public function test_get_token_balance_endpoint()
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson('/api/token-balance');

        $response->assertStatus(200)
            ->assertJsonPath('data.token_balance', 500); // Starts at 500 welcome gift
    }

    public function test_create_topup_order_unit_success()
    {
        Sanctum::actingAs($this->owner);

        // Fake the HTTP response of iPaymu api
        Http::fake([
            '*/payment' => Http::response([
                'Status' => 200,
                'Message' => 'Success',
                'Data' => [
                    'TransactionId' => 999123,
                    'Url' => 'https://sandbox.ipaymu.com/payment/999123',
                ]
            ], 200)
        ]);

        $payload = [
            'pricing_id' => $this->pricingUnit->id,
            'qty' => 50, // 50 tokens at 100 each = 5000 Rp
            'payment_method' => 'qris',
            'payment_channel' => 'qris',
        ];

        $response = $this->postJson('/api/token-topups', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_amount', 50)
            ->assertJsonPath('data.price', '5000.00')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.payment_url', 'https://sandbox.ipaymu.com/payment/999123');

        $this->assertDatabaseHas('token_topups', [
            'tenant_id' => $this->tenant->id,
            'pricing_id' => $this->pricingUnit->id,
            'token_amount' => 50,
            'price' => 5000,
            'ipaymu_trx_id' => '999123',
            'status' => 'pending',
        ]);
    }

    public function test_create_topup_order_package_success()
    {
        Sanctum::actingAs($this->owner);

        // Fake the HTTP response of iPaymu api
        Http::fake([
            '*/payment' => Http::response([
                'Status' => 200,
                'Message' => 'Success',
                'Data' => [
                    'TransactionId' => 999456,
                    'Url' => 'https://sandbox.ipaymu.com/payment/999456',
                ]
            ], 200)
        ]);

        $payload = [
            'pricing_id' => $this->pricingPackage->id,
            'payment_method' => 'va',
            'payment_channel' => 'bca',
        ];

        $response = $this->postJson('/api/token-topups', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_amount', 5500) // 5000 + 500 bonus
            ->assertJsonPath('data.price', '500000.00')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('token_topups', [
            'tenant_id' => $this->tenant->id,
            'pricing_id' => $this->pricingPackage->id,
            'token_amount' => 5500,
            'price' => 500000,
            'ipaymu_trx_id' => '999456',
            'status' => 'pending',
        ]);
    }

    public function test_ipaymu_webhook_verifies_signature_and_credits_tokens()
    {
        // 1. Create a pending topup order manually in database
        $topup = TokenTopup::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->owner->id,
            'pricing_id' => $this->pricingPackage->id,
            'order_number' => 'TKN-TEST-999',
            'token_amount' => 5500,
            'price' => 500000,
            'qty' => 1,
            'ipaymu_trx_id' => 'TRX-WEBHOOK-123',
            'payment_method' => 'va',
            'payment_channel' => 'bca',
            'status' => 'pending',
        ]);

        // 2. Prepare the webhook payload iPaymu sends
        $payload = [
            'trx_id' => '193205',
            'sid' => 'TRX-WEBHOOK-123',
            'reference_id' => 'TKN-TEST-999',
            'status' => 'berhasil',
            'status_code' => 1,
            'amount' => 500000,
            'via' => 'qris',
            'channel' => 'mpm',
        ];

        $bodyString = json_encode($payload);

        // 3. Compute expected signature using new HMAC-SHA256 formula
        $bodyHash = strtolower(hash('sha256', $bodyString));
        $stringToSign = "POST:" . $this->va . ":" . $bodyHash . ":" . $this->apiKey;
        $expectedSignature = hash_hmac('sha256', $stringToSign, $this->apiKey);

        // 4. Send webhook with correct signature
        $response = $this->postJson('/api/webhooks/ipaymu', $payload, [
            'Signature' => $expectedSignature,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');

        // 5. Verify database updates
        $topup->refresh();
        $this->assertEquals('paid', $topup->status);
        $this->assertNotNull($topup->paid_at);

        // Balance updated
        $this->tenant->refresh();
        $this->assertEquals(6000, $this->tenant->token_balance); // 500 + 5500 = 6000
        $this->assertEquals(5500, $this->tenant->token_lifetime_topup);

        // Log created
        $this->assertDatabaseHas('token_usage_logs', [
            'tenant_id' => $this->tenant->id,
            'type' => 'topup',
            'amount' => 5500,
            'balance_after' => 6000,
        ]);
    }

    public function test_ipaymu_webhook_rejects_invalid_signature()
    {
        $payload = [
            'trx_id' => 'TRX-WEBHOOK-123',
            'status' => 'berhasil',
            'sid' => 'TKN-TEST-999',
            'amount' => 500000,
        ];

        $response = $this->postJson('/api/webhooks/ipaymu', $payload, [
            'Signature' => 'totally-wrong-signature',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', 'invalid_signature');
    }

    public function test_mitra_custom_token_pricing_calculation()
    {
        // 1. Set custom mitra price for tenant
        $this->tenant->update(['harga_token' => 50.00]);

        Sanctum::actingAs($this->owner);

        // 2. Pricing index should reflect custom price for unit pricing
        $pricingResponse = $this->getJson('/api/token-pricing');
        $pricingResponse->assertStatus(200)
            ->assertJsonPath('data.0.effective_price', 50)
            ->assertJsonPath('data.0.is_mitra_price', true)
            ->assertJsonPath('data.1.effective_price', 500000)
            ->assertJsonPath('data.1.is_mitra_price', false);

        // 3. Fake iPaymu API redirect response
        Http::fake([
            '*/payment' => Http::response([
                'Status' => 200,
                'Message' => 'success',
                'Data' => [
                    'SessionID' => 'mitra-trx-123',
                    'Url' => 'https://sandbox.ipaymu.com/payment/mitra-trx-123',
                ]
            ], 200)
        ]);

        // 4. Create unit checkout with qty 10 (total: 10 * 50 = 500)
        $topupPayload = [
            'pricing_id' => $this->pricingUnit->id,
            'qty' => 10,
        ];

        $topupResponse = $this->postJson('/api/token-topups', $topupPayload);
        $topupResponse->assertStatus(201)
            ->assertJsonPath('data.price', '500.00')
            ->assertJsonPath('data.token_amount', 10);
    }

    public function test_admin_set_mitra_token_price()
    {
        Sanctum::actingAs($this->owner);

        $response = $this->putJson("/api/admin/tenants/{$this->tenant->id}/token-price", [
            'harga_token' => 75.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.harga_token', 75)
            ->assertJsonPath('data.is_mitra', true);

        $this->assertEquals(75.00, $this->tenant->fresh()->harga_token);

        // Revoke / set to 0
        $revokeResponse = $this->putJson("/api/admin/tenants/{$this->tenant->id}/token-price", [
            'harga_token' => 0.00,
        ]);

        $revokeResponse->assertStatus(200)
            ->assertJsonPath('data.harga_token', 0)
            ->assertJsonPath('data.is_mitra', false);

        $this->assertEquals(0.00, $this->tenant->fresh()->harga_token);
    }
}
