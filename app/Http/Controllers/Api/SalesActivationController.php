<?php

namespace App\Http\Controllers\Api;

use App\Models\SalesActivationPrice;
use App\Models\TokenPricing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalesActivationController extends BaseApiController
{
    /**
     * Get activation packages and their custom prices for the logged-in sales user.
     */
    public function index()
    {
        // Get all base activation packages
        $packages = TokenPricing::where('type', 'activation')->where('is_active', true)->get();

        // Get custom prices for this sales user
        $customPrices = SalesActivationPrice::where('sales_id', auth()->id())
            ->pluck('custom_price', 'token_pricing_id');

        $result = $packages->map(function ($package) use ($customPrices) {
            return [
                'id'           => $package->id,
                'name'         => $package->name,
                'description'  => $package->description,
                'base_price'   => $package->price,
                'custom_price' => $customPrices[$package->id] ?? $package->price,
                'token_amount' => $package->token_amount,
            ];
        });

        return $this->ok($result);
    }

    /**
     * Set a custom price for a specific activation package for the sales user.
     */
    public function updatePrice(Request $request)
    {
        $request->validate([
            'token_pricing_id' => [
                'required',
                Rule::exists('token_pricing', 'id')->where(function ($query) {
                    $query->where('type', 'activation');
                }),
            ],
            'custom_price' => 'required|numeric|min:0',
        ]);

        $price = SalesActivationPrice::updateOrCreate(
            [
                'sales_id' => auth()->id(),
                'token_pricing_id' => $request->token_pricing_id,
            ],
            [
                'custom_price' => $request->custom_price,
            ]
        );

        return $this->ok($price, 'Harga paket aktivasi berhasil diperbarui.');
    }
}
