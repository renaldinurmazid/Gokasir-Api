<?php

namespace App\Http\Controllers\Api;

use App\Models\TokenPricing;
use Illuminate\Http\Request;

class TokenPricingController extends BaseApiController
{
    // GET /api/token-pricing
    public function index()
    {
        $tenant = auth()->user()->tenant;
        $user   = auth()->user();
        
        $query = TokenPricing::active()->orderBy('sort_order');

        if (!$tenant->is_activated) {
            $query->where('type', 'activation');
        } else {
            $query->whereIn('type', ['unit', 'package']);
        }

        // Get custom prices if referred by sales
        $customPrices = [];
        if (!$tenant->is_activated && $user->referred_by_id) {
            $referrer = \App\Models\User::find($user->referred_by_id);
            if ($referrer && $referrer->role === 'sales') {
                $customPrices = \App\Models\SalesActivationPrice::where('sales_id', $referrer->id)
                    ->pluck('custom_price', 'token_pricing_id');
            }
        }

        $pricing = $query->get()->map(function ($p) use ($tenant, $customPrices) {
            $effectivePrice = (float) $p->price;
            
            if ($p->type === 'unit') {
                $effectivePrice = $tenant->getEffectiveTokenPrice($p);
            } elseif ($p->type === 'activation' && isset($customPrices[$p->id])) {
                $effectivePrice = (float) $customPrices[$p->id];
            }

            return array_merge($p->toArray(), [
                'total_token'      => $p->token_amount, // fallback to token_amount if total_token not defined in schema
                'effective_price'  => $effectivePrice,
                'is_mitra_price'   => $tenant->hasMitraPrice() && $p->type === 'unit',
                'price_per_token'  => $p->token_amount > 0
                    ? round($effectivePrice / $p->token_amount, 2)
                    : 0,
            ]);
        });

        return $this->ok($pricing);
    }

    // GET /api/public/activation-packages
    public function publicPackages(Request $request)
    {
        $referralCode = $request->query('referral_code');
        
        $query = TokenPricing::active()->where('type', 'activation')->orderBy('sort_order');
        $customPrices = [];

        if ($referralCode) {
            $referrer = \App\Models\User::where('referral_code', $referralCode)->first();
            if ($referrer && $referrer->role === 'sales') {
                $customPrices = \App\Models\SalesActivationPrice::where('sales_id', $referrer->id)
                    ->pluck('custom_price', 'token_pricing_id');
            }
        }

        $pricing = $query->get()->map(function ($p) use ($customPrices) {
            $effectivePrice = (float) $p->price;
            
            if (isset($customPrices[$p->id])) {
                $effectivePrice = (float) $customPrices[$p->id];
            }

            return array_merge($p->toArray(), [
                'total_token'      => $p->token_amount,
                'effective_price'  => $effectivePrice,
                'price_per_token'  => $p->token_amount > 0
                    ? round($effectivePrice / $p->token_amount, 2)
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
