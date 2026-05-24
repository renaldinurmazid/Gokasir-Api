<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendMessageWhatsAppJobs;
use Illuminate\Support\Facades\Http;


class AuthController extends BaseApiController
{
    // POST /api/auth/register
    public function register(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:150',
            'business_type' => 'nullable|string|max:100',
            'name'          => 'required|string|max:100',
            'email'         => 'nullable|email',
            'password'      => 'required|min:6|confirmed',
            'phone'         => 'required|string|max:30|unique:users,phone',
            'store_name'    => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'business_name'     => $request->business_name,
                'business_type'     => $request->business_type,
                'email'             => $request->email,
                'phone'             => $request->phone,
                'subscription_plan' => 'free',
                'status'            => 'active',
                'expired_at'        => now()->addMonths(1),
            ]);

            // 2. Create Default Store Branch
            $store = Store::create([
                'tenant_id' => $tenant->id,
                'name'      => $request->store_name ?? ($request->business_name),
            ]);

            // 3. Generate OTP
            $otp = (string) rand(100000, 999999);

            // 4. Create Owner User
            $user = User::create([
                'tenant_id'      => $tenant->id,
                'store_id'       => null,
                'role'           => 'owner',
                'name'           => $request->name,
                'email'          => $request->email,
                'phone'          => $request->phone,
                'password'       => Hash::make($request->password),
                'otp_code'       => $otp,
                'otp_expires_at' => now()->addMinutes(5),
                'status'         => 0, // Unverified
                'last_login'     => null,
            ]);

            DB::commit();

            // Dispatch WhatsApp Job
            dispatch(new SendMessageWhatsAppJobs("Kode verifikasi GoKasir Anda adalah: {$otp}. Berlaku selama 5 menit.", $user->phone));

            // Send Telegram Notification
            try {
                $text = "📢 *Pendaftaran Baru GoKasir*\n\n"
                    . "🏢 *Nama Bisnis:* " . $tenant->business_name . "\n"
                    . "💼 *Tipe Bisnis:* " . ($tenant->business_type ?? '-') . "\n"
                    . "👤 *Nama Owner:* " . $user->name . "\n"
                    . "📞 *No. HP:* " . $user->phone . "\n"
                    . "✉️ *Email:* " . ($user->email ?? '-') . "\n"
                    . "🏪 *Nama Toko:* " . ($store->name ?? '-') . "\n"
                    . "⏰ *Waktu:* " . now()->format('Y-m-d H:i:s');

                Http::post("https://api.telegram.org/bot7219922547:AAEIoouX8l9ANKh-Rw54OHoZY05qxQLQlYY/sendMessage", [
                    'chat_id' => '-4698105870',
                    'text'    => $text,
                    'parse_mode' => 'Markdown',
                ]);
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

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->fail('Nomor telepon atau password salah.', 401);
        }

        if ($user->status == 0) {
            return $this->fail('Akun belum diverifikasi. Silakan verifikasi kode OTP Anda.', 403);
        }

        if ($user->status != 1) {
            return $this->fail('Akun tidak aktif.', 403);
        }

        $user->update(['last_login' => now()]);
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
}
 