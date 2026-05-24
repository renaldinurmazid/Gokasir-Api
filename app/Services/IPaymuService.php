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
    public function createPayment(array $params): array
    {
        $body = [
            'product'     => [$params['description']],
            'qty'         => [1],
            'price'       => [$params['amount']],
            'returnUrl'   => $params['return_url'],
            'notifyUrl'   => $params['notify_url'],
            'cancelUrl'   => $params['cancel_url'],
            'referenceId' => $params['order_number'],
            'buyerName'   => $params['buyer_name'],
            'buyerEmail'  => $params['buyer_email'],
            'buyerPhone'  => $params['buyer_phone'],
        ];

        $bodyString = json_encode($body, JSON_UNESCAPED_SLASHES);

        $response = Http::withHeaders($this->buildHeaders($body, $bodyString))
            ->withBody($bodyString, 'application/json')
            ->post($this->baseUrl . '/payment');

        $data = $response->json();

        Log::channel('ipaymu')->info('Create payment response', $data ?: []);

        if (!$response->successful() || ($data['Status'] ?? null) != 200) {
            throw new \RuntimeException(
                'iPaymu error: ' . ($data['Message'] ?? 'Unknown error')
            );
        }

        return [
            'trx_id'       => $data['Data']['SessionID'] ?? $data['Data']['TransactionId'] ?? null,
            'reference_id' => $params['order_number'],
            'url'          => $data['Data']['Url'] ?? null,
        ];
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
     * Build Authorization header iPaymu.
     */
    private function buildHeaders(array $body, ?string $bodyString = null): array
    {
        $bodyString = $bodyString ?? json_encode($body, JSON_UNESCAPED_SLASHES);
        $bodyHash   = strtolower(hash('sha256', $bodyString));
        $timestamp  = now()->format('YmdHis');
        
        $stringToSign = "POST:" . $this->va . ":" . $bodyHash . ":" . $this->apiKey;
        $signature  = hash_hmac('sha256', $stringToSign, $this->apiKey);

        return [
            'Content-Type' => 'application/json',
            'va'           => $this->va,
            'signature'    => $signature,
            'timestamp'    => $timestamp,
        ];
    }
}
