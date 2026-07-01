<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Store;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendMessageWhatsAppJobs;
use App\Jobs\SendTelegramMessageJobs;
use Illuminate\Support\Facades\Http;


class AuthController extends BaseApiController
{
    // GET /api/business-types
    public function businessTypes()
    {
        $types = [
            ['value' => 'retail', 'label' => 'Retail / Toko Umum'],
            ['value' => 'grocery', 'label' => 'Toko Sembako / Grocery'],
            ['value' => 'minimarket', 'label' => 'Minimarket'],
            ['value' => 'fashion', 'label' => 'Fashion / Pakaian'],
            ['value' => 'food_beverage', 'label' => 'Makanan & Minuman'],
            ['value' => 'restaurant', 'label' => 'Restoran'],
            ['value' => 'cafe', 'label' => 'Cafe / Coffee Shop'],
            ['value' => 'bakery', 'label' => 'Bakery / Roti & Kue'],
            ['value' => 'pharmacy', 'label' => 'Apotek'],
            ['value' => 'beauty', 'label' => 'Beauty / Kosmetik'],
            ['value' => 'barbershop', 'label' => 'Barbershop / Salon'],
            ['value' => 'laundry', 'label' => 'Laundry'],
            ['value' => 'electronics', 'label' => 'Elektronik & Gadget'],
            ['value' => 'computer_store', 'label' => 'Komputer & Aksesoris'],
            ['value' => 'phone_store', 'label' => 'Counter HP / Pulsa'],
            ['value' => 'automotive', 'label' => 'Otomotif / Bengkel'],
            ['value' => 'hardware', 'label' => 'Bangunan / Material'],
            ['value' => 'pet_shop', 'label' => 'Pet Shop'],
            ['value' => 'book_store', 'label' => 'Toko Buku / ATK'],
            ['value' => 'furniture', 'label' => 'Furniture'],
            ['value' => 'health', 'label' => 'Kesehatan'],
            ['value' => 'sports', 'label' => 'Olahraga'],
            ['value' => 'jewelry', 'label' => 'Perhiasan'],
            ['value' => 'wholesale', 'label' => 'Grosir'],
            ['value' => 'service', 'label' => 'Jasa / Service'],
            ['value' => 'online_shop', 'label' => 'Online Shop'],
            ['value' => 'other', 'label' => 'Lainnya']
        ];

        return $this->ok($types, 'Business types list retrieved successfully.');
    }

