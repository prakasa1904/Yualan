<?php

namespace App\Services;

use App\Models\SaasSetting;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request; // Import Request for handleNotification

class IpaymuService
{
    protected $apiKey; // Ini adalah VA Number
    protected $secretKey; // Ini adalah Secret Key / API Key
    protected $baseUrl;
    protected $mode; // sandbox or production

    public function __construct(Tenant $tenant)
    {
        $this->mode = $tenant->ipaymu_mode ?: 'production'; // Default ke production

        // Coba dapatkan kredensial dari tenant dulu
        $this->apiKey = $tenant->ipaymu_api_key;
        $this->secretKey = $tenant->ipaymu_secret_key;

        $ambilDariSaas = false;
        // Jika tidak ada di tenant, ambil dari SaasSetting sebagai fallback
        if (empty($this->apiKey)) {
            $this->apiKey = SaasSetting::where('key', 'ipaymu_va')->value('value');
            $ambilDariSaas = true;
        }
        if (empty($this->secretKey)) {
            $this->secretKey = SaasSetting::where('key', 'ipaymu_api_key')->value('value');
            $ambilDariSaas = true;
        }

        if ($ambilDariSaas) {
            // Ambil baseUrl dari env jika ambil dari SaasSetting
            $this->baseUrl = env('IPAYMU_URL', 'https://my.ipaymu.com/api/v2');
        } else {
            $this->baseUrl = ($this->mode === 'production')
                ? 'https://my.ipaymu.com/api/v2'
                : 'https://sandbox.ipaymu.com/api/v2';
        }

        if (empty($this->apiKey) || empty($this->secretKey)) {
            throw new \Exception("iPaymu API Key atau Secret Key tidak dikonfigurasi untuk tenant: {$tenant->name} ({$tenant->slug})");
        }
    }

    /**
     * Generate signature for iPaymu API requests.
     */
    protected function generateSignature(string $method, string $endpoint, array $body): string
    {
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $bodyHash = strtolower(hash('sha256', $jsonBody));

        // KOREKSI: stringToSign seharusnya METHOD:VA_NUMBER:BODY_HASH:API_KEY
        // API_KEY di sini adalah Secret Key iPaymu Anda
        $stringToSign = strtoupper($method) . ':' . $this->apiKey . ':' . $bodyHash . ':' . $this->secretKey;
        $signature = hash_hmac('sha256', $stringToSign, $this->secretKey);

        // Tambahkan logging untuk debugging signature
        Log::info('iPaymu Signature Debug', [
            'method' => $method,
            'endpoint' => $endpoint,
            'body' => $body,
            'jsonBody' => $jsonBody,
            'bodyHash' => $bodyHash,
            'stringToSign' => $stringToSign, // Ini adalah stringToSign yang sudah dikoreksi
            'generatedSignature' => $signature,
            'vaUsed' => $this->apiKey,
            'secretKeyUsed' => $this->secretKey,
        ]);

        return $signature;
    }

    /**
     * Call iPaymu API.
     */
    protected function callApi(string $method, string $endpoint, array $body = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $timestamp = date('YmdHis'); // Generate timestamp in YYYYMMDDHHIISS format
        $signature = $this->generateSignature($method, $endpoint, $body);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'signature' => $signature,
            'va' => $this->apiKey, // 'va' header adalah VA Number Anda
            'timestamp' => $timestamp, // Tambahkan header timestamp
        ];

        Log::info("iPaymu API Call: {$method} {$url}", ['body' => $body, 'headers' => $headers]);

