<?php

namespace App\Services;

use App\Models\DeliveryLog;
use App\Models\Warehouse;
use App\Models\PincodeServiceabilityCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use setasign\Fpdi\Tcpdf\Fpdi;
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
    private $usernameProd = 'SBTECHNOWORLDSOLUTIONDCB2BRC';
    private $passwordProd = 'A4aFT2zFdDkBA9@';

    // Cache JWT tokens separately for test and production
    private $jwtTokenTest = null;
    private $jwtTokenProd = null;

    public function __construct()
    {
        // Load API key from environment variable with fallback to default
        // Production username and password are hardcoded in class properties
        $this->apiKey = env('DELHIVERY_API_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VybmFtZSI6IkxEU09MVVRJT05EQ0IyQlJDLUIyQiIsInBob25lX251bWJlciI6bnVsbCwibGFzdF9uYW1lIjoiTERTT0xVVElPTkRDIEIyQlJDIiwidXNlcl90eXBlIjoiQ0wiLCJpYXQiOjE3NjM0NjUyMjYsImlzX2NsaWVudF9hZG1pbiI6dHJ1ZSwidGVuYW50IjoiRGVsaGl2ZXJ5IiwiYXVkIjoiR3ZES3pvZDZhT0lNM0xjeWE5QmpmQmI4YnZGa1lUWHkiLCJmaXJzdF9uYW1lIjoiTERTT0xVVElPTkRDIEIyQlJDIiwic3ViIjoidW1zOjp1c2VyOjo0N2VjZDZhYS05NzlkLTExZjAtYTJkZi0wMmIzZTM4MDg4N2IiLCJjbGllbnRfdXVpZCI6ImNtczo6Y2xpZW50Ojo0N2VjZDZhYi05NzlkLTExZjAtYTJkZi0wMmIzZTM4MDg4N2IiLCJpZGxlIjoxNzY0MDcwMDI2LCJjbGllbnRfZW1haWwiOiJiaGFydGkua0BkZWxoaXZlcnkuY29tIiwiZXhwIjoxNzYzNTUxNjI2LCJjbGllbnRfbmFtZSI6IkxEU09MVVRJT05EQ0IyQlJDLUIyQiIsInRva2VuX2lkIjoiZGVjYmJiODgtM2VhNy00OGQyLWE2NTAtNzY0MzcyZmJhYzJhIiwiZW1haWwiOiJiaGFydGkua0BkZWxoaXZlcnkuY29tIiwiYXBpX3ZlcnNpb24iOiJ2MiIsInRvZSI6MTc2MzQ2NTIyNn0.Z_MCIom-n1iUnFv1XWSElObSWbK7SVDXk1io7Z1mBVo');
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
     * Authenticate and cache JWT separately for test and production
     */
    private function authenticate(int $test = 1): string
    {
        // Use separate token cache for test and production
        $isTest = (bool) $test;
        if ($isTest && $this->jwtTokenTest) {
            return $this->jwtTokenTest;
        }
        if (!$isTest && $this->jwtTokenProd) {
            return $this->jwtTokenProd;
        }

        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = '/ums/login';

        // Use production credentials if in production mode, otherwise use test credentials
        // Production credentials are hardcoded in class properties
        $username = $test ? $this->username : $this->usernameProd;
        $password = $test ? $this->password : $this->passwordProd;

        $payload = [
            'username' => $username,
            'password' => $password,
        ];

        $response = Http::withHeaders([
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
        $jwtToken = $data['data']['jwt'] ?? $data['access_token'] ?? $data['token'] ?? $data['JWTToken'] ?? null;
        
        if (!$jwtToken) {
            // Log the full response for debugging
            Log::error('Delhivery LTL auth - No token found in response. Response: ' . json_encode($data));
            throw new \Exception('Delhivery LTL auth failed: No token in response. Response: ' . json_encode($data));
        }
        
        // Cache token separately for test and production
        if ($isTest) {
            $this->jwtTokenTest = $jwtToken;
        } else {
            $this->jwtTokenProd = $jwtToken;
        }
        
        return $jwtToken;
    }

    /**
     * Check pincode serviceability
     * Uses database cache to avoid repeated API calls
     * 
     * @param string $pincode
     * @param float $weight Weight in kg
     * @param int $test
     * @return array
     */
    public function checkPincodeServiceability(string $pincode, float $weight = 1, int $test = 1): array
    {
        $isTest = (bool) $test;
        $serviceBy = 'delhivery';
        
        // Step 1: Check cache first
        try {
            $cached = PincodeServiceabilityCache::findCache($pincode, $weight, $isTest, $serviceBy);
            
            if ($cached) {
                $isProduction = !$test && app()->environment('production');
                if (!$isProduction) {
                    Log::info("Delhivery Serviceability Cache HIT: Pincode {$pincode}, Weight {$weight}kg");
                }
                
                return [
                    'success' => true,
                    'is_serviceable' => $cached->is_serviceable,
                    'serviceability_data' => $cached->serviceability_data ?? [],
                    'message' => $cached->is_serviceable ? 'Pincode is serviceable' : 'Pincode is not serviceable',
                    'data' => $cached->full_response_data ?? [],
                    'cached' => true,
                ];
            }
        } catch (\Exception $e) {
            // If cache lookup fails, continue to API call
            if (!$test || !app()->environment('production')) {
                Log::warning("Delhivery Serviceability Cache lookup failed: " . $e->getMessage());
            }
        }
        
        // Step 2: Cache miss - call API
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = "/pincode-service/{$pincode}";
        
        try {
            $response = Http::timeout(10)->withHeaders([
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

            // Step 3: Store in cache if API call was successful
            if ($response->successful()) {
                try {
                    PincodeServiceabilityCache::storeCache(
                        $pincode,
                        $weight,
                        $isTest,
                        $serviceBy,
                        $isServiceable,
                        $serviceabilityData,
                        $data
                    );
                } catch (\Exception $e) {
                    // Don't fail the request if caching fails
                    if (!$test || !app()->environment('production')) {
                        Log::warning("Failed to cache serviceability result: " . $e->getMessage());
                    }
                }
            }

            return [
                'success' => $response->successful(),
                'is_serviceable' => $isServiceable,
                'serviceability_data' => $serviceabilityData,
                'message' => $isServiceable ? 'Pincode is serviceable' : 'Pincode is not serviceable',
                'data' => $data,
                'cached' => false,
            ];
        } catch (\Exception $e) {
            if (!$test || !app()->environment('production')) {
                Log::error('Delhivery LTL Serviceability Check Exception: ' . $e->getMessage());
            }
            
            // On exception, try to return cached data (graceful degradation)
            try {
                $cached = PincodeServiceabilityCache::findCache($pincode, $weight, $isTest, $serviceBy);
                
                if ($cached) {
                    return [
                        'success' => true,
                        'is_serviceable' => $cached->is_serviceable,
                        'serviceability_data' => $cached->serviceability_data ?? [],
                        'message' => $cached->is_serviceable ? 'Pincode is serviceable (from cache)' : 'Pincode is not serviceable (from cache)',
                        'data' => $cached->full_response_data ?? [],
                        'cached' => true,
                        'warning' => 'Using cache due to API error',
                    ];
                }
            } catch (\Exception $cacheException) {
                // Ignore cache errors during exception handling
            }
            
            return [
                'success' => false,
                'is_serviceable' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'data' => [],
                'cached' => false,
            ];
        }
    }

    /**
     * Check multiple pincodes serviceability in parallel
     * Optimized for checking delivery and pickup pincodes simultaneously
     * 
     * @param array $pincodes Array of ['pincode' => string, 'weight' => float, 'key' => string]
     * @param int $test
     * @return array Array of results keyed by provided 'key' or index
     */
    public function checkMultiplePincodeServiceability(array $pincodes, int $test = 1): array
    {
        $isTest = (bool) $test;
        $serviceBy = 'delhivery';
        $isProduction = !$test && app()->environment('production');
        $results = [];
        $apiCalls = [];
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        
        // First, check cache for all pincodes
        foreach ($pincodes as $index => $pincodeData) {
            $pincode = $pincodeData['pincode'];
            $weight = $pincodeData['weight'] ?? 1;
            $key = $pincodeData['key'] ?? $index;
            
            try {
                $cached = PincodeServiceabilityCache::findCache($pincode, $weight, $isTest, $serviceBy);
                if ($cached) {
                    $results[$key] = [
                        'success' => true,
                        'is_serviceable' => $cached->is_serviceable,
                        'serviceability_data' => $cached->serviceability_data ?? [],
                        'cached' => true,
                    ];
                    continue;
                }
            } catch (\Exception $e) {
                // Continue to API call
            }
            
            // Prepare API call for uncached pincodes
            $apiCalls[$key] = [
                'pincode' => $pincode,
                'weight' => $weight,
                'endpoint' => "/pincode-service/{$pincode}",
            ];
        }
        
        // Execute API calls in parallel
        if (!empty($apiCalls)) {
            $responses = Http::pool(function ($pool) use ($apiCalls, $baseUrl, $jwt) {
                $requests = [];
                foreach ($apiCalls as $key => $call) {
                    $requests[$key] = $pool->as($key)
                        ->timeout(10)
                        ->withHeaders([
                            'Authorization' => "Bearer {$jwt}",
                            'Content-Type' => 'application/json',
                        ])
                        ->get($baseUrl . $call['endpoint'], ['weight' => $call['weight']]);
                }
                return $requests;
            });
            
            // Process responses
            foreach ($apiCalls as $key => $call) {
                try {
                    $response = $responses[$key] ?? null;
                    if (!$response || !$response->successful()) {
                        $results[$key] = [
                            'success' => false,
                            'is_serviceable' => false,
                            'cached' => false,
                        ];
                        continue;
                    }
                    
                    $data = $response->json();
                    $isServiceable = false;
                    $serviceabilityData = [];
                    
                    if (isset($data['data']['pincode_serviceability_data'])) {
                        $serviceabilityData = $data['data']['pincode_serviceability_data'];
                        foreach ($serviceabilityData as $center) {
                            if (isset($center['fm_serviceable']) && $center['fm_serviceable'] === true) {
                                $isServiceable = true;
                                break;
                            }
                        }
                    }
                    
                    // Cache the result (async in production)
                    try {
                        PincodeServiceabilityCache::storeCache(
                            $call['pincode'],
                            $call['weight'],
                            $isTest,
                            $serviceBy,
                            $isServiceable,
                            $serviceabilityData,
                            $data
                        );
                    } catch (\Exception $e) {
                        // Ignore cache errors
                    }
                    
                    $results[$key] = [
                        'success' => true,
                        'is_serviceable' => $isServiceable,
                        'serviceability_data' => $serviceabilityData,
                        'cached' => false,
                    ];
                } catch (\Exception $e) {
                    $results[$key] = [
                        'success' => false,
                        'is_serviceable' => false,
                        'cached' => false,
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * Create or get warehouse by pincode
     */
    public function getOrCreateWarehouse(string $pincode, array $warehouseData, int $test = 1): ?Warehouse
    {
        $isTest = (bool) $test;
        $serviceBy = 'delhivery';
        
        Log::info("getOrCreateWarehouse called", [
            'pincode' => $pincode,
            'test_param' => $test,
            'test_type' => gettype($test),
            'isTest' => $isTest,
            'serviceBy' => $serviceBy
        ]);
        
        // Step 1: Try to find warehouse with exact match (test + serviceBy)
        $warehouse = Warehouse::where('pin_code', $pincode)
            ->where('test', $isTest)
            ->where('serviceBy', $serviceBy)
            ->where('is_active', true)
            ->first();

        if ($warehouse) {
            Log::info("Warehouse found in DB (exact match) for pincode: {$pincode}, test: " . ($isTest ? 'Yes' : 'No') . ", serviceBy: {$serviceBy}");
            return $warehouse;
        }

        // Step 2: Fallback - try to find warehouse for this pincode ONLY if test/serviceBy are not set (backward compatibility)
        // This handles cases where warehouses exist but don't have test/serviceBy set yet
        // IMPORTANT: Do NOT return warehouses that have test/serviceBy set but don't match - we need separate warehouses for test/prod
        $warehouse = Warehouse::where('pin_code', $pincode)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('test')
                      ->orWhereNull('serviceBy');
            })
            ->first();

        if ($warehouse) {
            // Update existing warehouse with test/serviceBy if not set
            if (empty($warehouse->test) || empty($warehouse->serviceBy)) {
                try {
                    $warehouse->test = $isTest;
                    $warehouse->serviceBy = $serviceBy;
                    $warehouse->save();
                    Log::info("Updated existing warehouse with test/serviceBy: pincode {$pincode}, test: " . ($isTest ? 'Yes' : 'No'));
                } catch (\Exception $e) {
                    Log::warning("Failed to update warehouse test/serviceBy: " . $e->getMessage());
                }
            }
            Log::info("Warehouse found in DB (fallback - backward compatibility) for pincode: {$pincode}");
            return $warehouse;
        }

        Log::info("Warehouse not found for pincode: {$pincode}, test: " . ($isTest ? 'Yes' : 'No') . ", serviceBy: {$serviceBy}, attempting to create via API");
        Log::info("Warehouse data being sent to API: " . json_encode($warehouseData));

        // Step 3: Create new warehouse via API
        $result = $this->createWarehouse($warehouseData, $test);

        Log::info("Warehouse API creation result: " . json_encode([
            'success' => $result['success'] ?? false,
            'warehouse_id' => $result['warehouse_id'] ?? null,
            'message' => $result['message'] ?? 'No message',
            'full_result' => $result, // Log full result for debugging
        ]));

        // Check if we have warehouse_id - even if success is false, if we have warehouse_id, use it
        $warehouseId = $result['warehouse_id'] ?? null;
        $isSuccess = ($result['success'] ?? false) && $warehouseId !== null;
        
        // Fallback: if we have warehouse_id but success is false, still treat as success
        if (!$isSuccess && $warehouseId) {
            Log::warning("Warehouse creation returned success=false but warehouse_id exists: {$warehouseId}. Treating as success.");
            $isSuccess = true;
        }

        if ($isSuccess && $warehouseId) {
                Log::info("Warehouse created successfully via API. Warehouse ID: " . $warehouseId);
            
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
                    'warehouse_id' => $warehouseId,
                    'is_active' => true,
                    'test' => $isTest,
                    'serviceBy' => $serviceBy,
                ]);

                Log::info("Warehouse saved to database. DB ID: " . $warehouse->id);
                return $warehouse;
            } catch (\Exception $e) {
                Log::error("Failed to save warehouse to database: " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                // Return null but log the error
                return null;
            }
        } else {
            $errorMsg = $result['message'] ?? 'Unknown error';
            $errorData = $result['data'] ?? [];
            
            // Check error in multiple places - API might return error in different structures
            $errorMessageFromData = null;
            if (isset($errorData['error'])) {
                if (is_array($errorData['error'])) {
                    $errMsg = $errorData['error']['message'] ?? null;
                    if ($errMsg !== null) {
                        // Handle array of error messages (like ['Transaction Failed: ...'])
                        if (is_array($errMsg)) {
                            $errorMessageFromData = is_array($errMsg[0] ?? null) ? json_encode($errMsg) : (string) ($errMsg[0] ?? json_encode($errMsg));
                        } else {
                            $errorMessageFromData = (string) $errMsg;
                        }
                    } else {
                        $errorMessageFromData = json_encode($errorData['error']);
                    }
                } else {
                    $errorMessageFromData = (string) $errorData['error'];
                }
            }
            
            // Use error message from data if available, otherwise use result message
            $finalErrorMsg = $errorMessageFromData ?? $errorMsg;
            
            // Convert to string if it's an array
            if (is_array($finalErrorMsg)) {
                // If it's an array with a string at index 0, extract it
                if (isset($finalErrorMsg[0]) && is_string($finalErrorMsg[0])) {
                    $finalErrorMsg = $finalErrorMsg[0];
                } else {
                    $finalErrorMsg = json_encode($finalErrorMsg);
                }
            }
            
            // Ensure it's a string for string operations
            $errorMsgString = is_string($finalErrorMsg) ? $finalErrorMsg : (string) $finalErrorMsg;
            
            // Check if error message indicates warehouse already exists
            $alreadyExists = stripos($errorMsgString, 'already exists') !== false || 
                            stripos($errorMsgString, 'CLIENT_STORES_CREATE') !== false ||
                            (stripos($errorMsgString, 'warehouse') !== false && stripos($errorMsgString, 'exists') !== false);
            
            Log::info("Checking if warehouse already exists", [
                'error_msg_string' => $errorMsgString,
                'already_exists' => $alreadyExists,
                'pincode' => $pincode,
                'test' => $test,
                'isTest' => $isTest,
                'isTest_bool' => $isTest ? 'Yes' : 'No',
                'serviceBy' => $serviceBy
            ]);
            
            if ($alreadyExists) {
                Log::info("Warehouse already exists in Delhivery for pincode: {$pincode}, test: " . ($isTest ? 'Yes' : 'No') . ". Creating/updating record in our DB with test={$isTest}, serviceBy={$serviceBy}.");
                
                // Warehouse exists in Delhivery but not in our DB - create record anyway
                // Extract warehouse name from error message if possible
                $warehouseName = $warehouseData['name'] ?? "Warehouse_{$pincode}";
                if (preg_match("/name:\s*([^\s]+)/i", $errorMsgString, $matches)) {
                    $warehouseName = $matches[1];
                    Log::info("Extracted warehouse name from error message: {$warehouseName}");
                }
                
                try {
                    $warehouse = Warehouse::updateOrCreate(
                        [
                            'pin_code' => $pincode,
                            'test' => $isTest,
                            'serviceBy' => $serviceBy,
                        ],
                        [
                            'city' => $warehouseData['city'] ?? '',
                            'state' => $warehouseData['state'] ?? '',
                            'country' => $warehouseData['country'] ?? 'India',
                            'name' => $warehouseName,
                            'address_details' => $warehouseData['address_details'] ?? [],
                            'business_hours' => $warehouseData['business_hours'] ?? [],
                            'pick_up_hours' => $warehouseData['pick_up_hours'] ?? [],
                            'pick_up_days' => $warehouseData['pick_up_days'] ?? [],
                            'business_days' => $warehouseData['business_days'] ?? [],
                            'ret_address' => $warehouseData['ret_address'] ?? [],
                            'warehouse_id' => null, // Will be updated later when we fetch it
                            'is_active' => true,
                        ]
                    );
                    
                    Log::info("Warehouse record created/updated in DB (exists in Delhivery). DB ID: " . $warehouse->id . ", Name: {$warehouseName}. Note: warehouse_id will be fetched later or use pickup_location_name in manifest creation.");
                    
                    // Return the warehouse even without warehouse_id - the manifest creation can use name instead
                    return $warehouse;
                } catch (\Exception $e) {
                    Log::error("Failed to save warehouse record for existing Delhivery warehouse: " . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Check if warehouse might already exist in Delhivery but not in our DB
            // Sometimes API returns success but with a different structure, or warehouse already exists
            $warehouseIdFromError = null;
            if (isset($errorData['data']['result']['id'])) {
                $warehouseIdFromError = $errorData['data']['result']['id'];
            } elseif (isset($errorData['result']['id'])) {
                $warehouseIdFromError = $errorData['result']['id'];
            }
            
            // If we got a warehouse ID even though success=false, try to use it
            if ($warehouseIdFromError) {
                Log::warning("Warehouse creation returned error but warehouse_id found: {$warehouseIdFromError}. Attempting to use it.");
                
                try {
                    // Try to create warehouse record with the ID we got
                    $warehouse = Warehouse::updateOrCreate(
                        [
                            'pin_code' => $pincode,
                            'test' => $isTest,
                            'serviceBy' => $serviceBy,
                        ],
                        [
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
                            'warehouse_id' => $warehouseIdFromError,
                            'is_active' => true,
                        ]
                    );
                    
                    Log::info("Warehouse saved to database using warehouse_id from error response. DB ID: " . $warehouse->id);
                    return $warehouse;
                } catch (\Exception $e) {
                    Log::error("Failed to save warehouse from error response: " . $e->getMessage());
                }
            }
            
            Log::error("Warehouse creation failed via API. Error: {$errorMsg}", [
                'pincode' => $pincode,
                'test' => $isTest,
                'serviceBy' => $serviceBy,
                'api_response' => $errorData,
                'warehouse_data_sent' => $warehouseData,
                'warehouse_id_from_error' => $warehouseIdFromError,
                'already_exists_detected' => $alreadyExists,
            ]);
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
            
            // Log raw response for debugging
            Log::info('Delhivery Warehouse Creation - Raw Response', [
                'status_code' => $response->status(),
                'response_successful' => $response->successful(),
                'raw_response' => $response->body(),
            ]);

            // Check for success - warehouse ID is in data.result.id according to API response
            // Response structure: { "data": { "success": true, "result": { "id": "..." } }, "success": true }
            $warehouseId = $data['data']['result']['id'] ?? $data['data']['id'] ?? $data['id'] ?? $data['warehouse_id'] ?? $data['data']['warehouse_id'] ?? null;
            
            // Check success flags - API can have success at root level OR in data.success
            $rootSuccess = $data['success'] ?? false;
            $dataSuccess = $data['data']['success'] ?? false;
            $apiSuccess = $rootSuccess || $dataSuccess;
            
            // Success if: (HTTP status is 2xx OR API says success) AND we have warehouse ID
            // Be lenient - if API says success and we have warehouse_id, treat as success even if HTTP status is not 2xx
            $isSuccess = ($response->successful() || $apiSuccess) && $warehouseId !== null;
            
            Log::info('Delhivery Warehouse Creation Response Parsing', [
                'status_code' => $response->status(),
                'http_successful' => $response->successful(),
                'root_success' => $rootSuccess,
                'data_success' => $dataSuccess,
                'api_success' => $apiSuccess,
                'warehouse_id' => $warehouseId,
                'is_success' => $isSuccess,
                'response_keys' => array_keys($data),
                'data_keys' => isset($data['data']) ? array_keys($data['data']) : [],
                'result_keys' => isset($data['data']['result']) ? array_keys($data['data']['result']) : [],
            ]);

            // Extract error message - handle both string and array formats
            $errorMsg = null;
            if (!$isSuccess) {
                if (isset($data['error'])) {
                    if (is_array($data['error'])) {
                        $msg = $data['error']['message'] ?? null;
                        if ($msg !== null) {
                            // Handle array of error messages (like ['Transaction Failed: ...'])
                            if (is_array($msg)) {
                                $errorMsg = is_array($msg[0] ?? null) ? json_encode($msg) : (string) ($msg[0] ?? json_encode($msg));
                            } else {
                                $errorMsg = (string) $msg;
                            }
                        } else {
                            $errorMsg = json_encode($data['error']);
                        }
                    } else {
                        $errorMsg = is_array($data['error']) ? json_encode($data['error']) : (string) $data['error'];
                    }
                } else {
                    $msg = $data['message'] ?? 'Warehouse creation failed';
                    $errorMsg = is_array($msg) ? (is_array($msg[0] ?? null) ? json_encode($msg) : (string) ($msg[0] ?? json_encode($msg))) : (string) $msg;
                }
                
                // Log detailed error information
                Log::error('Delhivery Warehouse Creation Failed - Detailed Error', [
                    'status_code' => $response->status(),
                    'response_body' => $data,
                    'error_message' => $errorMsg,
                    'warehouse_id_found' => $warehouseId,
                    'api_success_flag' => $apiSuccess,
                    'payload_sent' => $payload,
                ]);
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

            // Even if isSuccess is false, if we have a warehouse_id, return it
            // Sometimes API returns warehouse_id even with error messages
            if (!$isSuccess && $warehouseId) {
                Log::warning("Warehouse creation marked as failed but warehouse_id exists: {$warehouseId}. Returning success anyway.");
                return [
                    'success' => true,
                    'warehouse_id' => $warehouseId,
                    'message' => 'Warehouse created successfully (warehouse_id found in response)',
                    'data' => $data,
                ];
            }
            
            // Extract error message properly for return
            $returnMessage = 'Warehouse created successfully';
            if (!$isSuccess) {
                if (isset($data['error']['message'])) {
                    $errMsg = $data['error']['message'];
                    $returnMessage = is_array($errMsg) ? (is_array($errMsg[0] ?? null) ? json_encode($errMsg) : (string) ($errMsg[0] ?? json_encode($errMsg))) : (string) $errMsg;
                } elseif (isset($data['error'])) {
                    $returnMessage = is_array($data['error']) ? json_encode($data['error']) : (string) $data['error'];
                } elseif (isset($data['message'])) {
                    $returnMessage = is_array($data['message']) ? (is_array($data['message'][0] ?? null) ? json_encode($data['message']) : (string) ($data['message'][0] ?? json_encode($data['message']))) : (string) $data['message'];
                } else {
                    $returnMessage = 'Warehouse creation failed';
                }
            }
            
            return [
                'success' => $isSuccess,
                'warehouse_id' => $warehouseId,
                'message' => $returnMessage,
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
            Log::info("Delhivery manifest created with job_id: {$jobId}. Waiting 1 second before polling status...");
            
            // Optimized: Reduced initial wait and retries for faster response
            $isProduction = !$test && app()->environment('production');
            $maxRetries = $isProduction ? 2 : 3; // Fewer retries in production
            $retryDelay = $isProduction ? 1 : 2; // Faster retries in production
            
            // Reduced initial wait - only 0.5s in production, 1s in test
            if (!$isProduction) {
                usleep(500000); // 0.5 seconds
            }
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                if ($attempt > 1) {
                    // Use 20 seconds delay for each retry
                    usleep(20 * 1000000); // 20 seconds delay for each retry
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
            'master_waybill' => $result['master_waybill'] ?? null,
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
    public function downloadAndStoreLabels(string $lrNumber, array $labelUrls, string $bookingId = null, int $test = 1, string $invoiceNo = null): ?string
    {
        // Store original time limit to restore later
        $originalTimeLimit = ini_get('max_execution_time');
        
        try {
            if (empty($labelUrls)) {
                Log::warning("No label URLs provided for LR: {$lrNumber}");
                return null;
            }

            // Increase max execution time for downloading multiple labels
            set_time_limit(300); // 5 minutes for downloading and processing multiple labels

            // Get JWT token for authentication (label URLs might require auth)
            $jwt = $this->authenticate($test);

            // Download all labels in parallel for faster processing
            Log::info("Downloading " . count($labelUrls) . " labels in parallel for LR: {$lrNumber}");
            $downloadedImages = [];
            
            // Execute all requests concurrently using HTTP pool
            $responses = Http::pool(function ($pool) use ($labelUrls, $jwt) {
                $requests = [];
                foreach ($labelUrls as $index => $labelUrl) {
                    $requests["label_{$index}"] = $pool->as("label_{$index}")
                        ->withHeaders([
                            'Authorization' => "Bearer {$jwt}",
                        ])
                        ->timeout(60)
                        ->get($labelUrl);
                }
                return $requests;
            });
            
            // Handle any failed requests by retrying individually without auth
            foreach ($labelUrls as $index => $labelUrl) {
                $key = "label_{$index}";
                if (!isset($responses[$key]) || ($responses[$key]->status() === 401 || $responses[$key]->status() === 403)) {
                    // Retry without auth
                    try {
                        $responses[$key] = Http::timeout(60)->get($labelUrl);
                    } catch (\Exception $e) {
                        Log::warning("Failed to retry label " . ($index + 1) . " without auth: " . $e->getMessage());
                    }
                }
            }
            
            // Process responses
            foreach ($labelUrls as $index => $labelUrl) {
                $key = "label_{$index}";
                try {
                    if (!isset($responses[$key])) {
                        Log::warning("No response for label " . ($index + 1) . " from: {$labelUrl}");
                        continue;
                    }
                    
                    $response = $responses[$key];
                    
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
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    Log::error("Connection timeout/error downloading label " . ($index + 1) . ": " . $e->getMessage());
                    // Continue with next label instead of failing completely
                } catch (\Exception $e) {
                    Log::error("Error downloading label " . ($index + 1) . ": " . $e->getMessage());
                    // Continue with next label instead of failing completely
                }
            }
            
            Log::info("Completed parallel download. Successfully downloaded " . count($downloadedImages) . " out of " . count($labelUrls) . " labels");

            if (empty($downloadedImages)) {
                Log::error("No labels were successfully downloaded for LR: {$lrNumber}");
                return null;
            }

            // Separate PDFs and images
            $pdfLabels = [];
            $imageLabels = [];
            
            foreach ($downloadedImages as $label) {
                if ($label['extension'] === 'pdf') {
                    $pdfLabels[] = $label;
                } else {
                    $imageLabels[] = $label;
                }
            }
            
            // Generate filename
            $filename = 'shipping_label_' . $lrNumber . '_' . time() . '.pdf';
            
            // Define the destination path (same as BlueDart)
            $destinationPath = public_path('storage/pdfs/shipping-labels/' . date('Y') . '/' . date('m'));
            
            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            $filePath = $destinationPath . '/' . $filename;
            
            // Combine all labels into a single PDF
            try {
                // If we have PDFs, merge them first
                $mergedPdfContent = null;
                if (!empty($pdfLabels)) {
                    $mergedPdfContent = $this->mergePdfLabels($pdfLabels, $invoiceNo);
                }
                
                // If we have images, create a PDF from them
                $imagePdfContent = null;
                if (!empty($imageLabels)) {
                    $imagePdfContent = $this->createPdfFromImages($imageLabels, $invoiceNo);
                }
                
                // Combine PDF and image PDFs if both exist
                if ($mergedPdfContent && $imagePdfContent) {
                    // Save both to temp files and merge
                    $tempPdf1 = tempnam(sys_get_temp_dir(), 'pdf1_') . '.pdf';
                    $tempPdf2 = tempnam(sys_get_temp_dir(), 'pdf2_') . '.pdf';
                    file_put_contents($tempPdf1, $mergedPdfContent);
                    file_put_contents($tempPdf2, $imagePdfContent);
                    
                    $finalPdf = $this->mergePdfFiles([$tempPdf1, $tempPdf2]);
                    file_put_contents($filePath, $finalPdf);
                    
                    // Clean up temp files
                    @unlink($tempPdf1);
                    @unlink($tempPdf2);
                } elseif ($mergedPdfContent) {
                    // Only PDFs
                    file_put_contents($filePath, $mergedPdfContent);
                } elseif ($imagePdfContent) {
                    // Only images
                    file_put_contents($filePath, $imagePdfContent);
                } else {
                    Log::error("No valid labels to combine for LR: {$lrNumber}");
                    return null;
                }
                
            } catch (\Exception $e) {
                Log::error("Error combining labels into PDF: " . $e->getMessage());
                // Fallback: save first label as before
                $firstLabel = $downloadedImages[0];
                $labelContent = $firstLabel['data'];
                $extension = $firstLabel['extension'];
                $fallbackFilename = 'shipping_label_' . $lrNumber . '_' . time() . '.' . $extension;
                $fallbackPath = $destinationPath . '/' . $fallbackFilename;
                file_put_contents($fallbackPath, $labelContent);
                $filePath = $fallbackPath;
                $filename = $fallbackFilename;
            }
            
            // Verify file was saved
            if (!file_exists($filePath)) {
                Log::error("Failed to save Delhivery label file: {$filePath}");
                return null;
            }
            
            // Return the public URL
            $baseUrl = 'https://track.sbexpresscargo.com';
            $relativePath = 'pdfs/shipping-labels/' . date('Y') . '/' . date('m') . '/' . $filename;
            $url = $baseUrl . '/storage/' . $relativePath;
            
            Log::info("Delhivery combined label saved successfully: {$url} (Size: " . filesize($filePath) . " bytes, Labels: " . count($downloadedImages) . ")");
            
            return $url;
            
        } catch (\Exception $e) {
            Log::error('Delhivery Download and Store Labels Exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        } finally {
            // Always restore original time limit
            if ($originalTimeLimit !== false && $originalTimeLimit !== '') {
                set_time_limit((int)$originalTimeLimit);
            }
        }
    }

    /**
     * Merge multiple PDF labels into a single PDF
     */
    private function mergePdfLabels(array $pdfLabels, string $invoiceNo = null): string
    {
        $pdf = new Fpdi();
        $isFirstPage = true;
        
        foreach ($pdfLabels as $index => $label) {
            try {
                $tempFile = tempnam(sys_get_temp_dir(), 'pdf_label_') . '.pdf';
                file_put_contents($tempFile, $label['data']);
                
                $pageCount = $pdf->setSourceFile($tempFile);
                
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    
                    // Add a page (always needed, even for first page)
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                    
                    // Add invoice number on the first page if provided
                    if ($isFirstPage && $invoiceNo) {
                        $pdf->SetFont('Arial', 'B', 10);
                        $pdf->SetTextColor(0, 0, 0);
                        // Add invoice number at bottom right corner
                        $pdf->SetXY($size['width'] - 60, $size['height'] - 15);
                        $pdf->Cell(0, 0, 'Inv No: ' . $invoiceNo, 0, 0, 'R');
                    }
                    
                    $isFirstPage = false;
                }
                
                @unlink($tempFile);
            } catch (\Exception $e) {
                Log::error("Error merging PDF label " . ($index + 1) . ": " . $e->getMessage());
            }
        }
        
        return $pdf->Output('S');
    }
    
    /**
     * Merge multiple PDF files into a single PDF
     */
    private function mergePdfFiles(array $pdfFiles): string
    {
        $pdf = new Fpdi();
        
        foreach ($pdfFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            try {
                $pageCount = $pdf->setSourceFile($file);
                
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            } catch (\Exception $e) {
                Log::error("Error merging PDF file {$file}: " . $e->getMessage());
            }
        }
        
        return $pdf->Output('S');
    }
    
    /**
     * Create a PDF from multiple image labels
     */
    private function createPdfFromImages(array $imageLabels, string $invoiceNo = null): string
    {
        $html = '<!DOCTYPE html><html><head><style>
            @page { margin: 0; size: auto; }
            body { margin: 0; padding: 0; }
            .label-page { page-break-after: always; width: 100%; position: relative; }
            .label-page:last-child { page-break-after: auto; }
            .label-page img { width: 100%; height: auto; display: block; }
            .invoice-no { position: absolute; bottom: 10px; right: 10px; font-family: Arial, sans-serif; font-size: 10px; font-weight: bold; color: #000; background: rgba(255,255,255,0.8); padding: 2px 5px; }
        </style></head><body>';
        
        foreach ($imageLabels as $index => $label) {
            $base64Data = base64_encode($label['data']);
            $mimeType = 'image/' . ($label['extension'] === 'jpg' ? 'jpeg' : $label['extension']);
            
            $html .= '<div class="label-page">';
            $html .= '<div class="invoice-no">Inv No: ' . $invoiceNo . '</div>';
            $html .= '<img src="data:' . $mimeType . ';base64,' . $base64Data . '" alt="Label ' . ($index + 1) . '">';
            // Add invoice number on first page only

            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->output();
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

    /**
     * Cancel Waybill/LR
     * Endpoint: DELETE /lrn/cancel/{lr_number}
     * 
     * @param string $waybillNumber LR number to cancel
     * @param mixed $bookingId Optional booking ID
     * @param int $test Test mode (1 for test, 0 for production)
     * @return array
     */
    public function cancelWaybill(string $waybillNumber, $bookingId = null, int $test = 1): array
    {
        $jwt = $this->authenticate($test);
        $baseUrl = $test ? $this->baseUrl : $this->baseUrlProd;
        $endpoint = "/lrn/cancel/{$waybillNumber}";

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$jwt}",
                'Content-Type' => 'application/json',
            ])->delete($baseUrl . $endpoint);

            $data = $response->json();

            $isSuccess = $response->successful() && 
                        (isset($data['success']) && $data['success'] === true);

            // Extract error message if failed
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
                    $msg = $data['message'] ?? 'Waybill cancellation failed';
                    $errorMsg = is_array($msg) ? json_encode($msg) : (string) $msg;
                }
            }

            $this->logApiCall([
                'booking_id'      => $bookingId,
                'api_endpoint'    => $endpoint,
                'request_payload' => ['lr_number' => $waybillNumber],
                'response_data'   => $data,
                'status_code'     => $response->status(),
                'is_success'      => $isSuccess,
                'awb_number'      => $waybillNumber,
                'error_message'   => $errorMsg,
            ]);

            Log::info('Delhivery Cancel Waybill Response', [
                'lr_number' => $waybillNumber,
                'status_code' => $response->status(),
                'success' => $isSuccess,
                'message' => $data['message'] ?? null,
            ]);

            return [
                'success' => $isSuccess,
                'message' => $isSuccess ? 'Waybill cancelled successfully' : ($errorMsg ?? 'Waybill cancellation failed'),
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Delhivery LTL Cancel Waybill Exception: ' . $e->getMessage());
            
            $this->logApiCall([
                'booking_id'      => $bookingId,
                'api_endpoint'    => $endpoint,
                'request_payload' => ['lr_number' => $waybillNumber],
                'response_data'   => [],
                'status_code'     => 500,
                'is_success'      => false,
                'awb_number'      => $waybillNumber,
                'error_message'   => $e->getMessage(),
            ]);

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
