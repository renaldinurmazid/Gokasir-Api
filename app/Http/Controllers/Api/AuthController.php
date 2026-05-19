<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseApiController
{
    // POST /api/auth/register
    public function register(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:150',
            'business_type' => 'nullable|string|max:100',
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|min:6|confirmed',
            'phone'         => 'nullable|string|max:30',
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
                'name'      => $request->store_name ?? ('Toko ' . $request->business_name),
            ]);

            // 3. Create Owner User
            $user = User::create([
                'tenant_id'  => $tenant->id,
                'store_id'   => null,
                'role'       => 'owner',
                'name'       => $request->name,
                'email'      => $request->email,
                'phone'      => $request->phone,
                'password'   => Hash::make($request->password),
                'status'     => 1,
                'last_login' => now(),
            ]);

            DB::commit();

            // Create Sanctum Token for immediate login
            $token = $user->createToken('gokasir')->plainTextToken;

            return $this->ok([
                'token'  => $token,
                'user'   => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'role'     => $user->role,
                    'store_id' => $user->store_id,
                ],
                'tenant' => $tenant,
                'store'  => $store,
            ], 'Registrasi berhasil dan login otomatis.', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->fail('Gagal melakukan registrasi: ' . $e->getMessage(), 500);
        }
    }

    // POST /api/auth/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->fail('Email atau password salah.', 401);
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
                'role'     => $user->role,
                'store_id' => $user->store_id,
            ],
        ], 'Login berhasil.');
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
