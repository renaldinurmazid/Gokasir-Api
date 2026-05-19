<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseApiController
{
    // GET /api/users
    public function index(Request $request)
    {
        $users = User::where('tenant_id', $this->tenantId())
            ->when($request->store_id, fn($q) => $q->where('store_id', $request->store_id))
            ->when($request->role, fn($q) => $q->where('role', $request->role))
            ->with('store')
            ->paginate(20);

        return $this->ok($users);
    }

    // POST /api/users
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|in:owner,cashier',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $user = User::create([
            'tenant_id' => $this->tenantId(),
            'store_id'  => $request->store_id,
            'role'      => $request->role,
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
        ]);

        return $this->ok($user, 'User berhasil dibuat.', 201);
    }

    // PUT /api/users/{user}
    public function update(Request $request, User $user)
    {
        abort_if($user->tenant_id !== $this->tenantId(), 403);

        $request->validate([
            'name'     => 'sometimes|string|max:100',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'role'     => 'sometimes|in:owner,cashier',
            'store_id' => 'nullable|exists:stores,id',
            'status'   => 'sometimes|in:0,1',
        ]);

        $data = $request->only('name','email','phone','role','store_id','status');
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return $this->ok($user, 'User diperbarui.');
    }

    // DELETE /api/users/{user}
    public function destroy(User $user)
    {
        abort_if($user->tenant_id !== $this->tenantId(), 403);
        abort_if($user->id === auth()->id(), 400, 'Tidak bisa hapus akun sendiri.');
        $user->delete();
        return $this->ok(null, 'User dihapus.');
    }
}
