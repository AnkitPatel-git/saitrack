<?php

namespace App\Services;

use App\Models\DeliveryLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DelhiveryLtlService implements DeliveryServiceInterface
{
    private $baseUrl = 'https://ltl-clients-api-dev.delhivery.com';
    private $baseUrlProd = 'https://ltl-clients-api.delhivery.com';

    private $apiKey = 'YOUR_GLOBAL_API_KEY'; // From Delhivery team
    private $username = 'LDSOLUTIONDCB2BRC-B2B';
    private $password = 'Ldsolution@123';

    private $jwtToken = null;

    /**
     * Log API call
     */
    private function logApiCall(array $data): void
    {
        try {
            DeliveryLog::create([
                'booking_id'             => $data['booking_id'] ?? null,
                'delivery_provider'      => 'delhivery_ltl',
                'api_endpoint'           => $data['api_endpoint'] ?? null,
                'request_payload'        => $data['request_payload'] ?? [],
                'response_data'          => $data['response_data'] ?? [],
                'status_code'            => $data['status_code'] ?? null,
                'is_success'             => $data['is_success'] ?? false,
                'awb_number'             => $data['awb_number'] ?? null,
                'error_message'          => $data['error_message'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('Delhivery LTL Log Error: ' . $e->getMessage());
        }
    }

    /**
     * Authenticate and cache JWT
     */
    private function authenticate(int $test = 1): string
    {
        if ($this->jwtToken) return $this->jwtToken;

        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = '/ums/login';

        $payload = [
            'username' => $this->username,
            'password' => $this->password,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->post($baseUrl . $endpoint, $payload);

        $data = $response->json();

        $this->logApiCall([
            'api_endpoint'    => $endpoint,
            'request_payload' => $payload,
            'response_data'   => $data,
            'status_code'     => $response->status(),
            'is_success'      => $response->successful(),
            'error_message'   => $data['message'] ?? null,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Delhivery LTL auth failed: ' . json_encode($data));
        }

        $this->jwtToken = $data['access_token'] ?? $data['token'] ?? null;
        return $this->jwtToken;
    }

    /**
     * Create Manifest (Equivalent to Waybill)
     */
    public function createManifest(array $payload, $bookingId = null, int $test = 1): array
    {
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = '/manifest';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
            ])->asMultipart()
              ->attach('doc_file', fopen($payload['doc_file'], 'r'), basename($payload['doc_file']))
              ->post($baseUrl . $endpoint, collect($payload)->except('doc_file')->toArray());

            $data = $response->json();

            $isSuccess = isset($data['manifest_number']) || isset($data['manifest_id']);

            $this->logApiCall([
                'booking_id'      => $bookingId,
                'api_endpoint'    => $endpoint,
                'request_payload' => $payload,
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => $isSuccess,
                'awb_number'      => $data['manifest_number'] ?? null,
                'error_message'   => $isSuccess ? null : ($data['message'] ?? 'Manifest creation failed'),
            ]);

            return [
                'success' => $isSuccess,
                'awb_number' => $data['manifest_number'] ?? null,
                'message' => $isSuccess ? 'Manifest created successfully' : ($data['message'] ?? 'Failed'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery LTL Manifest Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    public function getProviderName(): string
    {
        return 'delhivery_ltl';
    }
}
