<?php

namespace App\Http\Controllers\Api;

use App\Models\TokenPricing;
use Illuminate\Http\Request;

class TokenPricingController extends BaseApiController
{
    // GET /api/token-pricing
    public function index()
    {
        $tenant  = auth()->user()->tenant;
        $pricing = TokenPricing::active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($p) use ($tenant) {
                $effectivePrice = $p->type === 'unit'
                    ? $tenant->getEffectiveTokenPrice($p)
                    : (float) $p->price;  // paket tidak berubah

                return array_merge($p->toArray(), [
                    'total_token'      => $p->total_token,
                    'effective_price'  => $effectivePrice,
                    'is_mitra_price'   => $tenant->hasMitraPrice() && $p->type === 'unit',
                    'price_per_token'  => $p->total_token > 0
                        ? round($effectivePrice / $p->total_token, 2)
                        : 0,
                ]);
            });

        return $this->ok($pricing);
    }

    // POST /api/admin/token-pricing  (owner/admin only)
    public function store(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:unit,package',
            'name'         => 'required|string|max:100',
            'price'        => 'required|numeric|min:1',
            'token_amount' => 'required|integer|min:1',
            'token_bonus'  => 'nullable|integer|min:0',
            'description'  => 'nullable|string',
            'sort_order'   => 'nullable|integer',
        ]);

        $pricing = TokenPricing::create(array_merge(
            $request->only('type', 'name', 'description', 'price', 'token_amount', 'token_bonus', 'sort_order'),
            [
                'token_bonus' => $request->token_bonus ?? 0,
                'is_active'   => true,
                'created_by'  => auth()->id(),
            ]
        ));

        return $this->ok($pricing, 'Harga token ditambahkan.', 201);
    }

    // PUT /api/admin/token-pricing/{id}
    public function update(Request $request, TokenPricing $tokenPricing)
    {
        $request->validate([
            'price'        => 'sometimes|numeric|min:1',
            'token_amount' => 'sometimes|integer|min:1',
            'token_bonus'  => 'sometimes|integer|min:0',
            'is_active'    => 'sometimes|boolean',
        ]);

        $tokenPricing->update($request->only(
            'type', 'name', 'description', 'price',
            'token_amount', 'token_bonus', 'is_active', 'sort_order'
        ));

        return $this->ok($tokenPricing, 'Harga token diperbarui.');
    }

    // DELETE /api/admin/token-pricing/{id}
    public function destroy(TokenPricing $tokenPricing)
    {
        $tokenPricing->update(['is_active' => false]);
        return $this->ok(null, 'Harga token dinonaktifkan.');
    }
}
