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

        try {
            $resData = $data['Data'] ?? [];
            \Illuminate\Support\Facades\DB::table('ipaymu_data')->insert([
                'sessionId'     => $resData['SessionId'] ?? null,
                'TransactionId' => $resData['TransactionId'] ?? null,
                'Fee'           => $resData['Fee'] ?? 0,
                'Expired'       => $resData['Expired'] ?? null,
                'PaymentNo'     => $resData['PaymentNo'] ?? null,
                'PaymentName'   => $resData['PaymentName'] ?? null,
                'Total'         => $resData['Total'] ?? 0,
                'Via'           => $resData['Via'] ?? null,
                'Channel'       => $resData['Channel'] ?? null,
                'nama'          => $params['buyer_name'] ?? null,
                'tenants_id'    => $params['tenant_id'] ?? null,
                'email'         => $params['buyer_email'] ?? null,
                'phone'         => $params['buyer_phone'] ?? null,
                'jumlah'        => $params['amount'] ?? 0,
                'nominal'       => $params['amount'] ?? 0,
                'status'        => 'BARU',
                'created_date'  => now(),
            ]);
        } catch (\Exception $e) {
            Log::channel('ipaymu')->error('Gagal menyimpan log ipaymu_data: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Verifikasi signature webhook dari iPaymu.
     */
    public function verifySignature(\Illuminate\Http\Request $request): bool
    {
        if (empty($this->va)) {
            Log::channel('ipaymu')->error('verifySignature failed: iPaymu VA is not configured.', [
                'va' => $this->va,
            ]);
            return false;
        }

        // 1. Ambil Data Masuk (Mendukung JSON atau Form-Data sesuai setting dashboard/dokumentasi)
        $input = $request->getContent();
        $data  = json_decode($input, true);
        if (!is_array($data)) {
            $data = $request->post() ?: [];
        }

        // 2. Pisahkan signature yang diterima (iPaymu callbacks send signature in x-signature header)
        $receivedSig = $data['signature'] 
            ?? $request->header('x-signature') 
            ?? $request->header('signature') 
            ?? $request->input('signature') 
            ?? '';

        if (empty($receivedSig)) {
            Log::channel('ipaymu')->warning('verifySignature failed: No signature found in payload or header', [
                'headers' => $request->headers->all(),
                'payload' => $data,
            ]);
            return false;
        }

        // Hapus parameter signature dari data yang diterima
        unset($data['signature']);

        // 3. Urutkan data berdasarkan kunci (key) secara ascending (ksort)
        ksort($data);

        // 4. Konversi data yang sudah diurutkan menjadi string JSON
        $jsonBody = json_encode($data);

        // 5. Generate hash HMAC-SHA256 menggunakan string JSON tersebut dan Secret Key (VA) Anda
        $calculatedSig = hash_hmac('sha256', $jsonBody, $this->va);

        // 6. Bandingkan
        $isMatched = hash_equals(strtolower($calculatedSig), strtolower($receivedSig));

        if (!$isMatched) {
            Log::channel('ipaymu')->warning('verifySignature failed: Signature mismatch', [
                'received_signature'   => $receivedSig,
                'calculated_signature' => $calculatedSig,
                'va_secret'            => $this->va,
                'json_body'            => $jsonBody,
                'payload_after_unset'  => $data,
                'headers'              => $request->headers->all(),
            ]);
        }

        return $isMatched;
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
