<?php

namespace App\Services;

use App\Models\DeliveryLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class BlueDartService implements DeliveryServiceInterface
{
    // test base url
    private $baseUrl = 'https://apigateway-sandbox.bluedart.com';
    // prod url 
    private $baseUrl_prod = 'produrl';

    // login credentials (common)
    private $clientId = 'D7FGUzOG0AvjIGT6uGLRXs6AH8GbzhbA';
    private $clientSecret = 'bIkWdoWf3vXyjFVW';

    // account
    private $loginEmail = 'admin@gmail.com';
    private $loginPassword = '123456';

    // Static for waybill / tracking testing
    private $loginId = 'BOM47572';
    private $licenceKey = 'qfelnjusjpkv1uenvqq6svivfipntqkt';
    private $trackingLoginId = 'BOM77977';
    private $trackingLicenceKey = 'qrisjiiqul0ztmhsvgemgqlpopqjhonk';

    // Static for waybill / tracking prod
    private $loginId_prod = 'BOM77977';
    private $licenceKey_prod = 'l8hgvmorurqgu8nss8kikukxwovlopppaa';
    private $trackingLoginId_prod = 'BOM77977';
    private $trackingLicenceKey_prod = 'qrisjiiqul0ztmhsvgemgqlpopqjhonkaa';

    private $jwtToken = null;

    /**
     * Log API call to DeliveryLog
     */
    private function logApiCall(array $data): void
    {
        try {
            $logData = [
                'booking_id'             => $data['booking_id'] ?? null,
                'delivery_provider'      => 'bluedart',
                'api_endpoint'           => $data['api_endpoint'] ?? null,
                'request_payload'        => $data['request_payload'] ?? [],
                'response_data'          => $data['response_data'] ?? [],
                'status_code'            => $data['status_code'] ?? null,
                'is_success'             => $data['is_success'] ?? false,
                'awb_number'             => $data['awb_number'] ?? null,
                'token_number'           => $data['token_number'] ?? null,
                'error_message'          => $data['error_message'] ?? null,
                'provider_specific_data' => $data['provider_specific_data'] ?? [],
            ];

            DeliveryLog::create($logData);
        } catch (Throwable $e) {
            Log::error("Failed to log API call: " . $e->getMessage());
        }
    }

    /**
     * Extract error message from BlueDart status
     */
    private function extractErrorMessage(array $result = []): ?string
    {
        if (isset($result['Status']) && is_array($result['Status'])) {
            return collect($result['Status'])
                ->map(fn($s) => $s['StatusInformation'] ?? null)
                ->filter()
                ->implode(' | ');
        }
        return null;
    }

    /**
     * Authenticate with BlueDart API
     */
    private function authenticate(int $test = 1): string
    {
        if ($this->jwtToken) {
            return $this->jwtToken;
        }

        $baseUrl = $test ? $this->baseUrl : $this->baseUrl_prod;
        $endpoint = '/in/transportation/token/v1/login';
        $payload = [
            'email'    => $this->loginEmail,
            'password' => $this->loginPassword,
        ];

        try {
            $response = Http::withHeaders([
                'ClientID'     => $this->clientId,
                'ClientSecret' => $this->clientSecret,
                'Content-Type' => 'application/json',
            ])->withBody(json_encode($payload), 'application/json')
              ->get($baseUrl . $endpoint);

            $data = $response->json();

            Log::info('BlueDart Auth Response', [
                'status_code' => $response->status(),
                'response_body' => $data,
            ]);

            $this->jwtToken = $data['JWTToken'] ?? null;

            $this->logApiCall([
                'api_endpoint'    => $endpoint,
                'request_payload' => $payload,
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => $response->successful() && !empty($this->jwtToken),
                'error_message'   => $this->jwtToken ? null : ($data['message'] ?? 'Auth failed'),
            ]);

            if (!$this->jwtToken) {
                throw new \Exception('BlueDart Auth failed: ' . json_encode($data));
            }

            return $this->jwtToken;
        } catch (\Exception $e) {
            Log::error('BlueDart Authentication Exception: ' . $e->getMessage());
            throw new \Exception('BlueDart Authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Create Waybill
     */
    public function createWaybill(array $payload, $bookingId = null, $clientid = null, int $test = 1): array
    {
        try {
            $jwt = $this->authenticate($test);

            $baseUrl    = $test ? $this->baseUrl : $this->baseUrl_prod;
            $finalloginId    = $test ? $this->loginId : $this->loginId_prod;
            $finallicenceKey = $test ? $this->licenceKey : $this->licenceKey_prod;

            $endpoint = '/in/transportation/waybill/v1/GenerateWayBill';

            // Remove any profile sent by caller
            unset($payload['Profile']);

            $finalPayload = [
                'Request' => $payload['Request'],
                'Profile' => $payload['Profile'] ?? [
                    'LoginID'    => $finalloginId,
                    'LicenceKey' => $finallicenceKey,
                    'Api_type'   => 'S'
                ]
            ];
            // dd($finalPayload);
            $response = Http::withHeaders([
                'JWTToken'     => $jwt,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . $endpoint, $finalPayload);

            $data = $response->json();

            // Determine result for success or error
            $result = $data['GenerateWayBillResult'] ?? ($data['error-response'][0] ?? []);

            $this->logApiCall([
                'booking_id'      => $bookingId,
                'api_endpoint'    => $endpoint,
                'request_payload' => $finalPayload,
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => isset($result['IsError']) ? !$result['IsError'] : $response->successful(),
                'awb_number'      => $result['AWBNo'] ?? null,
                'token_number'    => $result['TokenNumber'] ?? null,
                'error_message'   => $this->extractErrorMessage($result),
                'provider_specific_data' => $result,
            ]);

            // Return standardized response
            if ($response->successful() && isset($result['AWBNo'])) {
                return [
                    'success' => true,
                    'waybill' => $result['AWBNo'],
                    'token'   => $result['TokenNumber'] ?? null,
                    'message' => 'Waybill created successfully',
                    'data'    => $result
                ];
            } else {
                return [
                    'success' => false,
                    'waybill' => null,
                    'token'   => null,
                    'message' => $this->extractErrorMessage($result) ?? ($data['message'] ?? 'Failed to create waybill'),
                    'data'    => $data
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'waybill' => null,
                'message' => 'Exception occurred: ' . $e->getMessage(),
                'data'    => []
            ];
        }
    }

    /**
     * Track Shipment
     */
    public function trackShipment(string $awbNumber, $bookingId = null, int $test = 1): array
    {
        $jwt = $this->authenticate($test);

        $baseUrl         = $test ? $this->baseUrl : $this->baseUrl_prod;
        $trackingLoginId = $test ? $this->trackingLoginId : $this->trackingLoginId_prod;
        $trackingKey     = $test ? $this->trackingLicenceKey : $this->trackingLicenceKey_prod;

        $endpoint = '/in/transportation/tracking/v1/shipment';
        $query = [
            'handler' => 'tnt',
            'loginid' => $trackingLoginId,
            'numbers' => $awbNumber,
            'format'  => 'json',
            'lickey'  => $trackingKey,
            'scan'    => 1,
            'action'  => 'custawbquery',
            'verno'   => 1,
            'awb'     => 'awb',
        ];

        $response = Http::withHeaders([
            'JWTToken' => $jwt,
            'Accept'   => 'application/json'
        ])->get($baseUrl . $endpoint, $query);

        $data = $response->json();

        $this->logApiCall([
            'booking_id'      => $bookingId,
            'api_endpoint'    => $endpoint,
            'request_payload' => $query,
            'response_data'   => $data,
            'status_code'     => $response->status(),
            'is_success'      => $response->successful(),
            'awb_number'      => $awbNumber,
            'error_message'   => $response->successful() ? null : ($data['message'] ?? 'Tracking failed'),
        ]);

        return $data;
    }

    public function getProviderName(): string
    {
        return 'bluedart';
    }
}
