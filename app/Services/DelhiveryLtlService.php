<?php

namespace App\Services;

use App\Models\DeliveryLog;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class DelhiveryLtlService implements DeliveryServiceInterface
{
    private $baseUrl = 'https://ltl-clients-api-dev.delhivery.com';
    private $baseUrlProd = 'https://ltl-clients-api.delhivery.com';
    
    // API Key for authentication (Bearer token for login endpoint)
    private $apiKey;
    
    // Test credentials
    private $username = 'LDSOLUTIONDCB2BRC-B2B';
    private $password = 'Ldsolution@123';
    
    // Production credentials (from environment)
    private $usernameProd;
    private $passwordProd;

    private $jwtToken = null;

    public function __construct()
    {
        // Load credentials from environment variables with fallback to defaults
        $this->apiKey = env('DELHIVERY_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VybmFtZSI6IkxEU09MVVRJT05EQ0IyQlJDLUIyQiIsInBob25lX251bWJlciI6bnVsbCwibGFzdF9uYW1lIjoiTERTT0xVVElPTkRDIEIyQlJDIiwidXNlcl90eXBlIjoiQ0wiLCJpYXQiOjE3NjM0NjUyMjYsImlzX2NsaWVudF9hZG1pbiI6dHJ1ZSwidGVuYW50IjoiRGVsaGl2ZXJ5IiwiYXVkIjoiR3ZES3pvZDZhT0lNM0xjeWE5QmpmQmI4YnZGa1lUWHkiLCJmaXJzdF9uYW1lIjoiTERTT0xVVElPTkRDIEIyQlJDIiwic3ViIjoidW1zOjp1c2VyOjo0N2VjZDZhYS05NzlkLTExZjAtYTJkZi0wMmIzZTM4MDg4N2IiLCJjbGllbnRfdXVpZCI6ImNtczo6Y2xpZW50Ojo0N2VjZDZhYi05NzlkLTExZjAtYTJkZi0wMmIzZTM4MDg4N2IiLCJpZGxlIjoxNzY0MDcwMDI2LCJjbGllbnRfZW1haWwiOiJiaGFydGkua0BkZWxoaXZlcnkuY29tIiwiZXhwIjoxNzYzNTUxNjI2LCJjbGllbnRfbmFtZSI6IkxEU09MVVRJT05EQ0IyQlJDLUIyQiIsInRva2VuX2lkIjoiZGVjYmJiODgtM2VhNy00OGQyLWE2NTAtNzY0MzcyZmJhYzJhIiwiZW1haWwiOiJiaGFydGkua0BkZWxoaXZlcnkuY29tIiwiYXBpX3ZlcnNpb24iOiJ2MiIsInRvZSI6MTc2MzQ2NTIyNn0.Z_MCIom-n1iUnFv1XWSElObSWbK7SVDXk1io7Z1mBVo');
        $this->usernameProd = env('DELHIVERY_USERNAME_PROD', '');
        $this->passwordProd = env('DELHIVERY_PASSWORD_PROD', '');
    }

    /**
     * Log API call
     */
    private function logApiCall(array $data): void
    {
        try {
            // Ensure error_message is always a string
            $errorMessage = $data['error_message'] ?? null;
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            } elseif ($errorMessage !== null && !is_string($errorMessage)) {
                $errorMessage = (string) $errorMessage;
            }
            
            DeliveryLog::create([
                'booking_id'             => $data['booking_id'] ?? null,
                'delivery_provider'      => 'delhivery_ltl',
                'api_endpoint'           => $data['api_endpoint'] ?? null,
                'request_payload'        => $data['request_payload'] ?? [],
                'response_data'          => $data['response_data'] ?? [],
                'status_code'            => $data['status_code'] ?? null,
                'is_success'             => $data['is_success'] ?? false,
                'awb_number'             => $data['awb_number'] ?? null,
                'error_message'          => $errorMessage,
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

        // Use production credentials if in production mode, otherwise use test credentials
        $username = $test ? $this->username : ($this->usernameProd ?: $this->username);
        $password = $test ? $this->password : ($this->passwordProd ?: $this->password);

        $payload = [
            'username' => $username,
            'password' => $password,
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

        // The response might contain 'access_token', 'token', 'JWTToken', or nested in 'data.jwt'
        // Based on Delhivery API, the token is usually in data['data']['jwt']
        $this->jwtToken = $data['data']['jwt'] ?? $data['access_token'] ?? $data['token'] ?? $data['JWTToken'] ?? null;
        
        if (!$this->jwtToken) {
            // Log the full response for debugging
            Log::error('Delhivery LTL auth - No token found in response. Response: ' . json_encode($data));
            throw new \Exception('Delhivery LTL auth failed: No token in response. Response: ' . json_encode($data));
        }
        
        return $this->jwtToken;
    }

    /**
     * Check pincode serviceability
     * 
     * @param string $pincode
     * @param float $weight Weight in kg
     * @param int $test
     * @return array
     */
    public function checkPincodeServiceability(string $pincode, float $weight = 1, int $test = 1): array
    {
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = "/pincode-service/{$pincode}";
        
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
                'Content-Type' => 'application/json',
            ])->get($baseUrl . $endpoint, [
                'weight' => $weight
            ]);

            $data = $response->json();

            $isServiceable = false;
            $serviceabilityData = [];

            if ($response->successful() && isset($data['data']['pincode_serviceability_data'])) {
                $serviceabilityData = $data['data']['pincode_serviceability_data'];
                // Check if any center is serviceable
                foreach ($serviceabilityData as $center) {
                    if (isset($center['fm_serviceable']) && $center['fm_serviceable'] === true) {
                        $isServiceable = true;
                        break;
                    }
                }
            }

            $this->logApiCall([
                'api_endpoint'    => $endpoint,
                'request_payload' => ['pincode' => $pincode, 'weight' => $weight],
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => $response->successful(),
                'error_message'   => $response->successful() ? null : ($data['message'] ?? 'Serviceability check failed'),
            ]);

            return [
                'success' => $response->successful(),
                'is_serviceable' => $isServiceable,
                'serviceability_data' => $serviceabilityData,
                'message' => $isServiceable ? 'Pincode is serviceable' : 'Pincode is not serviceable',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery LTL Serviceability Check Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'is_serviceable' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Create or get warehouse by pincode
     */
    public function getOrCreateWarehouse(string $pincode, array $warehouseData, int $test = 1): ?Warehouse
    {
        // Check if warehouse exists for this pincode
        $warehouse = Warehouse::where('pin_code', $pincode)
            ->where('is_active', true)
            ->first();

        if ($warehouse) {
            Log::info("Warehouse found in DB for pincode: {$pincode}");
            return $warehouse;
        }

        Log::info("Warehouse not found for pincode: {$pincode}, attempting to create via API");

        // Create new warehouse via API
        $result = $this->createWarehouse($warehouseData, $test);

        if ($result['success'] && isset($result['warehouse_id'])) {
            Log::info("Warehouse created successfully via API. Warehouse ID: " . $result['warehouse_id']);
            
            // Save warehouse to database
            try {
                $warehouse = Warehouse::create([
                    'pin_code' => $pincode,
                    'city' => $warehouseData['city'] ?? '',
                    'state' => $warehouseData['state'] ?? '',
                    'country' => $warehouseData['country'] ?? 'India',
                    'name' => $warehouseData['name'] ?? "Warehouse_{$pincode}",
                    'address_details' => $warehouseData['address_details'] ?? [],
                    'business_hours' => $warehouseData['business_hours'] ?? [],
                    'pick_up_hours' => $warehouseData['pick_up_hours'] ?? [],
                    'pick_up_days' => $warehouseData['pick_up_days'] ?? [],
                    'business_days' => $warehouseData['business_days'] ?? [],
                    'ret_address' => $warehouseData['ret_address'] ?? [],
                    'warehouse_id' => $result['warehouse_id'],
                    'is_active' => true,
                ]);

                Log::info("Warehouse saved to database. DB ID: " . $warehouse->id);
                return $warehouse;
            } catch (\Exception $e) {
                Log::error("Failed to save warehouse to database: " . $e->getMessage());
                // Return null but log the error
                return null;
            }
        } else {
            $errorMsg = $result['message'] ?? 'Unknown error';
            if (is_array($errorMsg)) {
                $errorMsg = json_encode($errorMsg);
            }
            Log::warning("Warehouse creation failed via API. Error: " . $errorMsg);
        }

        return null;
    }

    /**
     * Create Warehouse
     */
    public function createWarehouse(array $payload, int $test = 1): array
    {
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = '/client-warehouse/create/';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
                'Content-Type' => 'application/json',
            ])->post($baseUrl . $endpoint, $payload);

            $data = $response->json();

            // Check for success - warehouse ID is in data.result.id according to API response
            // Response structure: { "data": { "result": { "id": "..." } } }
            $warehouseId = $data['data']['result']['id'] ?? $data['data']['id'] ?? $data['id'] ?? $data['warehouse_id'] ?? $data['data']['warehouse_id'] ?? null;
            
            // Also check if the response indicates success
            $apiSuccess = ($data['success'] ?? false) || ($data['data']['success'] ?? false);
            $isSuccess = $response->successful() && $apiSuccess && $warehouseId !== null;
            
            Log::info('Delhivery Warehouse Creation Response', [
                'status_code' => $response->status(),
                'api_success' => $apiSuccess,
                'warehouse_id' => $warehouseId,
                'response_structure' => array_keys($data),
                'full_response' => $data, // Log full response for debugging
            ]);

            // Extract error message - handle both string and array formats
            $errorMsg = null;
            if (!$isSuccess) {
                if (isset($data['error'])) {
                    if (is_array($data['error'])) {
                        $msg = $data['error']['message'] ?? null;
                        if ($msg !== null) {
                            $errorMsg = is_array($msg) ? json_encode($msg) : (string) $msg;
                        } else {
                            $errorMsg = json_encode($data['error']);
                        }
                    } else {
                        $errorMsg = is_array($data['error']) ? json_encode($data['error']) : (string) $data['error'];
                    }
                } else {
                    $msg = $data['message'] ?? 'Warehouse creation failed';
                    $errorMsg = is_array($msg) ? json_encode($msg) : (string) $msg;
                }
            }
            
            $this->logApiCall([
                'api_endpoint'    => $endpoint,
                'request_payload' => $payload,
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => $isSuccess,
                'error_message'   => $errorMsg,
            ]);

            if (!$isSuccess) {
                Log::error('Delhivery Warehouse Creation Failed', [
                    'response_status' => $response->status(),
                    'response_data' => $data,
                ]);
            }

            return [
                'success' => $isSuccess,
                'warehouse_id' => $warehouseId,
                'message' => $isSuccess ? 'Warehouse created successfully' : ($data['message'] ?? $data['error'] ?? 'Failed'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery LTL Warehouse Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => [],
            ];
        }
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
            // Build multipart form data according to API format
            $multipartData = [];
            
            // Add all form fields
            if (isset($payload['lrn'])) {
                $multipartData[] = ['name' => 'lrn', 'contents' => $payload['lrn']];
            }
            if (isset($payload['pickup_location_name'])) {
                $multipartData[] = ['name' => 'pickup_location_name', 'contents' => $payload['pickup_location_name']];
            }
            if (isset($payload['pickup_location_id'])) {
                $multipartData[] = ['name' => 'pickup_location_id', 'contents' => $payload['pickup_location_id']];
            }
            if (isset($payload['payment_mode'])) {
                $multipartData[] = ['name' => 'payment_mode', 'contents' => $payload['payment_mode']];
            }
            if (isset($payload['weight'])) {
                // Weight should be in grams according to API docs
                $multipartData[] = ['name' => 'weight', 'contents' => (string)$payload['weight']];
            }
            if (isset($payload['cod_amount'])) {
                $multipartData[] = ['name' => 'cod_amount', 'contents' => (string)$payload['cod_amount']];
            }
            if (isset($payload['freight_mode'])) {
                $multipartData[] = ['name' => 'freight_mode', 'contents' => $payload['freight_mode']];
            }
            if (isset($payload['dropoff_location'])) {
                $multipartData[] = ['name' => 'dropoff_location', 'contents' => is_array($payload['dropoff_location']) 
                    ? json_encode($payload['dropoff_location']) 
                    : $payload['dropoff_location']];
            }
            if (isset($payload['rov_insurance'])) {
                $multipartData[] = ['name' => 'rov_insurance', 'contents' => $payload['rov_insurance'] ? 'True' : 'False'];
            }
            if (isset($payload['invoices'])) {
                $multipartData[] = ['name' => 'invoices', 'contents' => is_array($payload['invoices']) 
                    ? json_encode($payload['invoices']) 
                    : $payload['invoices']];
            }
            if (isset($payload['shipment_details'])) {
                // Ensure shipment_details is properly formatted as JSON string
                // Delhivery API expects a JSON array string in the multipart form
                // The API may parse it as Python, so we need Python-style booleans (False/True)
                if (is_array($payload['shipment_details'])) {
                    // Validate that it's an array of objects
                    if (!is_array($payload['shipment_details']) || empty($payload['shipment_details'])) {
                        throw new \Exception('shipment_details must be a non-empty array');
                    }
                    
                    // First encode as standard JSON
                    $shipmentDetailsContent = json_encode($payload['shipment_details'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    
                    // Convert JSON booleans to Python-style booleans (False/True)
                    // This is needed because Delhivery's API may parse the JSON as Python
                    $shipmentDetailsContent = preg_replace('/:(\s*)false(\s*[,}])/', ':$1False$2', $shipmentDetailsContent);
                    $shipmentDetailsContent = preg_replace('/:(\s*)true(\s*[,}])/', ':$1True$2', $shipmentDetailsContent);
                    $shipmentDetailsContent = preg_replace('/,(\s*)false(\s*[,}])/', ',$1False$2', $shipmentDetailsContent);
                    $shipmentDetailsContent = preg_replace('/,(\s*)true(\s*[,}])/', ',$1True$2', $shipmentDetailsContent);
                    
                    Log::info('Delhivery shipment_details JSON (Python-style): ' . $shipmentDetailsContent);
                } else {
                    // If it's already a string, use it as-is
                    $shipmentDetailsContent = $payload['shipment_details'];
                }
                
                $multipartData[] = ['name' => 'shipment_details', 'contents' => $shipmentDetailsContent];
            }
            if (isset($payload['dimensions'])) {
                $multipartData[] = ['name' => 'dimensions', 'contents' => is_array($payload['dimensions']) 
                    ? json_encode($payload['dimensions']) 
                    : $payload['dimensions']];
            }
            if (isset($payload['doc_data'])) {
                $multipartData[] = ['name' => 'doc_data', 'contents' => is_array($payload['doc_data']) 
                    ? json_encode($payload['doc_data']) 
                    : $payload['doc_data']];
            }
            if (isset($payload['fm_pickup'])) {
                $multipartData[] = ['name' => 'fm_pickup', 'contents' => $payload['fm_pickup'] ? 'True' : 'False'];
            }
            if (isset($payload['billing_address'])) {
                $multipartData[] = ['name' => 'billing_address', 'contents' => is_array($payload['billing_address']) 
                    ? json_encode($payload['billing_address']) 
                    : $payload['billing_address']];
            }
            
            // Add doc_file if provided
            if (isset($payload['doc_file']) && file_exists($payload['doc_file'])) {
                $multipartData[] = [
                    'name' => 'doc_file',
                    'contents' => fopen($payload['doc_file'], 'r'),
                    'filename' => basename($payload['doc_file'])
                ];
            }

            // Log the multipart data structure for debugging
            Log::info('Delhivery Manifest Request - Multipart Data Keys: ' . json_encode(array_column($multipartData, 'name')));
            foreach ($multipartData as $item) {
                if ($item['name'] === 'shipment_details') {
                    Log::info('Delhivery shipment_details content: ' . $item['contents']);
                    Log::info('Delhivery shipment_details type: ' . gettype($item['contents']));
                }
            }

            // Log the final multipart data for debugging
            Log::info('Delhivery Manifest - Final multipart data structure:');
            foreach ($multipartData as $item) {
                if ($item['name'] === 'shipment_details') {
                    Log::info('shipment_details final value: ' . substr($item['contents'], 0, 200));
                }
            }
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
            ])->asMultipart()->post($baseUrl . $endpoint, $multipartData);

            $data = $response->json();

            // Delhivery manifest API is async - it returns job_id on success
            // We need to check for job_id OR direct waybill/manifest numbers
            $isSuccess = isset($data['job_id']) || isset($data['request_id']) || 
                        isset($data['manifest_number']) || isset($data['manifest_id']) || 
                        isset($data['waybill_number']) || (isset($data['success']) && $data['success'] === true);

            // Prepare payload for logging (convert arrays to JSON strings for logging)
            $logPayload = [];
            foreach ($payload as $key => $value) {
                if (is_array($value)) {
                    $logPayload[$key] = json_encode($value);
                } else {
                    $logPayload[$key] = $value;
                }
            }
            
            // Extract error message - handle both string and array formats
            $errorMsg = null;
            if (!$isSuccess) {
                if (isset($data['error'])) {
                    if (is_array($data['error'])) {
                        $msg = $data['error']['message'] ?? null;
                        if ($msg !== null) {
                            $errorMsg = is_array($msg) ? json_encode($msg) : (string) $msg;
                        } else {
                            $errorMsg = json_encode($data['error']);
                        }
                    } else {
                        $errorMsg = is_array($data['error']) ? json_encode($data['error']) : (string) $data['error'];
                    }
                } else {
                    $msg = $data['message'] ?? 'Manifest creation failed';
                    $errorMsg = is_array($msg) ? json_encode($msg) : (string) $msg;
                }
            }
            
            $this->logApiCall([
                'booking_id'      => $bookingId,
                'api_endpoint'    => $endpoint,
                'request_payload' => $logPayload,
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => $isSuccess,
                'awb_number'      => $data['manifest_number'] ?? $data['manifest_id'] ?? $data['waybill_number'] ?? null,
                'error_message'   => $errorMsg,
            ]);

            // For async API, return job_id/request_id for polling
            $jobId = $data['job_id'] ?? $data['request_id'] ?? null;
            $awbNumber = $data['manifest_number'] ?? $data['manifest_id'] ?? $data['waybill_number'] ?? null;
            
            return [
                'success' => $isSuccess,
                'awb_number' => $awbNumber,
                'job_id' => $jobId, // For async API, use this to poll status
                'message' => $isSuccess ? ($jobId ? 'Manifest job created successfully. Use job_id to poll status.' : 'Manifest created successfully') : ($data['message'] ?? ($data['error'] ?? 'Failed')),
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

    /**
     * Create Waybill (wrapper for createManifest for consistency)
     * Automatically polls manifest status after creation to get waybill numbers
     */
    public function createWaybill(array $payload, $bookingId = null, $clientid = null, int $test = 1): array
    {
        $result = $this->createManifest($payload, $bookingId, $test);
        
        // If we got a job_id but no waybill number, automatically poll for status
        $jobId = $result['job_id'] ?? null;
        $awbNumber = $result['awb_number'] ?? null;
        
        if ($result['success'] && $jobId && !$awbNumber) {
            Log::info("Delhivery manifest created with job_id: {$jobId}. Automatically polling status...");
            
            // Poll manifest status with retries
            $maxRetries = 3;
            $retryDelay = 2; // seconds
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                if ($attempt > 1) {
                    Log::info("Retrying manifest status check (attempt {$attempt}/{$maxRetries})...");
                    sleep($retryDelay);
                }
                
                $statusResult = $this->getManifestStatus($jobId, $test);
                
                // Check if we got the waybill numbers
                if ($statusResult['success'] && ($statusResult['lr_number'] || !empty($statusResult['awb_numbers']))) {
                    // Get LR number (primary tracking ID) or first AWB number
                    $awbNumber = $statusResult['lr_number'] ?? 
                               (isset($statusResult['awb_numbers']) && !empty($statusResult['awb_numbers']) 
                                ? $statusResult['awb_numbers'][0] : null);
                    
                    Log::info("Delhivery manifest status retrieved. LR: {$awbNumber}, AWBs: " . json_encode($statusResult['awb_numbers'] ?? []));
                    
                    // Update result with waybill numbers
                    $result['awb_number'] = $awbNumber;
                    $result['lr_number'] = $statusResult['lr_number'] ?? null;
                    $result['awb_numbers'] = $statusResult['awb_numbers'] ?? [];
                    $result['doc_waybill'] = $statusResult['doc_waybill'] ?? null;
                    $result['master_waybill'] = $statusResult['master_waybill'] ?? null;
                    $result['status'] = $statusResult['status'] ?? null;
                    $result['message'] = 'Manifest created and waybill numbers retrieved successfully';
                    break; // Success, exit retry loop
                }
                
                // If this is not the last attempt, continue to retry
                if ($attempt < $maxRetries) {
                    Log::info("Manifest status not ready yet, will retry...");
                }
            }
            
            // If still no waybill after retries, keep the job_id for later polling
            if (!$awbNumber) {
                Log::warning("Delhivery manifest status not ready after {$maxRetries} attempts for job_id: {$jobId}. Returning job_id for later polling.");
            }
        }
        
        // Standardize response format to match BlueDart
        return [
            'success' => $result['success'],
            'waybill' => $awbNumber ?? $result['awb_number'] ?? null,
            'job_id' => $jobId, // Include job_id for async API
            'lr_number' => $result['lr_number'] ?? null,
            'awb_numbers' => $result['awb_numbers'] ?? [],
            'message' => $result['message'],
            'data' => $result['data'] ?? []
        ];
    }

    /**
     * Get Manifest Status using job_id
     * This API provides the LR and AWB details in response using the JOB ID received from the Shipment Creation API
     * Endpoint: GET /manifest?job_id={job_id}
     */
    public function getManifestStatus(string $jobId, int $test = 1): array
    {
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = '/manifest';

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
            ])->get($baseUrl . $endpoint, [
                'job_id' => $jobId
            ]);

            $data = $response->json();

            // Check for success - response has success field and data object
            $isSuccess = $response->successful() && 
                        (isset($data['success']) && $data['success'] === true) && 
                        isset($data['data']);

            $this->logApiCall([
                'api_endpoint' => $endpoint,
                'request_payload' => ['job_id' => $jobId],
                'response_data' => $data,
                'status_code' => $response->status(),
                'is_success' => $isSuccess,
            ]);

            // Extract LR and AWB numbers from response
            // Response structure: { "success": true, "data": { "lrnum": "...", "waybills": [...], "doc_waybill": "...", "master_waybill": "...", "status": "Complete" } }
            $responseData = $data['data'] ?? [];
            $lrNumber = $responseData['lrnum'] ?? $responseData['lr_number'] ?? $responseData['lr'] ?? null;
            $awbNumbers = $responseData['waybills'] ?? $responseData['awb_numbers'] ?? $responseData['awbs'] ?? [];
            $docWaybill = $responseData['doc_waybill'] ?? null;
            $masterWaybill = $responseData['master_waybill'] ?? null;
            $status = $responseData['status'] ?? null;
            
            // If awb_numbers is a single value, convert to array
            if (!is_array($awbNumbers) && !empty($awbNumbers)) {
                $awbNumbers = [$awbNumbers];
            }
            
            // If status is "Complete", we have the waybill numbers
            $isComplete = ($status === 'Complete' || $status === 'complete') && ($lrNumber || !empty($awbNumbers));

            return [
                'success' => $isSuccess && $isComplete,
                'data' => $data,
                'lr_number' => $lrNumber,
                'awb_numbers' => $awbNumbers,
                'doc_waybill' => $docWaybill,
                'master_waybill' => $masterWaybill,
                'status' => $status,
                'message' => $isSuccess && $isComplete ? 'Manifest status retrieved successfully' : ($status ? "Manifest status: {$status}" : ($data['message'] ?? 'Failed to get manifest status')),
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery Get Manifest Status Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * Get shipping label URLs for a LR number
     * Endpoint: GET /label/get_urls/std/{lr_number}
     */
    public function getLabelUrls(string $lrNumber, int $test = 1): array
    {
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = "/label/get_urls/std/{$lrNumber}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
            ])->get($baseUrl . $endpoint);

            $data = $response->json();

            $isSuccess = $response->successful() && isset($data['success']) && $data['success'] === true && isset($data['data']);

            $this->logApiCall([
                'api_endpoint' => $endpoint,
                'request_payload' => ['lr_number' => $lrNumber],
                'response_data' => $data,
                'status_code' => $response->status(),
                'is_success' => $isSuccess,
            ]);

            return [
                'success' => $isSuccess,
                'label_urls' => $data['data'] ?? [],
                'message' => $isSuccess ? 'Label URLs retrieved successfully' : ($data['message'] ?? 'Failed to get label URLs'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery Get Label URLs Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'label_urls' => [],
                'data' => [],
            ];
        }
    }

    /**
     * Download and store shipping labels from Delhivery
     * Downloads labels from URLs and saves them to storage
     * The label URLs return base64 encoded image data in JSON format
     */
    public function downloadAndStoreLabels(string $lrNumber, array $labelUrls, string $bookingId = null, int $test = 1): ?string
    {
        try {
            if (empty($labelUrls)) {
                Log::warning("No label URLs provided for LR: {$lrNumber}");
                return null;
            }

            // Get JWT token for authentication (label URLs might require auth)
            $jwt = $this->authenticate($test);

            // Download all labels
            $downloadedImages = [];
            
            foreach ($labelUrls as $index => $labelUrl) {
                try {
                    Log::info("Downloading Delhivery label " . ($index + 1) . " from: {$labelUrl}");
                    
                    // Try with authentication first
                    $response = Http::withHeaders([
                        'Authorization' => "Bearer {$jwt}",
                    ])->timeout(30)->get($labelUrl);
                    
                    // If unauthorized, try without auth (some URLs might be public)
                    if ($response->status() === 401 || $response->status() === 403) {
                        Log::info("Label URL requires no auth, retrying without Authorization header");
                        $response = Http::timeout(30)->get($labelUrl);
                    }
                    
                    if ($response->successful()) {
                        $labelData = $response->json();
                        
                        // Response format: { "success": true, "data": "data:image/png;base64,..." }
                        if (isset($labelData['data']) && is_string($labelData['data'])) {
                            $base64Data = $labelData['data'];
                            
                            // Check if it's a data URI
                            if (strpos($base64Data, 'data:') === 0) {
                                // Extract base64 data (everything after the comma)
                                $commaPos = strpos($base64Data, ',');
                                if ($commaPos !== false) {
                                    $imageData = base64_decode(substr($base64Data, $commaPos + 1));
                                    
                                    if ($imageData !== false) {
                                        // Determine file extension from data URI
                                        $extension = 'png'; // Default
                                        if (strpos($base64Data, 'data:image/png') === 0) {
                                            $extension = 'png';
                                        } elseif (strpos($base64Data, 'data:image/jpeg') === 0 || strpos($base64Data, 'data:image/jpg') === 0) {
                                            $extension = 'jpg';
                                        } elseif (strpos($base64Data, 'data:application/pdf') === 0 || strpos($base64Data, 'data:image/pdf') === 0) {
                                            $extension = 'pdf';
                                        }
                                        
                                        $downloadedImages[] = [
                                            'data' => $imageData,
                                            'extension' => $extension,
                                            'index' => $index
                                        ];
                                        
                                        Log::info("Downloaded label " . ($index + 1) . " as {$extension} format");
                                    } else {
                                        Log::warning("Failed to decode base64 data for label " . ($index + 1));
                                    }
                                } else {
                                    Log::warning("Invalid data URI format for label " . ($index + 1));
                                }
                            } else {
                                // If it's already binary data (not a data URI)
                                $imageData = base64_decode($base64Data);
                                if ($imageData !== false) {
                                    $downloadedImages[] = [
                                        'data' => $imageData,
                                        'extension' => 'png', // Default
                                        'index' => $index
                                    ];
                                    Log::info("Downloaded label " . ($index + 1) . " (direct base64)");
                                }
                            }
                        } else {
                            // Try to get binary data directly
                            $imageData = $response->body();
                            if (!empty($imageData)) {
                                $contentType = $response->header('Content-Type', '');
                                $extension = 'png'; // Default
                                
                                if (strpos($contentType, 'image/png') !== false) {
                                    $extension = 'png';
                                } elseif (strpos($contentType, 'image/jpeg') !== false || strpos($contentType, 'image/jpg') !== false) {
                                    $extension = 'jpg';
                                } elseif (strpos($contentType, 'application/pdf') !== false) {
                                    $extension = 'pdf';
                                }
                                
                                $downloadedImages[] = [
                                    'data' => $imageData,
                                    'extension' => $extension,
                                    'index' => $index
                                ];
                                
                                Log::info("Downloaded label " . ($index + 1) . " (binary, {$extension})");
                            }
                        }
                    } else {
                        Log::warning("Failed to download label " . ($index + 1) . " from: {$labelUrl}. Status: " . $response->status());
                    }
                } catch (\Exception $e) {
                    Log::error("Error downloading label " . ($index + 1) . ": " . $e->getMessage());
                }
            }

            if (empty($downloadedImages)) {
                Log::error("No labels were successfully downloaded for LR: {$lrNumber}");
                return null;
            }

            // Save the first label (or combine all if needed)
            // For now, we'll save the first label
            $firstLabel = $downloadedImages[0];
            $labelContent = $firstLabel['data'];
            $extension = $firstLabel['extension'];
            
            // Generate filename
            $filename = 'shipping_label_' . $lrNumber . '_' . time() . '.' . $extension;
            
            // Define the destination path (same as BlueDart)
            $destinationPath = public_path('storage/pdfs/shipping-labels/' . date('Y') . '/' . date('m'));
            
            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            // Save the file
            $filePath = $destinationPath . '/' . $filename;
            file_put_contents($filePath, $labelContent);
            
            // Verify file was saved
            if (!file_exists($filePath)) {
                Log::error("Failed to save Delhivery label file: {$filePath}");
                return null;
            }
            
            // Return the public URL
            $baseUrl = 'https://track.sbexpresscargo.com';
            $relativePath = 'pdfs/shipping-labels/' . date('Y') . '/' . date('m') . '/' . $filename;
            $url = $baseUrl . '/storage/' . $relativePath;
            
            Log::info("Delhivery label saved successfully: {$url} (Size: " . filesize($filePath) . " bytes)");
            
            return $url;
            
        } catch (\Exception $e) {
            Log::error('Delhivery Download and Store Labels Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Track Shipment
     */
    public function trackShipment(string $awbNumber, $bookingId = null, int $test = 1): array
    {
        // TODO: Implement Delhivery tracking if needed
        return [
            'success' => false,
            'message' => 'Tracking not implemented for Delhivery LTL',
            'data' => []
        ];
    }

    public function getProviderName(): string
    {
        return 'delhivery_ltl';
    }
}