        try {
            $response = Http::withHeaders($headers)
                            ->{$method}($url, $body);

            $response->throw(); // Melemparkan pengecualian jika terjadi kesalahan klien atau server

            $data = $response->json();
            Log::info("iPaymu API Response: {$url}", ['response' => $data]);
            return $data;
        } catch (\Exception $e) {
            Log::error("iPaymu API Error: {$e->getMessage()}", [
                'url' => $url,
                'body' => $body,
                'response_body' => $response->body() ?? 'N/A',
                'exception' => $e,
            ]);
            throw new \Exception("Panggilan API iPaymu gagal: " . $e->getMessage());
        }
    }

    /**
     * Initiate a payment.
     *
     * @param array $items Array of product items: ['name' => 'Product A', 'price' => 10000, 'qty' => 1]
     * @param string $referenceId Your internal transaction ID (e.g., Sale ID)
     * @param string $customerName
     * @param string $customerEmail
     * @param string $customerPhone
     * @param string $returnUrl URL to redirect after successful payment
     * @param string $cancelUrl URL to redirect if payment is cancelled
     * @param string $notifyUrl URL for iPaymu to send payment notifications (IPN)
     * @param string|null $pickupArea Optional pickup area (if null, will be omitted)
     * @param string|null $pickupAddress Optional pickup address (if null, will be omitted)
     */
    public function initiatePayment(
        array $items,
        string $referenceId,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $returnUrl,
        string $cancelUrl,
        string $notifyUrl,
        string $pickupArea = null,
        string $pickupAddress = null
    ): array {
        $productNames = array_column($items, 'name');
        $qtys = array_column($items, 'qty');
        $prices = array_column($items, 'price');

        $body = [
            'product' => $productNames,
            'qty' => $qtys,
            'price' => $prices,
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
            'notifyUrl' => $notifyUrl,
            'referenceId' => $referenceId,
            'buyerName' => $customerName,
            'buyerEmail' => $customerEmail,
            'buyerPhone' => $customerPhone,
            'comments' => 'Pembayaran pesanan POS',
        ];

        // Only add pickup fields if they are provided and not empty
        if (!empty($pickupArea)) {
            $body['pickupArea'] = $pickupArea;
        }
        if (!empty($pickupAddress)) {
            $body['pickupAddress'] = $pickupAddress;
        }

        return $this->callApi('post', '/payment', $body);
    }

    /**
     * Create a subscription payment.
     *
     * @param Tenant $tenant
     * @param \App\Models\PricingPlan $plan
     * @param array $product
     * @param array $qty
     * @param array $price
     * @param array $description
     * @return array
     * @throws \Exception
     */
    public function createSubscriptionPayment(Tenant $tenant, \App\Models\PricingPlan $plan, array $product, array $qty, array $price, array $description): array
    {
        $body = [
            'name' => $plan->plan_name,
            'product' => $product,
            'qty' => $qty,
            'price' => $price,
            'description' => $description,
                        'returnUrl' => route('subscription.success'), // Redirect on success
            'notifyUrl' => route('subscription.notify'),
            'cancelUrl' => route('subscription.payment'),
                        'referenceId' => 'SUB-' . $tenant->id . '-' . $plan->id . '-' . time(), // Include plan_id
            'buyerName' => $tenant->owner->name ?? $tenant->name,
            'buyerEmail' => $tenant->owner->email,
            'buyerPhone' => $tenant->phone, 
        ];

        return $this->callApi('post', '/payment', $body); // Menggunakan endpoint yang sama dengan initiatePayment untuk saat ini
    }

    /**
     * Check transaction status.
     */
    public function checkTransaction(string $transactionId): array
    {
        $body = [
            'transactionId' => $transactionId,
        ];
        return $this->callApi('post', '/transaction', $body);
    }

    /**
     * Initiate a refund for a completed transaction.
     *
     * @param string $transactionId The iPaymu transaction ID to refund.
     * @param float $amount The amount to refund.
     * @return array The API response from iPaymu.
     * @throws \Exception If the API call fails.
     */
    public function refundTransaction(string $transactionId, float $amount): array
    {
        $body = [
            'transactionId' => $transactionId,
            'amount' => $amount,
        ];
        return $this->callApi('post', '/refund', $body); // Assuming /refund is the endpoint
    }

    /**
     * Handle iPaymu notification (IPN).
     * This method will parse the incoming notification data.
     */
    public function handleNotification(Request $request): array
    {
        // iPaymu mengirim data sebagai form-urlencoded, bukan JSON
        $data = $request->all();
        Log::info('Notifikasi iPaymu Diterima', $data);

        // Verifikasi tanda tangan (opsional tapi direkomendasikan untuk keamanan)
        // Verifikasi tanda tangan notifikasi iPaymu bisa kompleks.
        // Untuk kesederhanaan, kita akan mengandalkan pemeriksaan status transaksi via API nanti.
        // Jika Anda membutuhkan verifikasi IPN yang ketat, Anda perlu mengimplementasikannya berdasarkan dokumentasi iPaymu.

        return $data;
    }
}
