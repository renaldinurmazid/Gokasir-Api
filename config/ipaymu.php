<?php

return [
    'sandbox' => env('IPAYMU_SANDBOX', true),
    
    // Production
    'api_key' => env('IPAYMU_API_KEY', ''),
    'va'      => env('IPAYMU_VA', ''),
    
    // Sandbox
    'sandbox_api_key' => env('IPAYMU_SANDBOX_API_KEY', ''),
    'sandbox_va'      => env('IPAYMU_SANDBOX_VA', ''),
];
