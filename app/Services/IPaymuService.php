<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IPaymuService
{
    private string $apiKey;
    private string $va;
    private string $baseUrl;
    private bool   $isSandbox;

    public function __construct()
    {
        $this->isSandbox = config('ipaymu.sandbox', true);
        $this->apiKey    = config('ipaymu.api_key', '');
        $this->va        = config('ipaymu.va', '');
        $this->baseUrl   = $this->isSandbox
            ? 'https://sandbox.ipaymu.com/api/v2'
            : 'https://my.ipaymu.com/api/v2';
    }

    /**
     * Buat transaksi pembayaran baru di iPaymu.
     */
    /**
     * Buat transaksi pembayaran baru di iPaymu menggunakan Direct API.
     */
    public function createPayment(array $params): array
    {
        $body = [
            'name'           => $params['buyer_name'],
            'phone'          => $params['buyer_phone'],
            'email'          => $params['buyer_email'],
            'amount'         => $params['amount'],
            'notifyUrl'      => $params['notify_url'],
            'expired'        => '24',
            'expiredType'    => 'hours',
            'referenceId'    => $params['order_number'],
            'paymentMethod'  => $params['payment_method'],
            'paymentChannel' => $params['payment_channel'],
            'product'        => [$params['description']],
            'qty'            => [1],
            'price'          => [$params['amount']],
        ];

        $bodyString = json_encode($body, JSON_UNESCAPED_SLASHES);

        $response = Http::withHeaders($this->buildHeaders($body, $bodyString))
            ->withBody($bodyString, 'application/json')
            ->post($this->baseUrl . '/payment/direct');

        $data = $response->json();

        Log::channel('ipaymu')->info('Create payment response', $data ?: []);

        if (!$response->successful() || ($data['Status'] ?? null) != 200) {
            throw new \RuntimeException(
                'iPaymu error: ' . ($data['Message'] ?? 'Unknown error')
            );
        }

        return $data;
    }

    /**
     * Verifikasi signature webhook dari iPaymu.
     */
    public function verifySignature(\Illuminate\Http\Request $request): bool
    {
        $receivedSig  = $request->header('signature') ?? $request->input('signature');
        if (!$receivedSig) return false;

        $bodyString   = $request->getContent();
        $bodyHash     = strtolower(hash('sha256', $bodyString));
        
        $stringToSign = "POST:" . $this->va . ":" . $bodyHash . ":" . $this->apiKey;
        $expectedSig  = hash_hmac('sha256', $stringToSign, $this->apiKey);

        return hash_equals(strtolower($expectedSig), strtolower($receivedSig));
    }

    /**
     * Get list of available payment channels.
     */
    public function getPaymentChannels(): array
    {
        // For GET requests, iPaymu expects signature built with "{}" as request body and GET as HTTP method
        $headers = $this->buildHeaders([], '{}', 'GET');

        $response = Http::withHeaders($headers)
            ->get($this->baseUrl . '/payment-channels');

        $data = $response->json();

        Log::channel('ipaymu')->info('Get payment channels response', $data ?: []);

        if (!$response->successful() || ($data['Status'] ?? null) != 200) {
            throw new \RuntimeException(
                'iPaymu error: ' . ($data['Message'] ?? 'Unknown error')
            );
        }

        return $data ?: [];
    }

    /**
     * Build Authorization header iPaymu.
     */
    private function buildHeaders(array $body, ?string $bodyString = null, string $method = 'POST'): array
    {
        $bodyString = $bodyString ?? json_encode($body, JSON_UNESCAPED_SLASHES);
        $bodyHash   = strtolower(hash('sha256', $bodyString));
        $timestamp  = now()->format('YmdHis');
        
        $stringToSign = strtoupper($method) . ":" . $this->va . ":" . $bodyHash . ":" . $this->apiKey;
        $signature  = hash_hmac('sha256', $stringToSign, $this->apiKey);

        return [
            'Content-Type' => 'application/json',
            'va'           => $this->va,
            'signature'    => $signature,
            'timestamp'    => $timestamp,
        ];
    }
}