    // POST /api/auth/register
    public function register(Request $request)
    {
        $role = $request->input('role', 'owner');

        $request->validate([
            'role'          => 'nullable|string|in:owner,sales',
            'business_name' => $role === 'owner' ? 'required|string|max:150' : 'nullable|string|max:150',
            'business_type' => 'nullable|string|max:100',
            'name'          => 'required|string|max:100',
            'email'         => 'nullable|email',
            'password'      => 'required|min:6|confirmed',
            'phone'         => 'required|string|max:30|unique:users,phone',
            'store_name'    => 'nullable|string|max:100',
            'referral_code' => 'nullable|string|exists:users,referral_code',
            'pricing_id'    => 'nullable|exists:token_pricing,id',
        ]);

        $businessName = $role === 'sales' ? 'Toko ' . $request->name : $request->business_name;
        $businessType = $role === 'sales' ? 'retail' : $request->business_type;
        $storeName    = $request->store_name ?? $businessName;

        DB::beginTransaction();
        try {
            // Handle referral
            $referredById = null;
            $isActivated = true;
            if ($request->referral_code && $role !== 'sales') {
                $referrer = User::where('referral_code', $request->referral_code)->first();
                if ($referrer) {
                    $referredById = $referrer->id;
                    if ($referrer->role === 'sales') {
                        $isActivated = false;
                    }
                }
            }

            // 1. Create Tenant
            $tenant = Tenant::create([
                'business_name'     => $businessName,
                'business_type'     => $businessType,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'subscription_plan' => 'free',
                'status'            => 'active',
                'is_activated'      => $isActivated,
                'expired_at'        => now()->addMonths(1),
                'token_balance'     => $referredById ? 0 : 25, // No welcome token if referred
            ]);

            // 2. Create Default Store Branch
            $store = Store::create([
                'tenant_id' => $tenant->id,
                'name'      => $storeName,
            ]);

            // 3. Generate OTP
            $otp = (string) rand(100000, 999999);

            // moved above

            // Generate unique referral code for this new user
            $userReferralCode = strtoupper(\Illuminate\Support\Str::random(8));
            while (User::where('referral_code', $userReferralCode)->exists()) {
                $userReferralCode = strtoupper(\Illuminate\Support\Str::random(8));
            }

            // 4. Create Owner or Sales User
            $user = User::create([
                'tenant_id'      => $tenant->id,
                'store_id'       => null,
                'role'           => $role,
                'name'           => $request->name,
                'email'          => $request->email,
                'phone'          => $request->phone,
                'password'       => Hash::make($request->password),
                'otp_code'       => $otp,
                'otp_expires_at' => now()->addMinutes(5),
                'status'         => 0, // Unverified
                'is_approved'    => $role === 'sales' ? false : true,
                'last_login'     => null,
                'referral_code'  => $userReferralCode,
                'referred_by_id' => $referredById,
            ]);

            // 5. Create 5 Default Products with Category, Unit, and Stock based on Business Type
            $seedData = $this->getSeedingDataByBusinessType($businessType);

            $createdCategories = [];
            foreach ($seedData['categories'] as $catName) {
                $createdCategories[$catName] = Category::create([
                    'tenant_id' => $tenant->id,
                    'name'      => $catName
                ]);
            }

            $createdUnits = [];
            foreach ($seedData['units'] as $key => $unitData) {
                $createdUnits[$key] = Unit::create([
                    'tenant_id' => $tenant->id,
                    'name'      => $unitData['name'],
                    'code'      => $unitData['code']
                ]);
            }

            foreach ($seedData['products'] as $prodData) {
                $product = Product::create([
                    'tenant_id'      => $tenant->id,
                    'category_id'    => $createdCategories[$prodData['category_key']]->id,
                    'unit_id'        => $createdUnits[$prodData['unit_key']]->id,
                    'sku'            => $prodData['sku'],
                    'barcode'        => $prodData['barcode'],
                    'name'           => $prodData['name'],
                    'description'    => $prodData['description'],
                    'image'          => $prodData['image'],
                    'purchase_price' => $prodData['purchase_price'],
                    'selling_price'  => $prodData['selling_price'],
                    'min_stock'      => $prodData['min_stock'],
                    'is_active'      => true,
                ]);

                // Create initial stock in the newly created Store
                Stock::create([
                    'tenant_id'  => $tenant->id,
                    'store_id'   => $store->id,
                    'product_id' => $product->id,
                    'qty'        => $prodData['qty'],
                ]);
            }

            // --- REFERRAL BONUS ---
            if ($referredById && isset($referrer)) {
                // 1. Bonus untuk pendaftar baru
                $tenant->addToken(25);
                \App\Models\TokenUsageLog::create([
                    'tenant_id'      => $tenant->id,
                    'type'           => 'gift',
                    'amount'         => 25,
                    'balance_before' => $tenant->token_balance - 25,
                    'balance_after'  => $tenant->token_balance,
                    'reference_type' => 'referral',
                    'reference_id'   => $referrer->id,
                    'description'    => 'Bonus mendaftar menggunakan kode referal',
                    'created_at'     => now(),
                ]);

                // 2. Bonus untuk pemilik kode referal
                // $referrerTenant = $referrer->tenant;
                // if ($referrerTenant) {
                //     $referrerTenant->addToken(25);
                //     \App\Models\TokenUsageLog::create([
                //         'tenant_id'      => $referrerTenant->id,
                //         'user_id'        => $referrer->id,
                //         'type'           => 'gift',
                //         'amount'         => 25,
                //         'balance_before' => $referrerTenant->token_balance - 25,
                //         'balance_after'  => $referrerTenant->token_balance,
                //         'reference_type' => 'referral',
                //         'reference_id'   => $user->id,
                //         'description'    => 'Bonus referal dari pendaftaran toko: ' . $tenant->business_name,
                //         'created_at'     => now(),
                //     ]);
                // }
            }

            DB::commit();

            // --- CREATE PENDING TOPUP IF PRICING_ID IS PROVIDED ---
            if ($request->pricing_id && $role !== 'sales') {
                $pricing = \App\Models\TokenPricing::find($request->pricing_id);
                if ($pricing && $pricing->type === 'activation') {
                    $orderNumber = 'TKN-' . strtoupper(\Illuminate\Support\Str::random(4)) . '-' . time();
                    $totalPrice = $pricing->price;
                    
                    if (isset($referrer) && $referrer->role === 'sales') {
                        $customPrice = \App\Models\SalesActivationPrice::where('sales_id', $referrer->id)
                            ->where('token_pricing_id', $pricing->id)
                            ->value('custom_price');
                        if ($customPrice) {
                            $totalPrice = $customPrice;
                        }
                    }

                    $topup = \App\Models\TokenTopup::create([
                        'tenant_id'      => $tenant->id,
                        'user_id'        => $user->id,
                        'pricing_id'     => $pricing->id,
                        'order_number'   => $orderNumber,
                        'token_amount'   => $pricing->token_amount,
                        'price'          => $totalPrice,
                        'qty'            => 1,
                        'payment_method' => 'qris',
                        'payment_channel' => 'qris',
                        'status'         => 'pending',
                        'expired_at'     => now()->addHours(24),
                    ]);

                    $buyerPhone = preg_replace('/[^0-9+]/', '', $user->phone);
                    $buyerPhone = preg_replace('/^(?:\+62|0)/', '62', $buyerPhone);

                    try {
                        $ipaymu = app(\App\Services\IPaymuService::class);
                        $ipaymuResponse = $ipaymu->createPayment([
                            'tenant_id'       => $tenant->id,
                            'order_number'    => $orderNumber,
                            'amount'          => (int) $totalPrice,
                            'payment_method'  => 'qris',
                            'payment_channel' => 'qris',
                            'buyer_name'      => $user->name,
                            'buyer_email'     => $user->email ?? 'customer@gokasir.net',
                            'buyer_phone'     => $buyerPhone,
                            'description'     => "Aktivasi {$pricing->name} GoKasir",
                            'notify_url'      => config('app.url') . '/api/webhooks/ipaymu',
                        ]);

                        $responseData = $ipaymuResponse['Data'] ?? [];

                        $topup->update([
                            'ipaymu_trx_id'       => $responseData['TransactionId'] ?? null,
                            'ipaymu_reference'    => $responseData['ReferenceId'] ?? null,
                            'payment_no'          => $responseData['PaymentNo'] ?? null,
                            'payment_name'        => $responseData['PaymentName'] ?? null,
                            'payment_url'         => $responseData['Url'] ?? $responseData['QrImage'] ?? $responseData['QrTemplate'] ?? $responseData['PaymentNo'] ?? null,
                            'expired_at'          => isset($responseData['Expired']) ? \Carbon\Carbon::parse($responseData['Expired']) : now()->addHours(24),
                            'ipaymu_raw_response' => json_encode($ipaymuResponse),
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to create iPaymu activation order during registration: " . $e->getMessage());
                    }
                }
            }

            dispatch(new SendMessageWhatsAppJobs("Kode verifikasi GoKasir Anda adalah: {$otp}. Berlaku selama 5 menit.", $user->phone));
            try {
                if ($role === 'sales') {
                    $text = "📢 *Pendaftaran Baru Sales GoKasir*\n\n"
                        . "👤 *Nama Sales:* " . $user->name . "\n"
                        . "📞 *No. HP:* " . $user->phone . "\n"
                        . "✉️ *Email:* " . ($user->email ?? '-') . "\n"
                        . "🔑 *OTP:* `{$otp}`\n"
                        . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s');
                } else {
                    $text = "📢 *Pendaftaran Baru GoKasir*\n\n"
                        . "🏢 *Nama Bisnis:* " . $tenant->business_name . "\n"
                        . "💼 *Tipe Bisnis:* " . ($tenant->business_type ?? '-') . "\n"
                        . "👤 *Nama Owner:* " . $user->name . "\n"
                        . "📞 *No. HP:* " . $user->phone . "\n"
                        . "✉️ *Email:* " . ($user->email ?? '-') . "\n"
                        . "🏪 *Nama Toko:* " . ($store->name ?? '-') . "\n"
                        . "🔑 *OTP:* `{$otp}`\n"
                        . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s');
                }

                dispatch(new SendTelegramMessageJobs($text));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send Telegram notification: " . $e->getMessage());
            }

            return $this->ok([
                'phone' => $user->phone,
            ], 'Registrasi berhasil. Silakan periksa WhatsApp Anda untuk kode verifikasi (OTP).', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal melakukan registrasi: ' . $e->getMessage(), 500);
        }
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required',
        ]);

        $user = User::where('phone', $request->phone)
            ->orWhere('email', $request->phone)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->fail('Nomor telepon atau password salah.', 401);
        }

        if ($user->status == 0) {
            return $this->fail('Akun belum diverifikasi. Silakan verifikasi kode OTP Anda.', 403);
        }

        if ($user->status != 1) {
            return $this->fail('Akun tidak aktif.', 403);
        }

        if ($user->role === 'sales' && !$user->is_approved) {
            return $this->fail('Akun sales Anda sedang dalam peninjauan. Harap tunggu persetujuan dari admin.', 403);
        }

        $user->update(['last_login' => now()]);
        $token = $user->createToken('gokasir')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user'  => [
                'id'            => $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'role'          => $user->role,
                'store_id'      => $user->store_id,
                'referral_code' => $user->referral_code,
            ],
        ], 'Login berhasil.');
    }

    // POST /api/auth/verify-otp
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'otp_code' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->fail('Pengguna tidak ditemukan.', 404);
        }

        if ($user->status == 1) {
            return $this->fail('Akun sudah terverifikasi.', 400);
        }

        if ($user->otp_code !== $request->otp_code) {
            return $this->fail('Kode OTP salah.', 422);
        }

        if ($user->otp_expires_at < now()) {
            return $this->fail('Kode OTP telah kedaluwarsa.', 422);
        }

        // Verify user
        $user->update([
            'status'         => 1,
            'otp_code'       => null,
            'otp_expires_at' => null,
            'last_login'     => now(),
        ]);

        if ($user->role === 'sales' && !$user->is_approved) {
            return $this->ok(null, 'Verifikasi berhasil. Akun sales Anda saat ini sedang dalam peninjauan admin. Kami akan menghubungi Anda setelah disetujui.');
        }

        $token = $user->createToken('gokasir')->plainTextToken;

        return $this->ok([
            'token' => $token,
            'user'  => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'phone'    => $user->phone,
                'role'     => $user->role,
                'store_id' => $user->store_id,
            ],
            'tenant' => $user->tenant,
            'store'  => $user->store,
        ], 'Verifikasi berhasil dan login otomatis.');
    }

    // POST /api/auth/resend-otp
    public function resendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->fail('Pengguna tidak ditemukan.', 404);
        }

        if ($user->status == 1 && $user->otp_code == null) {
            return $this->fail('Akun sudah terverifikasi.', 400);
        }

        $otp = (string) rand(100000, 999999);
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        dispatch(new SendMessageWhatsAppJobs("Kode verifikasi GoKasir Anda adalah: {$otp}. Berlaku selama 5 menit.", $user->phone));

        // Internal Telegram report
        try {
            $teleText = "🔄 *Kirim Ulang OTP GoKasir*\n\n"
                . "👤 *Nama:* " . $user->name . "\n"
                . "📞 *No. HP:* " . $user->phone . "\n"
                . "🔑 *OTP:* `{$otp}`\n"
                . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s');
            dispatch(new SendTelegramMessageJobs($teleText));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }

        return $this->ok(null, 'Kode OTP baru telah dikirimkan ke WhatsApp Anda.');
    }

    // POST /api/auth/forgot-password
    public function forgotPassword(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->fail('Nomor telepon tidak terdaftar.', 404);
        }

        $otp = (string) rand(100000, 999999);
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        dispatch(new SendMessageWhatsAppJobs("Kode OTP Reset Password GoKasir Anda adalah: {$otp}. Berlaku selama 5 menit.", $user->phone));

        // Internal Telegram report
        try {
            $teleText = "🔐 *Reset Password OTP GoKasir*\n\n"
                . "👤 *Nama:* " . $user->name . "\n"
                . "📞 *No. HP:* " . $user->phone . "\n"
                . "🔑 *OTP:* `{$otp}`\n"
                . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s');
            dispatch(new SendTelegramMessageJobs($teleText));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send Telegram notification: " . $e->getMessage());
        }

        return $this->ok([
            'phone' => $user->phone,
        ], 'Instruksi reset password dan OTP telah dikirim ke WhatsApp Anda.');
    }

    // POST /api/auth/reset-password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string',
            'otp_code' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return $this->fail('Pengguna tidak ditemukan.', 404);
        }

        if ($user->otp_code !== $request->otp_code) {
            return $this->fail('Kode OTP salah.', 422);
        }

        if ($user->otp_expires_at < now()) {
            return $this->fail('Kode OTP telah kedaluwarsa.', 422);
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
            'status'         => 1, // ensure they are active
        ]);

        return $this->ok(null, 'Password berhasil direset. Silakan login dengan password baru Anda.');
    }

    // POST /api/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->ok(null, 'Logout berhasil.');
    }

    // GET /api/auth/me
    public function me(Request $request)
    {
        return $this->ok($request->user()->load('store', 'tenant'));
    }

    // PUT /api/auth/profile
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|max:30',
        ]);

        $request->user()->update($request->only('name', 'phone'));
        return $this->ok($request->user(), 'Profil diperbarui.');
    }

    // PUT /api/auth/change-password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return $this->fail('Password lama tidak cocok.', 422);
        }

        $request->user()->update(['password' => Hash::make($request->new_password)]);
        return $this->ok(null, 'Password berhasil diubah.');
    }

    // DELETE /api/auth/delete-account
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        DB::beginTransaction();
        try {
            if ($user->isOwner()) {
                // Soft delete Tenant
                $tenant = $user->tenant;
                if ($tenant) {
                    $tenant->delete();
                }

                // Soft delete all users belonging to this tenant
                User::where('tenant_id', $user->tenant_id)->delete();
            } else {
                // Soft delete only this cashier user
                $user->delete();
            }

            DB::commit();

            // Revoke current token
            $user->tokens()->delete();

            return $this->ok(null, 'Akun berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal menghapus akun: ' . $e->getMessage(), 500);
        }
    }

    private function getSeedingDataByBusinessType($businessType): array
    {
        switch ($businessType) {
            case 'food_beverage':
            case 'restaurant':
            case 'cafe':
            case 'bakery':
                return [
                    'categories' => ['Kopi', 'Non-Kopi', 'Makanan Utama', 'Cemilan', 'Pastry'],
                    'units' => [
                        'CUP' => ['name' => 'Cup', 'code' => 'CUP'],
                        'PORSI' => ['name' => 'Porsi', 'code' => 'PORSI'],
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                    ],
                    'products' => [
                        [
                            'name' => 'Kopi Susu Gula Aren',
                            'category_key' => 'Kopi',
                            'unit_key' => 'CUP',
                            'sku' => 'KOPI-AREN',
                            'barcode' => '8990123456782',
                            'purchase_price' => 7000,
                            'selling_price' => 15000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1541167760496-1628856ab772?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Perpaduan espresso dengan susu segar dan gula aren.',
                            'qty' => 100
                        ],
                        [
                            'name' => 'Matcha Latte Ice',
                            'category_key' => 'Non-Kopi',
                            'unit_key' => 'CUP',
                            'sku' => 'MATCHA-LATTE',
                            'barcode' => '8990123456783',
                            'purchase_price' => 8000,
                            'selling_price' => 18000,
                            'min_stock' => 8,
                            'image' => 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Matcha jepang asli premium dengan es dan susu segar.',
                            'qty' => 80
                        ],
                        [
                            'name' => 'Nasi Goreng Spesial',
                            'category_key' => 'Makanan Utama',
                            'unit_key' => 'PORSI',
                            'sku' => 'NASI-GORENG',
                            'barcode' => '8990123456784',
                            'purchase_price' => 12000,
                            'selling_price' => 25000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1603133872878-685f57b40d4a?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Nasi goreng dengan telur, sosis, ayam, dan kerupuk.',
                            'qty' => 50
                        ],
                        [
                            'name' => 'French Fries',
                            'category_key' => 'Cemilan',
                            'unit_key' => 'PORSI',
                            'sku' => 'FRENCH-FRIES',
                            'barcode' => '8990123456785',
                            'purchase_price' => 6000,
                            'selling_price' => 15000,
                            'min_stock' => 6,
                            'image' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kentang goreng gurih renyah dengan cocolan saus sambal.',
                            'qty' => 60
                        ],
                        [
                            'name' => 'Chocolate Croissant',
                            'category_key' => 'Pastry',
                            'unit_key' => 'PCS',
                            'sku' => 'CROISSANT-CHOCO',
                            'barcode' => '8990123456786',
                            'purchase_price' => 9000,
                            'selling_price' => 20000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Roti croissant mentega renyah isi cokelat lumer.',
                            'qty' => 30
                        ]
                    ]
                ];

            case 'fashion':
            case 'beauty':
            case 'jewelry':
                return [
                    'categories' => ['Atasan', 'Bawahan', 'Kosmetik', 'Aksesoris', 'Perawatan Wajah'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'SET' => ['name' => 'Set', 'code' => 'SET'],
                    ],
                    'products' => [
                        [
                            'name' => 'Kaos Polos Combed 30s',
                            'category_key' => 'Atasan',
                            'unit_key' => 'PCS',
                            'sku' => 'KAOS-POLOS',
                            'barcode' => '8991112223334',
                            'purchase_price' => 25000,
                            'selling_price' => 45000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kaos polos katun combed 30s premium adem.',
                            'qty' => 50
                        ],
                        [
                            'name' => 'Celana Chino Slim Fit',
                            'category_key' => 'Bawahan',
                            'unit_key' => 'PCS',
                            'sku' => 'CHINO-SLIM',
                            'barcode' => '8992223334445',
                            'purchase_price' => 75000,
                            'selling_price' => 135000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Celana chino panjang slim fit bahan stretch melar.',
                            'qty' => 30
                        ],
                        [
                            'name' => 'Matte Lip Cream',
                            'category_key' => 'Kosmetik',
                            'unit_key' => 'PCS',
                            'sku' => 'LIP-CREAM',
                            'barcode' => '8993334445556',
                            'purchase_price' => 30000,
                            'selling_price' => 55000,
                            'min_stock' => 8,
                            'image' => 'https://images.unsplash.com/photo-1586495777744-4413f21062fa?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Lipstik liquid matte tahan lama tidak membuat bibir kering.',
                            'qty' => 40
                        ],
                        [
                            'name' => 'Serum Hyaluronic Acid',
                            'category_key' => 'Perawatan Wajah',
                            'unit_key' => 'PCS',
                            'sku' => 'SERUM-HA',
                            'barcode' => '8994445556667',
                            'purchase_price' => 60000,
                            'selling_price' => 95000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1620916566398-39f1143ab7be?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Serum pelembab wajah mencerahkan dan menghidrasi kulit.',
                            'qty' => 25
                        ],
                        [
                            'name' => 'Kalung Perak Klasik',
                            'category_key' => 'Aksesoris',
                            'unit_key' => 'PCS',
                            'sku' => 'KALUNG-SILVER',
                            'barcode' => '8995556667778',
                            'purchase_price' => 120000,
                            'selling_price' => 199000,
                            'min_stock' => 3,
                            'image' => 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kalung perak 925 antikarat desain minimalis anggun.',
                            'qty' => 15
                        ]
                    ]
                ];

            case 'laundry':
            case 'barbershop':
            case 'service':
                return [
                    'categories' => ['Jasa Utama', 'Paket Kilat', 'Perawatan Rambut', 'Produk Ritel', 'Layanan Extra'],
                    'units' => [
                        'KG' => ['name' => 'Kg', 'code' => 'KG'],
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'JASA' => ['name' => 'Jasa', 'code' => 'JASA'],
                    ],
                    'products' => [
                        [
                            'name' => 'Cuci Setrika Regular',
                            'category_key' => 'Jasa Utama',
                            'unit_key' => 'KG',
                            'sku' => 'JASA-CUCI-SETRIKA',
                            'barcode' => '8990000000001',
                            'purchase_price' => 0,
                            'selling_price' => 7000,
                            'min_stock' => 0,
                            'image' => 'https://images.unsplash.com/photo-1545130810-689d2c887ec5?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Layanan cuci dan setrika wangi regular estimasi selesai 2 hari.',
                            'qty' => 999
                        ],
                        [
                            'name' => 'Haircut Premium + Wash',
                            'category_key' => 'Jasa Utama',
                            'unit_key' => 'JASA',
                            'sku' => 'BARBER-HAIRCUT',
                            'barcode' => '8990000000002',
                            'purchase_price' => 0,
                            'selling_price' => 35000,
                            'min_stock' => 0,
                            'image' => 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Cukur rambut modis ditangani kapster profesional, cuci rambut pijat santai.',
                            'qty' => 999
                        ],
                        [
                            'name' => 'Matte Pomade Strong Hold',
                            'category_key' => 'Produk Ritel',
                            'unit_key' => 'PCS',
                            'sku' => 'BARBER-POMADE',
                            'barcode' => '8990000000003',
                            'purchase_price' => 40000,
                            'selling_price' => 75000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Pomade penata rambut pria dengan kekuatan rekat tinggi beraroma segar.',
                            'qty' => 20
                        ],
                        [
                            'name' => 'Cuci Bed Cover Besar',
                            'category_key' => 'Layanan Extra',
                            'unit_key' => 'PCS',
                            'sku' => 'LAUNDRY-BEDCOVER',
                            'barcode' => '8990000000004',
                            'purchase_price' => 0,
                            'selling_price' => 25000,
                            'min_stock' => 0,
                            'image' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Layanan cuci bersih bedcover ukuran king wangi higienis.',
                            'qty' => 999
                        ],
                        [
                            'name' => 'Hair Tonic Ginseng',
                            'category_key' => 'Perawatan Rambut',
                            'unit_key' => 'PCS',
                            'sku' => 'BARBER-TONIC',
                            'barcode' => '8990000000005',
                            'purchase_price' => 25000,
                            'selling_price' => 45000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1601049541289-9b1b7bbbfe19?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Tonik penutrisi kulit kepala untuk mengurangi rambut rontok.',
                            'qty' => 15
                        ]
                    ]
                ];

            case 'electronics':
            case 'computer_store':
            case 'phone_store':
                return [
                    'categories' => ['Aksesoris Charger', 'Penyimpanan', 'Perangkat Audio', 'Input Device', 'Daya Listrik'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                    ],
                    'products' => [
                        [
                            'name' => 'Kabel Data Fast Charging',
                            'category_key' => 'Aksesoris Charger',
                            'unit_key' => 'PCS',
                            'sku' => 'KABEL-FAST',
                            'barcode' => '8997778880001',
                            'purchase_price' => 8000,
                            'selling_price' => 25000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1543269664-56d93c1b41a6?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kabel data USB type-C mendukung pengisian daya cepat 3A tahan lama.',
                            'qty' => 50
                        ],
                        [
                            'name' => 'Powerbank 10000mAh',
                            'category_key' => 'Daya Listrik',
                            'unit_key' => 'PCS',
                            'sku' => 'POWERBANK-10K',
                            'barcode' => '8997778880002',
                            'purchase_price' => 65000,
                            'selling_price' => 120000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1609592424109-dd87f90e8a71?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Pengisi daya portabel kapasitas 10000mAh dual output aman dibawa ke pesawat.',
                            'qty' => 20
                        ],
                        [
                            'name' => 'Wireless Mouse Ergonomis',
                            'category_key' => 'Input Device',
                            'unit_key' => 'PCS',
                            'sku' => 'MOUSE-WIRELESS',
                            'barcode' => '8997778880003',
                            'purchase_price' => 35000,
                            'selling_price' => 69000,
                            'min_stock' => 8,
                            'image' => 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Mouse tanpa kabel desain ergonomis dengan sensitivitas DPI tinggi.',
                            'qty' => 30
                        ],
                        [
                            'name' => 'Bluetooth Earphone',
                            'category_key' => 'Perangkat Audio',
                            'unit_key' => 'PCS',
                            'sku' => 'EARPHONE-TWS',
                            'barcode' => '8997778880004',
                            'purchase_price' => 90000,
                            'selling_price' => 175000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Earphone TWS suara bass jernih konektivitas bluetooth 5.0.',
                            'qty' => 15
                        ],
                        [
                            'name' => 'Flashdisk USB 3.0 64GB',
                            'category_key' => 'Penyimpanan',
                            'unit_key' => 'PCS',
                            'sku' => 'FLASHDISK-64G',
                            'barcode' => '8997778880005',
                            'purchase_price' => 40000,
                            'selling_price' => 75000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1618424181497-157f25b6ddd5?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Penyimpanan portabel 64GB transfer data super cepat USB 3.0.',
                            'qty' => 40
                        ]
                    ]
                ];

            case 'automotive':
            case 'hardware':
            case 'furniture':
                return [
                    'categories' => ['Pelumas', 'Suku Cadang', 'Perkakas', 'Bahan Bangunan', 'Alat Kerja'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'LITER' => ['name' => 'Liter', 'code' => 'LTR'],
                    ],
                    'products' => [
                        [
                            'name' => 'Oli Motor 4T 1 Liter',
                            'category_key' => 'Pelumas',
                            'unit_key' => 'LITER',
                            'sku' => 'OLI-MOTOR-1L',
                            'barcode' => '8999990001112',
                            'purchase_price' => 38000,
                            'selling_price' => 50000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1619642751034-765dfdf7c58e?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Oli pelumas mesin motor 4 tak tangguh menjaga performa.',
                            'qty' => 30
                        ],
                        [
                            'name' => 'Kampas Rem Depan Motor',
                            'category_key' => 'Suku Cadang',
                            'unit_key' => 'PCS',
                            'sku' => 'SPARE-BRAKEPAD',
                            'barcode' => '8999990002223',
                            'purchase_price' => 18000,
                            'selling_price' => 32000,
                            'min_stock' => 8,
                            'image' => 'https://images.unsplash.com/photo-1486006920555-c77dce18193b?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Diskbrakepad kampas rem depan motor presisi pakem.',
                            'qty' => 25
                        ],
                        [
                            'name' => 'Kunci Pas Set 8-24mm',
                            'category_key' => 'Perkakas',
                            'unit_key' => 'PCS',
                            'sku' => 'TOOL-WRENCHSET',
                            'barcode' => '8999990003334',
                            'purchase_price' => 85000,
                            'selling_price' => 145000,
                            'min_stock' => 3,
                            'image' => 'https://images.unsplash.com/photo-1534224039826-c7a0eda0e6b3?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Set kunci pas ring baja vanadium ukuran lengkap 8mm sampai 24mm.',
                            'qty' => 10
                        ],
                        [
                            'name' => 'Cat Tembok Putih 5kg',
                            'category_key' => 'Bahan Bangunan',
                            'unit_key' => 'PCS',
                            'sku' => 'BUILD-PAINT-5K',
                            'barcode' => '8999990004445',
                            'purchase_price' => 70000,
                            'selling_price' => 95000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Cat tembok interior warna putih cemerlang daya tutup luas.',
                            'qty' => 15
                        ],
                        [
                            'name' => 'Kuas Cat Tembok 3 Inch',
                            'category_key' => 'Alat Kerja',
                            'unit_key' => 'PCS',
                            'sku' => 'TOOL-BRUSH-3I',
                            'barcode' => '8999990005556',
                            'purchase_price' => 5000,
                            'selling_price' => 9500,
                            'min_stock' => 15,
                            'image' => 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kuas lukis cat tembok ukuran 3 inci berbulu tebal.',
                            'qty' => 50
                        ]
                    ]
                ];

            case 'pharmacy':
            case 'health':
                return [
                    'categories' => ['Obat Bebas', 'Vitamin & Suplemen', 'Alat Kesehatan', 'Pembersih Tangan', 'Minyak Gosok'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'KOTAK' => ['name' => 'Kotak', 'code' => 'KTK'],
                        'BOTOL' => ['name' => 'Botol', 'code' => 'BTL'],
                    ],
                    'products' => [
                        [
                            'name' => 'Paracetamol 500mg Strip',
                            'category_key' => 'Obat Bebas',
                            'unit_key' => 'PCS',
                            'sku' => 'MED-PARACETAMOL',
                            'barcode' => '8994441110001',
                            'purchase_price' => 2000,
                            'selling_price' => 4500,
                            'min_stock' => 20,
                            'image' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Membantu menurunkan demam, meringankan sakit kepala.',
                            'qty' => 100
                        ],
                        [
                            'name' => 'Vitamin C 1000mg Strip',
                            'category_key' => 'Vitamin & Suplemen',
                            'unit_key' => 'PCS',
                            'sku' => 'MED-VITC1000',
                            'barcode' => '8994441110002',
                            'purchase_price' => 3500,
                            'selling_price' => 6000,
                            'min_stock' => 15,
                            'image' => 'https://images.unsplash.com/photo-1616679911721-eff6eec18fcd?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Suplemen vitamin C 1000mg menjaga daya tahan tubuh.',
                            'qty' => 80
                        ],
                        [
                            'name' => 'Masker Medis 3-Ply',
                            'category_key' => 'Alat Kesehatan',
                            'unit_key' => 'KOTAK',
                            'sku' => 'MED-MASK-3PLY',
                            'barcode' => '8994441110003',
                            'purchase_price' => 15000,
                            'selling_price' => 25000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Masker bedah medis isi 50pcs efisiensi filtrasi tinggi.',
                            'qty' => 30
                        ],
                        [
                            'name' => 'Hand Sanitizer Gel 100ml',
                            'category_key' => 'Pembersih Tangan',
                            'unit_key' => 'BOTOL',
                            'sku' => 'MED-SANITIZER',
                            'barcode' => '8994441110004',
                            'purchase_price' => 7500,
                            'selling_price' => 12500,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1584483777113-47004b685ea3?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Gel pembersih tangan antiseptik wangi aloe vera.',
                            'qty' => 50
                        ],
                        [
                            'name' => 'Minyak Kayu Putih 120ml',
                            'category_key' => 'Minyak Gosok',
                            'unit_key' => 'BOTOL',
                            'sku' => 'MED-KAYUPUTIH',
                            'barcode' => '8994441110005',
                            'purchase_price' => 32000,
                            'selling_price' => 39500,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Minyak kayu putih murni meredakan masuk angin.',
                            'qty' => 20
                        ]
                    ]
                ];

            case 'pet_shop':
                return [
                    'categories' => ['Makanan Kucing', 'Makanan Anjing', 'Pasir Kucing', 'Aksesoris Hewan', 'Shampo Hewan'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'PACK' => ['name' => 'Pack', 'code' => 'PACK'],
                    ],
                    'products' => [
                        [
                            'name' => 'Makanan Kucing Kering 1kg',
                            'category_key' => 'Makanan Kucing',
                            'unit_key' => 'PACK',
                            'sku' => 'PET-CATDRY-1K',
                            'barcode' => '8993339990001',
                            'purchase_price' => 22000,
                            'selling_price' => 35000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Makanan kering kucing dewasa nutrisi lengkap rasa tuna.',
                            'qty' => 30
                        ],
                        [
                            'name' => 'Makanan Kucing Basah Kaleng',
                            'category_key' => 'Makanan Kucing',
                            'unit_key' => 'PCS',
                            'sku' => 'PET-CATWET-CAN',
                            'barcode' => '8993339990002',
                            'purchase_price' => 9500,
                            'selling_price' => 15000,
                            'min_stock' => 15,
                            'image' => 'https://images.unsplash.com/photo-1569591159212-b02ea8a9f239?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Makanan basah kaleng premium tinggi protein rasa salmon.',
                            'qty' => 50
                        ],
                        [
                            'name' => 'Pasir Kucing Wangi 5L',
                            'category_key' => 'Pasir Kucing',
                            'unit_key' => 'PACK',
                            'sku' => 'PET-CATLITTER-5L',
                            'barcode' => '8993339990003',
                            'purchase_price' => 22000,
                            'selling_price' => 35000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1597843798180-fc394d2f0eb3?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Pasir bentonite gumpal wangi lavender menyerap bau.',
                            'qty' => 20
                        ],
                        [
                            'name' => 'Kalung Leher Anti-Kutu',
                            'category_key' => 'Aksesoris Hewan',
                            'unit_key' => 'PCS',
                            'sku' => 'PET-COLLAR-FLEA',
                            'barcode' => '8993339990004',
                            'purchase_price' => 8000,
                            'selling_price' => 18000,
                            'min_stock' => 8,
                            'image' => 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kalung leher pengusir kutu hewan wangi aromaterapi.',
                            'qty' => 25
                        ],
                        [
                            'name' => 'Shampo Hewan Anti-Kutu 250ml',
                            'category_key' => 'Shampo Hewan',
                            'unit_key' => 'PCS',
                            'sku' => 'PET-SHAMPOO-ANTI',
                            'barcode' => '8993339990005',
                            'purchase_price' => 18000,
                            'selling_price' => 30000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Sabun shampo pembersih bulu anjing kucing wangi segar.',
                            'qty' => 15
                        ]
                    ]
                ];

            case 'book_store':
                return [
                    'categories' => ['Alat Tulis', 'Buku Tulis', 'Kertas HVS', 'Alat Perekat', 'Penyimpanan Dokumen'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'PAX' => ['name' => 'Pax', 'code' => 'PAX'],
                        'RIM' => ['name' => 'Rim', 'code' => 'RIM'],
                    ],
                    'products' => [
                        [
                            'name' => 'Pulpen Gel Hitam 0.5mm',
                            'category_key' => 'Alat Tulis',
                            'unit_key' => 'PCS',
                            'sku' => 'ATK-PULPEN-GEL',
                            'barcode' => '8995432109876',
                            'purchase_price' => 2500,
                            'selling_price' => 4000,
                            'min_stock' => 20,
                            'image' => 'https://images.unsplash.com/photo-1583485088034-697b5bc54ccd?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Pulpen tinta gel hitam pekat mata pena ultra halus.',
                            'qty' => 100
                        ],
                        [
                            'name' => 'Buku Tulis Kotak Set',
                            'category_key' => 'Buku Tulis',
                            'unit_key' => 'PAX',
                            'sku' => 'ATK-BUKU-KOTAK',
                            'barcode' => '8995432109877',
                            'purchase_price' => 22000,
                            'selling_price' => 32000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Set buku tulis isi 10 buku kertas tebal putih.',
                            'qty' => 20
                        ],
                        [
                            'name' => 'Kertas HVS A4 80gr',
                            'category_key' => 'Kertas HVS',
                            'unit_key' => 'RIM',
                            'sku' => 'ATK-HVS-A4-80',
                            'barcode' => '8995432109878',
                            'purchase_price' => 40000,
                            'selling_price' => 49500,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1618424181497-157f25b6ddd5?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Kertas putih HVS ukuran A4 ketebalan 80 gram isi 500 lembar.',
                            'qty' => 15
                        ],
                        [
                            'name' => 'Pensil Kayu 2B',
                            'category_key' => 'Alat Tulis',
                            'unit_key' => 'PCS',
                            'sku' => 'ATK-PENCIL-2B',
                            'barcode' => '8995432109879',
                            'purchase_price' => 1500,
                            'selling_price' => 3000,
                            'min_stock' => 25,
                            'image' => 'https://images.unsplash.com/photo-1513542789411-b6a5d4f31634?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Pensil grafit hitam standar ujian 2B mudah dihapus.',
                            'qty' => 120
                        ],
                        [
                            'name' => 'Binder Clip Hitam 1 Box',
                            'category_key' => 'Penyimpanan Dokumen',
                            'unit_key' => 'PCS',
                            'sku' => 'ATK-BINDERCLIP',
                            'barcode' => '8995432109880',
                            'purchase_price' => 8000,
                            'selling_price' => 15000,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Klip penjepit kertas binder besi hitam kuat ukuran sedang.',
                            'qty' => 30
                        ]
                    ]
                ];

            default:
                // Retail / Sembako / Grocery / Fallback
                return [
                    'categories' => ['Sembako', 'Makanan Ringan', 'Minuman', 'Kebersihan', 'Kebutuhan Rumah'],
                    'units' => [
                        'PCS' => ['name' => 'Pcs', 'code' => 'PCS'],
                        'BKS' => ['name' => 'Bungkus', 'code' => 'BKS'],
                    ],
                    'products' => [
                        [
                            'name' => 'Aqua Botol 600ml',
                            'category_key' => 'Minuman',
                            'unit_key' => 'PCS',
                            'sku' => 'AQUA-600ML',
                            'barcode' => '8992345678901',
                            'purchase_price' => 2000,
                            'selling_price' => 3500,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1608885898957-a599fb1b467e?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Air mineral berkualitas tinggi dalam kemasan botol 600ml.',
                            'qty' => 50
                        ],
                        [
                            'name' => 'Indomie Goreng Spesial',
                            'category_key' => 'Makanan Ringan',
                            'unit_key' => 'BKS',
                            'sku' => 'INDOMIE-GORENG',
                            'barcode' => '8998866200029',
                            'purchase_price' => 2800,
                            'selling_price' => 3500,
                            'min_stock' => 10,
                            'image' => 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Mie instan goreng rasa spesial dari Indomie.',
                            'qty' => 100
                        ],
                        [
                            'name' => 'Beras Sentra Ramos 5kg',
                            'category_key' => 'Sembako',
                            'unit_key' => 'PCS',
                            'sku' => 'BERAS-RAMOS-5KG',
                            'barcode' => '8993123456782',
                            'purchase_price' => 65000,
                            'selling_price' => 75000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Beras putih pulen premium kemasan 5kg.',
                            'qty' => 20
                        ],
                        [
                            'name' => 'Minyak Goreng Bimoli 2L',
                            'category_key' => 'Sembako',
                            'unit_key' => 'PCS',
                            'sku' => 'MINYAK-BIMOLI-2L',
                            'barcode' => '8994567890123',
                            'purchase_price' => 32000,
                            'selling_price' => 36000,
                            'min_stock' => 5,
                            'image' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Minyak goreng kelapa sawit premium kemasan 2 liter.',
                            'qty' => 25
                        ],
                        [
                            'name' => 'Sabun Cuci Piring Mama Lemon',
                            'category_key' => 'Kebersihan',
                            'unit_key' => 'PCS',
                            'sku' => 'MAMA-LEMON',
                            'barcode' => '8991234567894',
                            'purchase_price' => 4500,
                            'selling_price' => 6000,
                            'min_stock' => 8,
                            'image' => 'https://images.unsplash.com/photo-1607613009820-a29f7bb81c04?w=500&auto=format&fit=crop&q=60',
                            'description' => 'Cairan pencuci piring dengan ekstrak jeruk nipis.',
                            'qty' => 30
                        ]
                    ]
                ];
        }
    }
}
