<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\booking;
use App\Models\bookinglog;
use App\Models\ApiRequestLog;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\BlueDartService;
use App\Services\DelhiveryLtlService; 

class UnicommerceApiController extends Controller
{
    protected $bluedartService;
    protected $delhiveryService;

    public function __construct(BlueDartService $bluedartService, DelhiveryLtlService $delhiveryService)
    {
        $this->bluedartService = $bluedartService;
        $this->delhiveryService = $delhiveryService;
    }
    /**
     * Generate authentication token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function authToken(Request $request): JsonResponse
    {
        // Validate the request payload
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'INVALID_CREDENTIALS',
                'errors' => $validator->errors()
            ], 400);
        }

        $username = $request->input('username');
        $password = $request->input('password');

        // Authenticate user credentials
        $webhook = $this->authenticateUser($username, $password);
        
        if ($webhook) {
            // Generate unique token and save it to the webhook record
            $token = $this->generateToken($webhook);
            
            return response()->json([
                'status' => 'SUCCESS',
                'token' => $token
            ], 200);
        }

        return response()->json([
            'status' => 'INVALID_CREDENTIALS'
        ], 401);
    }

    /**
     * Authenticate user credentials using Webhook model
     * 
     * @param string $username (api_key)
     * @param string $password (api_secret)
     * @return Webhook|null
     */
    private function authenticateUser(string $username, string $password): ?Webhook
    {
        // Find webhook by api_key (username) and check if active
        $webhook = Webhook::where('api_key', $username)
                         ->where('is_active', 1)
                         ->first();

        if (!$webhook) {
            return null;
        }

        // Verify api_secret (password)
        // Assuming api_secret is stored as plain text, but you should hash it
        if ($webhook->api_secret === $password) {
            return $webhook;
        }

        // If api_secret is hashed, use this instead:
        // if (Hash::check($password, $webhook->api_secret)) {
        //     return $webhook;
        // }

        return null;
    }

    /**
     * Generate unique authentication token and save to webhook record
     * 
     * @param Webhook $webhook
     * @return string
     */
    private function generateToken(Webhook $webhook): string
    {
        // Generate a unique token
        $timestamp = time();
        $randomString = Str::random(32);
        
        // Create a unique token combining various elements
        $token = hash('sha256', $webhook->api_key . $timestamp . $randomString);
       
        // Save token to the webhook record
        $webhook->update([
            'token' =>  $token,
            'updated_at' => now()
        ]);
         $token ="Bearer {$token}";
        return $token;
    }

    /**
     * Verify if a token is valid and return associated webhook
     * 
     * @param string $token
     * @return Webhook|null
     */
    public function verifyToken(string $token): ?Webhook
    {
        // Explicitly select all columns including service_provider
        $webhook = Webhook::where('token', $token)
                     ->where('is_active', 1)
                     ->select('*') // Ensure all columns are selected
                     ->first();
        
        return $webhook;
    }

    /**
     * Log API request and response
     * 
     * @param Request $request
     * @param JsonResponse $response
     * @param string $apiType
     * @param float $executionTime
     * @param string|null $waybillNumber
     * @return void
     */
    private function logApiRequest(Request $request, JsonResponse $response, string $apiType, float $executionTime, ?string $waybillNumber = null): void
    {
        try {
            ApiRequestLog::create([
                'endpoint' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'request_data' => $request->all(),
                'response_data' => json_decode($response->getContent(), true),
                'status_code' => $response->getStatusCode(),
                'execution_time' => $executionTime,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'waybill_number' => $waybillNumber,
                'api_type' => $apiType,
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the main flow
            \Log::error('Failed to log API request: ' . $e->getMessage());
        }
    }
    /**
     * Generate PDF shipping label for a booking
     * 
     * @param booking $booking
     * @return string|null
     */
         public function testShippingLabel(Request $request, $waybill)
{
    try {
        // Find booking by waybill no
        $booking = booking::where('forwordingno', $waybill)->first();

        if (!$booking) {
            return response()->json([
                'status' => 'FAILED',
                'reason' => 'NOT_FOUND',
                'message' => "Booking with waybill {$waybill} not found."
            ], 404);
        }
        

        // Generate PDF
        $shippingLabelUrl = $this->generateShippingLabelPdf($booking);

        if (!$shippingLabelUrl) {
            return response()->json([
                'status' => 'FAILED',
                'reason' => 'PDF_ERROR',
                'message' => 'Failed to generate shipping label.'
            ], 500);
        }

        return response()->json([
            'status' => 'SUCCESS',
            'waybill' => $waybill,
            'shippingLabel' => $shippingLabelUrl
        ], 200);

    } catch (\Exception $e) {
        \Log::error('Test shipping label failed: ' . $e->getMessage());
        return response()->json([
            'status' => 'FAILED',
            'reason' => 'SYSTEM_ERROR',
            'message' => $e->getMessage()
        ], 500);
    }
}

     private function generateShippingLabelPdf(booking $booking): ?string
    {
        try {
            \Log::info('Starting PDF generation for booking: ' . $booking->forwordingno);
            
            // Generate PDF using the shipping label template
            $pdf = Pdf::loadView('api.shipping-label', compact('booking'));
            
            // Set paper size to match label dimensions (4x6 inches)
            $pdf->setPaper([0, 0, 288, 432], 'portrait'); // 4x6 inches in points
            
            // Generate unique filename
            $filename = 'shipping_label_' . $booking->forwordingno . '_' . time() . '.pdf';
            
            // Define the destination path using public_path like couriercontroller
            $destinationPath = public_path('storage/pdfs/shipping-labels/' . date('Y') . '/' . date('m'));
            \Log::info('Creating directory: ' . $destinationPath);
            
            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                $created = mkdir($destinationPath, 0777, true);
                \Log::info('Directory created: ' . ($created ? 'true' : 'false'));
            } else {
                \Log::info('Directory already exists');
            }
            
            // Save PDF to the destination path
            $filePath = $destinationPath . '/' . $filename;
            \Log::info('Saving PDF to: ' . $filePath);
            
            // Save PDF content to file
            file_put_contents($filePath, $pdf->output());
            
            // Check if file exists
            $exists = file_exists($filePath);
            \Log::info('File exists after save: ' . ($exists ? 'true' : 'false'));
            
            // Return the public URL with the correct base URL
            $baseUrl = 'https://track.sbexpresscargo.com';
            $relativePath = 'pdfs/shipping-labels/' . date('Y') . '/' . date('m') . '/' . $filename;
            $url = $baseUrl . '/storage/' . $relativePath;
            \Log::info('Generated URL: ' . $url);
            
            return $url;
            
        } catch (\Exception $e) {
            // Log error and return null
            \Log::error('PDF generation failed: ' . $e->getMessage());
            \Log::error('PDF generation stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**

    /**
     * Middleware method to authenticate API requests using token
     * 
     * @param Request $request
     * @return JsonResponse|null
     */
    protected function authenticateRequest(Request $request): ?JsonResponse
{
    $token = $request->header('Authorization');

    // Remove 'Bearer ' prefix if present
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }

    $webhook = $this->verifyToken($token);

    if (!$webhook) {
        return response()->json([
            'status' => 'INVALID_TOKEN',
            'message' => 'Invalid or expired token'
        ], 401);
    }

    // ✅ Store clientid (or full webhook) in request for later use
    $request->attributes->set('clientid', $webhook->id);
    $request->attributes->set('test', $webhook->test);
    $request->attributes->set('webhook', $webhook);

    return null; // Token is valid
}

    public function waybill(Request $request): JsonResponse
{
    $startTime = microtime(true);

    // Step 1: Authenticate
    $authError = $this->authenticateRequest($request);
    if ($authError) {
        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $authError, 'waybill_create', $executionTime);
        return $authError;
    }

    // Step 2: Validate request
    $validator = Validator::make($request->all(), [
        'serviceType' => 'required|string',
        'handOverMode' => 'required|string',
        'returnShipmentFlag' => 'required|string|in:true,false',
        'Shipment.code' => 'required|string',
        'Shipment.SaleOrderCode' => 'required|string',
        'Shipment.orderCode' => 'required|string',
        'Shipment.invoiceCode' => 'required|string',
        'Shipment.orderDate' => 'required|string',
        'Shipment.weight' => 'required|numeric',
        'Shipment.length' => 'required|string',
        'Shipment.height' => 'required|string',
        'Shipment.breadth' => 'required|string',
        'Shipment.items' => 'required|array|min:1',
        'deliveryAddressDetails.name' => 'required|string',
        'deliveryAddressDetails.phone' => 'required|string',
        'deliveryAddressDetails.address1' => 'required|string',
        'deliveryAddressDetails.pincode' => 'required|string',
        'deliveryAddressDetails.city' => 'required|string',
        'deliveryAddressDetails.state' => 'required|string',
        'deliveryAddressDetails.country' => 'required|string',
        'pickupAddressDetails.name' => 'required|string',
        'pickupAddressDetails.phone' => 'required|string',
        'pickupAddressDetails.address1' => 'required|string',
        'pickupAddressDetails.pincode' => 'required|string',
        'pickupAddressDetails.city' => 'required|string',
        'pickupAddressDetails.state' => 'required|string',
        'pickupAddressDetails.country' => 'required|string',
        'currencyCode' => 'required|string|in:INR',
        'paymentMode' => 'required|string|in:COD,PREPAID',
        'totalAmount' => 'required|numeric',
        'collectableAmount' => 'required|numeric',
         'Shipment.customField' => 'nullable|array',
        'Shipment.customField.*.name' => 'required_with:Shipment.customField|string',
        'Shipment.customField.*.value' => 'required_with:Shipment.customField|string',
    ]);

    if ($validator->fails()) {
        $response = response()->json([
            'status' => 'FAILED',
            'reason' => 'WRONG INPUT',
            'errors' => $validator->errors()
        ], 400);

        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
        return $response;
    }
     $payload = $request->all();

    // ✅ Extract invoice_link from Shipment.customField if present
    $invoiceLink = null;
    if (!empty($payload['Shipment']['customField']) && is_array($payload['Shipment']['customField'])) {
        foreach ($payload['Shipment']['customField'] as $field) {
            if (isset($field['name']) && $field['name'] === 'invoice_link') {
                $invoiceLink = $field['value'] ?? null;
                break;
            }
        }
    }
    if ($payload['returnShipmentFlag'] === 'true'){
       $invoiceLink = 'R' . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    }

    // ✅ Extra validation: required, digits-only, max 10 digits
    if (empty($invoiceLink) || mb_strlen($invoiceLink) > 10) {
        $response = response()->json([
            'status' => 'FAILED',
            'reason' => 'INVALID_INVOICE_LINK',
            'message' => 'Shipment.customField.invoice_link is required, and must be at most 10 digits.'
        ], 400);

        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
        return $response;
    }

    try {
        // Get webhook to determine service provider
        $webhook = $request->attributes->get('webhook');
        
        // Re-fetch from database to ensure we have the latest service_provider value
        // Use where() instead of find() to ensure fresh query
        $webhook = Webhook::where('id', $webhook->id)
                         ->where('is_active', 1)
                         ->first();
        
        if (!$webhook) {
            throw new \Exception('Webhook not found after authentication');
        }
        
        // Get service provider directly from attributes to avoid any accessor issues
        $serviceProviderRaw = $webhook->getAttribute('service_provider');
        $serviceProvider = trim(strtolower($serviceProviderRaw ?? 'bluedart')); // Default to bluedart if not set

        // Debug logging - write to both log and error_log for visibility
        \Log::info('=== WAYBILL ROUTING DEBUG ===');
        \Log::info('Webhook ID: ' . $webhook->id);
        \Log::info('Service Provider (raw from DB): ' . var_export($serviceProviderRaw, true));
        \Log::info('Service Provider (after processing): ' . var_export($serviceProvider, true));
        \Log::info('Comparison (serviceProvider === delhivery): ' . var_export($serviceProvider === 'delhivery', true));
        
        error_log('WAYBILL ROUTING: service_provider=' . var_export($serviceProviderRaw, true) . ', processed=' . var_export($serviceProvider, true) . ', routing=' . ($serviceProvider === 'delhivery' ? 'DELHIVERY' : 'BLUEDART'));

        // Route to appropriate service based on webhook configuration
        if ($serviceProvider === 'delhivery') {
            \Log::info('✅ ROUTING TO DELHIVERY SERVICE');
            error_log('ROUTING TO DELHIVERY');
            return $this->handleDelhiveryWaybill($request, $payload, $invoiceLink, $startTime);
        } else {
            \Log::info('✅ ROUTING TO BLUEDART SERVICE (value: ' . $serviceProvider . ')');
            error_log('ROUTING TO BLUEDART - service_provider was: ' . var_export($serviceProviderRaw, true));
            return $this->handleBlueDartWaybill($request, $payload, $invoiceLink, $startTime);
        }
    } catch (\Exception $e) {
        $response = response()->json([
            'status' => 'FAILED',
            'reason' => 'SYSTEM_ERROR',
            'message' => $e->getMessage()
        ], 500);

        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $response, 'waybill_create', $executionTime);

        return $response;
    }
}

    /**
     * Handle BlueDart waybill creation
     */
    private function handleBlueDartWaybill(Request $request, array $payload, string $invoiceLink, float $startTime): JsonResponse
    {
        try {
            /**
             * ✅ BlueDart Mapper - Complete mapping according to BlueDart API requirements
             */
             
            $invoice = $payload['Shipment']['invoiceCode'];

            // Look for pattern like INS#### or ######IN
            if (preg_match('/(INS\d+|\d+IN)/i', $invoice, $matches)) {
                $code = $matches[1];
            } else {
                $code = null; // nothing matched
            }
            $mappedRequest = [
            'Consignee' => [
                'AvailableDays' => '',
                'AvailableTiming' => '',
                'ConsigneeAddress1' => $payload['deliveryAddressDetails']['address1'],
                'ConsigneeAddress2' => $payload['deliveryAddressDetails']['address2'] ?? '',
                'ConsigneeAddress3' => '',
                'ConsigneeAddressType' => '',
                'ConsigneeAddressinfo' => '',
                'ConsigneeAttention' => '',
                'ConsigneeEmailID' => $payload['deliveryAddressDetails']['email'] ?? '',
                'ConsigneeFullAddress' => '',
                'ConsigneeGSTNumber' => $payload['deliveryAddressDetails']['gstin'] ?? '',
                'ConsigneeLatitude' => isset($payload['deliveryAddressDetails']['latitude']) && $payload['deliveryAddressDetails']['latitude'] !== '' 
    ? number_format((float) $payload['deliveryAddressDetails']['latitude'], 6, '.', '') 
    : null,

'ConsigneeLongitude' => isset($payload['deliveryAddressDetails']['longitude']) && $payload['deliveryAddressDetails']['longitude'] !== '' 
    ? number_format((float) $payload['deliveryAddressDetails']['longitude'], 6, '.', '') 
    : null,
                'ConsigneeMaskedContactNumber' => '',
                'ConsigneeMobile' => $payload['deliveryAddressDetails']['phone'],
                'ConsigneeName' => $payload['deliveryAddressDetails']['name'],
                'ConsigneePincode' => $payload['deliveryAddressDetails']['pincode'],
                'ConsigneeTelephone' => $payload['deliveryAddressDetails']['alternatePhone'] ?? '',
            ],
            'Returnadds' => [
                'ManifestNumber' => '',
                'ReturnAddress1' => $payload['returnAddressDetails']['address1'] ?? '',
                'ReturnAddress2' => $payload['returnAddressDetails']['address2'] ?? '',
                'ReturnAddress3' => '',
                'ReturnAddressinfo' => '',
                'ReturnContact' => $payload['returnAddressDetails']['name'] ?? '',
                'ReturnEmailID' => $payload['returnAddressDetails']['email'] ?? '',
                'ReturnLatitude' => isset($payload['returnAddressDetails']['latitude']) && $payload['returnAddressDetails']['latitude'] !== '' 
    ? number_format((float) $payload['returnAddressDetails']['latitude'], 6, '.', '') 
    : null,

'ReturnLongitude' => isset($payload['returnAddressDetails']['longitude']) && $payload['returnAddressDetails']['longitude'] !== '' 
    ? number_format((float) $payload['returnAddressDetails']['longitude'], 6, '.', '') 
    : null,
                'ReturnMaskedContactNumber' => '',
                'ReturnMobile' => $payload['returnAddressDetails']['phone'] ?? '',
                'ReturnPincode' => $payload['returnAddressDetails']['pincode'] ?? '',
                'ReturnTelephone' => '',
            ],
            'Services' => [
                'AWBNo' => '',
                'ActualWeight' => $payload['Shipment']['weight'] / 1000,
                'CollectableAmount' => (float) $payload['collectableAmount'],
                'Commodity' => [
                    'CommodityDetail1' => $payload['Shipment']['items'][0]['name'] ?? 'General',
                    'CommodityDetail2' => '',
                    'CommodityDetail3' => ''
                ],
                'CreditReferenceNo' => $payload['Shipment']['code'],
                'CreditReferenceNo2' => '',
                'CreditReferenceNo3' => '',
                'CurrencyCode' => $payload['currencyCode'],
                'DeclaredValue' => (float) $payload['totalAmount'],
                'DeliveryTimeSlot' => '',
                'Dimensions' => [
                    [
                       
                        'Count' => (int) ($payload['Shipment']['numberOfBoxes'] ?? 1),
                        'Breadth' => (float) ($payload['Shipment']['breadth'] / 10),
                        'Height'  => (float) ($payload['Shipment']['height'] / 10),
                        'Length'  => (float) ($payload['Shipment']['length'] / 10),
                    ]
                ],
                'FavouringName' => '',
                'ForwardAWBNo' => '',
                'ForwardLogisticCompName' => '',
                'InsurancePaidBy' => '',
                'InvoiceNo' => $invoiceLink,
                'IsChequeDD' => '',
                'IsDedicatedDeliveryNetwork' => false,
                'IsForcePickup' => false,
                'IsPartialPickup' => false,
                'IsReversePickup' => $payload['returnShipmentFlag'] === 'true',
                'ItemCount' => count($payload['Shipment']['items']),
                'OTPBasedDelivery' => '0',
                'OTPCode' => '',
                'Officecutofftime' => '',
                'PDFOutputNotRequired' => true,
                'PackType' => '',
                'ParcelShopCode' => '',
                'PayableAt' => '',
                'PickupDate' => '/Date(' . (strtotime($payload['Shipment']['orderDate']) * 1000) . ')/',
                'PickupMode' => '',
                'PickupTime' => '0800',
                'PickupType' => '',
                'PieceCount' => (string) ($payload['Shipment']['numberOfBoxes'] ?? 1),
                'PreferredPickupTimeSlot' => '',
                'ProductCode' => 'E',
                'ProductFeature' => '',
                'ProductType' => count($payload['Shipment']['items']),
                'RegisterPickup' => true,
                'SpecialInstruction' => '',
                'SubProductCode' => '',
                'TotalCashPaytoCustomer' => 0,
                'itemdtl' => array_map(function($item) use ($payload) {
                    return [
                        'CGSTAmount' => 0,
                        'HSCode' => $item['hsnCode'] ?? '',
                        'IGSTAmount' => 0,
                        'IGSTRate' => 0,
                        'Instruction' => '',
                        'InvoiceDate' => '/Date(' . (strtotime($payload['Shipment']['orderDate']) * 1000) . ')/',
                        'InvoiceNumber' => $payload['Shipment']['invoiceCode'],
                        'ItemID' => substr($item['skuCode'], 0, 15),
                        'ItemName' => $item['name'],
                        'ItemValue' => (float) $item['itemPrice'],
                        'Itemquantity' => (int) $item['quantity'],
                        'PlaceofSupply' => $payload['deliveryAddressDetails']['city'],
                        'ProductDesc1' => $item['description'] ?? $item['name'],
                        'ProductDesc2' => $item['description'] ?? $item['name'],
                        'ReturnReason' => '',
                        'SGSTAmount' => 0,
                        'SKUNumber' => substr($item['skuCode'], 0, 15),
                        'SellerGSTNNumber' => $payload['pickupAddressDetails']['gstin'] ?? '',
                        'SellerName' => $payload['pickupAddressDetails']['name'],
                        'TaxableAmount' => 0,
                        'TotalValue' => (float) $item['itemPrice'],
                        'cessAmount' => '0.0',
                        'countryOfOrigin' => 'IN',
                        'docType' => 'INV',
                        'subSupplyType' => 1,
                        'supplyType' => '0'
                    ];
                }, $payload['Shipment']['items']),
                'noOfDCGiven' => 0
            ],
            'Shipper' => [
                'CustomerAddress1' => $payload['pickupAddressDetails']['address1'],
                'CustomerAddress2' => $payload['pickupAddressDetails']['address2'] ?? '',
                'CustomerAddress3' => $payload['pickupAddressDetails']['city'] . ',' . $payload['pickupAddressDetails']['state'],
                'CustomerAddressinfo' => '',
                'CustomerCode' => env('BLUEDART_CUSTOMER_CODE', '480056'),
                'CustomerEmailID' => $payload['pickupAddressDetails']['email'] ?? '',
                'CustomerGSTNumber' => $payload['pickupAddressDetails']['gstin'] ?? '',
                'CustomerLatitude' => isset($payload['pickupAddressDetails']['latitude']) 
    ? substr((string) $payload['pickupAddressDetails']['latitude'], 0, 20) 
    : null,
'CustomerLongitude' => isset($payload['pickupAddressDetails']['longitude']) 
    ? substr((string) $payload['pickupAddressDetails']['longitude'], 0, 20) 
    : null,
                'CustomerMaskedContactNumber' => '',
                'CustomerMobile' => $payload['pickupAddressDetails']['phone'],
                'CustomerName' => $payload['pickupAddressDetails']['name'],
                'CustomerPincode' => $payload['pickupAddressDetails']['pincode'],
                'CustomerTelephone' => $payload['pickupAddressDetails']['phone'],
                'IsToPayCustomer' => true,
                'OriginArea' => 'BOM',
                'Sender' => $payload['pickupAddressDetails']['name'],
                'VendorCode' => '125465'
            ]
        ];
    
        // ✅ Call BlueDart API here
        $bluedartPayload = [
            'Request' => $mappedRequest,
            'Profile' => [
                'LoginID' => env('BLUEDART_LOGIN_ID', 'BOM47572'),
                'LicenceKey' => env('BLUEDART_LICENCE_KEY', 'qfelnjusjpkv1uenvqq6svivfipntqkt'),
                'Api_type' => 'S'
            ]
        ];
      
  
      
        $bluedartResponse = $this->bluedartService->createWaybill($bluedartPayload,null,$request->attributes->get('clientid'), $request->attributes->get('test'));
        if (!$bluedartResponse['success']) {
            return response()->json([
                'status' => 'FAILED',
                'reason' => 'BLUEDART_ERROR',
                'message' => $bluedartResponse['message'],
                'details' => $bluedartResponse['data'] // ✅ send full Blueddart payload back
            ], 500);
        }

        $blueDartWaybill = $bluedartResponse['waybill'];

        // ✅ Save booking in DB
        $itemNames = collect($payload['Shipment']['items'])->pluck('name')->toArray();
        $contentString = implode(', ', $itemNames);

        $booking = booking::create([
            'waybills' => $blueDartWaybill,
            'cust_name' => 'Waree',
            'clientid' => $request->attributes->get('clientid'),
            'forwordingno' => $blueDartWaybill,
            'status' => 'Booked',
            'content' => $contentString,
            'service_type' => $payload['serviceType'],
            'modeoftrans' => $payload['handOverMode'],
            'con_client_name' => $payload['deliveryAddressDetails']['name'],
            'receivername' => $payload['deliveryAddressDetails']['name'],
            'receiver_pincode' => $payload['deliveryAddressDetails']['pincode'],
            'receivercity' => $payload['deliveryAddressDetails']['city'],
            'deliverylocation' => $payload['deliveryAddressDetails']['city'],
            'receiverstate' => $payload['deliveryAddressDetails']['state'],
            'receiveraddress' => $payload['deliveryAddressDetails']['address1'] . ' ' . ($payload['deliveryAddressDetails']['address2'] ?? ''),
            'receivercontactno' => $payload['deliveryAddressDetails']['phone'],
            'sendername' => $payload['pickupAddressDetails']['name'],
            'sender_pincode' => $payload['pickupAddressDetails']['pincode'],
            'sendercity' => $payload['pickupAddressDetails']['city'],
            'pickuplocation' => $payload['pickupAddressDetails']['city'],
            'senderstate' => $payload['pickupAddressDetails']['state'],
            'senderaddress' => $payload['pickupAddressDetails']['address1'] . ' ' . ($payload['pickupAddressDetails']['address2'] ?? ''),
            'sendercontactno' => $payload['pickupAddressDetails']['phone'],
            'payment_mode' => $payload['paymentMode'],
            'total_amount' => $payload['totalAmount'],
            'collectable_amount' => $payload['collectableAmount'],
            'weight' => $payload['Shipment']['weight'],
            'dimension' => [
                'l' => (float) ($payload['Shipment']['length'] / 10),
                'b' => (float) ($payload['Shipment']['breadth'] / 10),
                'h' => (float) ($payload['Shipment']['height'] / 10),
            ],
            'booking_date' => now(),
            'invoice_no' => $invoiceLink,
            'pices' => isset($payload['Shipment']['numberOfBoxes']) ? (int) $payload['Shipment']['numberOfBoxes'] : 1,
            'refrenceno' => $blueDartWaybill,
            'value' => $payload['collectableAmount'],
        ]);

        // Save items
        foreach ($payload['Shipment']['items'] as $item) {
            $booking->items()->create([
                'name' => $item['name'],
                'quantity' => $item['quantity'],
                'skuCode' => $item['skuCode'],
                'itemPrice' => $item['itemPrice'],
            ]);
        }

        // Add log
        $booking->bookingLogs()->create([
            'status' => 'Booked',
            'remark' => 'Shipment created via API',
            'bookingno' => $booking->id,
            'currentstatus' => 'Booked',
            'createdbyy' => 'API',
        ]);

        // Shipping label
        $shippingLabelUrl = $this->generateShippingLabelPdf($booking);

            // ✅ Success response
            $response = response()->json([
                'status' => 'SUCCESS',
                'waybill' => $blueDartWaybill,
                'courierName' => 'Bluedart',
                'shippingLabel' => $shippingLabelUrl
            ], 200);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_create', $executionTime, $blueDartWaybill);

            return $response;

        } catch (\Exception $e) {
            $response = response()->json([
                'status' => 'FAILED',
                'reason' => 'SYSTEM_ERROR',
                'message' => $e->getMessage()
            ], 500);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_create', $executionTime);

            return $response;
        }
    }

    /**
     * Handle Delhivery waybill creation
     */
    private function handleDelhiveryWaybill(Request $request, array $payload, string $invoiceLink, float $startTime): JsonResponse
    {
        try {
            // Step 1: Check delivery pincode serviceability first (most important)
            $deliveryPincode = $payload['deliveryAddressDetails']['pincode'];
            $senderPincode = $payload['pickupAddressDetails']['pincode'];
            $weightInKg = (float) ($payload['Shipment']['weight'] / 1000); // Convert grams to kg
            $test = $request->attributes->get('test');
            
            \Log::info("Delhivery Waybill: Checking delivery pincode serviceability - Pincode: {$deliveryPincode}, Weight: {$weightInKg} kg");
            
            // Check delivery pincode serviceability
            $deliveryServiceabilityCheck = $this->delhiveryService->checkPincodeServiceability(
                $deliveryPincode,
                $weightInKg,
                $test
            );
            
            \Log::info("Delhivery Waybill: Delivery pincode serviceability result", [
                'success' => $deliveryServiceabilityCheck['success'] ?? false,
                'is_serviceable' => $deliveryServiceabilityCheck['is_serviceable'] ?? false,
            ]);

            if (!$deliveryServiceabilityCheck['success'] || !$deliveryServiceabilityCheck['is_serviceable']) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'reason' => 'PINCODE_NOT_SERVICEABLE',
                    'message' => 'Delivery pincode ' . $deliveryPincode . ' is not serviceable by Delhivery for weight ' . $weightInKg . ' kg',
                    'details' => $deliveryServiceabilityCheck['serviceability_data'] ?? []
                ], 400);

                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                return $response;
            }

            // Step 2: Check sender pincode serviceability before warehouse creation
            \Log::info("Delhivery Waybill: Checking sender pincode serviceability - Pincode: {$senderPincode}, Weight: {$weightInKg} kg");
            
            $senderServiceabilityCheck = $this->delhiveryService->checkPincodeServiceability(
                $senderPincode,
                $weightInKg,
                $test
            );
            
            \Log::info("Delhivery Waybill: Sender pincode serviceability result", [
                'success' => $senderServiceabilityCheck['success'] ?? false,
                'is_serviceable' => $senderServiceabilityCheck['is_serviceable'] ?? false,
            ]);

            // Fallback pincode if sender pincode is not serviceable
            $fallbackPincode = '400059';
            $useFallbackPincode = false;

            if (!$senderServiceabilityCheck['success'] || !$senderServiceabilityCheck['is_serviceable']) {
                \Log::warning("Sender pincode {$senderPincode} is not serviceable. Trying fallback pincode: {$fallbackPincode}");
                
                // Check fallback pincode serviceability
                $fallbackServiceabilityCheck = $this->delhiveryService->checkPincodeServiceability(
                    $fallbackPincode,
                    $weightInKg,
                    $test
                );
                
                if ($fallbackServiceabilityCheck['success'] && $fallbackServiceabilityCheck['is_serviceable']) {
                    \Log::info("Fallback pincode {$fallbackPincode} is serviceable. Using it for warehouse creation.");
                    $useFallbackPincode = true;
                    $senderPincode = $fallbackPincode;
                } else {
                    $response = response()->json([
                        'status' => 'FAILED',
                        'reason' => 'PINCODE_NOT_SERVICEABLE',
                        'message' => 'Sender pincode ' . $payload['pickupAddressDetails']['pincode'] . ' and fallback pincode ' . $fallbackPincode . ' are not serviceable by Delhivery for weight ' . $weightInKg . ' kg',
                        'details' => [
                            'original_pincode' => $payload['pickupAddressDetails']['pincode'],
                            'fallback_pincode' => $fallbackPincode,
                            'original_check' => $senderServiceabilityCheck['serviceability_data'] ?? [],
                            'fallback_check' => $fallbackServiceabilityCheck['serviceability_data'] ?? []
                        ]
                    ], 400);

                    $executionTime = microtime(true) - $startTime;
                    $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                    return $response;
                }
            }

            // Step 3: Check/Create warehouse based on sender's pincode (or fallback pincode)
            $warehouseData = $this->prepareWarehouseData($payload);
            
            // If using fallback, update warehouse data with fallback pincode
            if ($useFallbackPincode) {
                $warehouseData['pin_code'] = $fallbackPincode;
                $warehouseData['name'] = "Warehouse_{$fallbackPincode}";
            }
            
            $warehouse = $this->delhiveryService->getOrCreateWarehouse(
                $senderPincode,
                $warehouseData,
                $request->attributes->get('test')
            );

            // If warehouse creation fails, try fallback pincode 400059
            if (!$warehouse && !$useFallbackPincode) {
                \Log::warning('Warehouse creation/retrieval failed for pincode: ' . $senderPincode . '. Trying fallback pincode: ' . $fallbackPincode);
                
                // Try fallback pincode
                $fallbackWarehouseData = $this->prepareWarehouseData($payload);
                $fallbackWarehouseData['pin_code'] = $fallbackPincode;
                $fallbackWarehouseData['name'] = "Warehouse_{$fallbackPincode}";
                
                $warehouse = $this->delhiveryService->getOrCreateWarehouse(
                    $fallbackPincode,
                    $fallbackWarehouseData,
                    $request->attributes->get('test')
                );
                
                if ($warehouse) {
                    \Log::info("Successfully created/retrieved warehouse for fallback pincode: {$fallbackPincode}");
                }
            }

            // ✅ FAIL EARLY: If warehouse creation/retrieval failed, return error immediately
            // Do not proceed with manifest creation if warehouse is not available
            if (!$warehouse) {
                \Log::error('Warehouse creation/retrieval failed for both original and fallback pincodes. Cannot proceed with manifest creation.');
                
                // Try to find any existing warehouse in DB as last resort
                $existingWarehouse = Warehouse::where('pin_code', $payload['pickupAddressDetails']['pincode'])->first();
                if (!$existingWarehouse) {
                    $existingWarehouse = Warehouse::where('pin_code', $fallbackPincode)->first();
                }
                
                if ($existingWarehouse && $existingWarehouse->warehouse_id) {
                    \Log::info('Found existing warehouse in DB with warehouse_id, using it: ' . $existingWarehouse->name);
                    $warehouse = $existingWarehouse;
                } else {
                    // No warehouse found or created - fail immediately
                    $response = response()->json([
                        'status' => 'FAILED',
                        'reason' => 'WAREHOUSE_NOT_CONFIGURED',
                        'message' => 'Failed to create or retrieve warehouse for pincode: ' . $payload['pickupAddressDetails']['pincode'] . 
                                     ($useFallbackPincode ? ' (also tried fallback pincode: ' . $fallbackPincode . ')' : '') . 
                                     '. Please ensure the warehouse is configured in Delhivery FAAS system before creating shipments.',
                        'details' => [
                            'original_pincode' => $payload['pickupAddressDetails']['pincode'],
                            'fallback_pincode' => $fallbackPincode,
                            'pincode_serviceable' => true, // We already checked this
                            'warehouse_creation_failed' => true
                        ]
                    ], 400);

                    $executionTime = microtime(true) - $startTime;
                    $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                    return $response;
                }
            }
            
            // ✅ Additional check: Ensure warehouse has a valid warehouse_id
            // If warehouse exists but has no warehouse_id, it means creation failed
            if ($warehouse && empty($warehouse->warehouse_id)) {
                \Log::error('Warehouse found but has no warehouse_id. Warehouse creation must have failed. Cannot proceed.');
                
                $response = response()->json([
                    'status' => 'FAILED',
                    'reason' => 'WAREHOUSE_NOT_CONFIGURED',
                    'message' => 'Warehouse for pincode ' . $warehouse->pin_code . ' exists but is not properly configured. ' .
                                 'Warehouse creation failed or warehouse is not configured in Delhivery FAAS system. ' .
                                 'Please ensure the warehouse is properly configured before creating shipments.',
                    'details' => [
                        'pincode' => $warehouse->pin_code,
                        'warehouse_name' => $warehouse->name,
                        'warehouse_id_missing' => true
                    ]
                ], 400);

                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                return $response;
            }

            /**
             * ✅ Delhivery Mapper - Map request to Delhivery LTL manifest format
             */
            $mappedRequest = $this->mapToDelhiveryPayload($payload, $invoiceLink, $warehouse);

            // Call Delhivery API
            $delhiveryResponse = $this->delhiveryService->createWaybill(
                $mappedRequest,
                null,
                $request->attributes->get('clientid'),
                $request->attributes->get('test')
            );

            if (!$delhiveryResponse['success']) {
                // Check if error is about warehouse not being configured in FAAS
                $errorMessage = $delhiveryResponse['message'];
                $errorData = $delhiveryResponse['data'] ?? [];
                
                if (is_array($errorMessage)) {
                    $errorMessage = json_encode($errorMessage);
                }
                
                // Check for FAAS warehouse configuration error
                if (stripos($errorMessage, 'FAAS') !== false && stripos($errorMessage, 'warehouse') !== false && stripos($errorMessage, 'not been configured') !== false) {
                    $response = response()->json([
                        'status' => 'FAILED',
                        'reason' => 'WAREHOUSE_NOT_CONFIGURED',
                        'message' => 'Warehouse needs to be pre-configured in Delhivery FAAS system. Please configure the warehouse for pincode ' . $senderPincode . ' in Delhivery FAAS before creating waybills.',
                        'details' => [
                            'pincode' => $senderPincode,
                            'warehouse_name' => $warehouse->name ?? 'N/A',
                            'suggestion' => 'Either configure the warehouse in FAAS or use pickup_location_id if you have a warehouse ID from FAAS'
                        ]
                    ], 400);
                } else {
                    $response = response()->json([
                        'status' => 'FAILED',
                        'reason' => 'DELHIVERY_ERROR',
                        'message' => $errorMessage,
                        'details' => $errorData
                    ], 500);
                }

                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                return $response;
            }

            // The service now automatically polls for status, so we can use the waybill directly
            $delhiveryWaybill = $delhiveryResponse['waybill'] ?? null;
            $jobId = $delhiveryResponse['job_id'] ?? null;
            
            // If still no waybill after automatic polling, return job_id for later polling
            if (!$delhiveryWaybill && $jobId) {
                \Log::warning("Delhivery manifest status not ready after automatic polling for job_id: {$jobId}. Returning job_id for later polling.");
                // Return job_id so client can poll later
                $response = response()->json([
                    'status' => 'PENDING',
                    'reason' => 'ASYNC_PROCESSING',
                    'message' => 'Manifest is being processed. Use job_id to poll status via GET /manifest?job_id={job_id}',
                    'job_id' => $jobId,
                    'details' => $delhiveryResponse['data'] ?? []
                ], 202);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                return $response;
            }
            
            // Extract LR number and AWB numbers from response
            $lrNumber = $delhiveryResponse['lr_number'] ?? null;
            $awbNumbers = $delhiveryResponse['awb_numbers'] ?? [];
            
            // Log the waybill details if available
            if ($delhiveryWaybill) {
                \Log::info("Delhivery waybill created successfully. LR: " . ($lrNumber ?? 'N/A') . ", Waybill: {$delhiveryWaybill}, AWBs: " . json_encode($awbNumbers));
            }

            // ✅ Save booking in DB
            $itemNames = collect($payload['Shipment']['items'])->pluck('name')->toArray();
            $contentString = implode(', ', $itemNames);

            $booking = booking::create([
                'waybills' => $delhiveryWaybill,
                'lr_number' => $lrNumber, // Store LR number for Delhivery shipments
                'cust_name' => 'Waree',
                'clientid' => $request->attributes->get('clientid'),
                'forwordingno' => $delhiveryWaybill,
                'status' => 'Booked',
                'content' => $contentString,
                'service_type' => $payload['serviceType'],
                'modeoftrans' => $payload['handOverMode'],
                'con_client_name' => $payload['deliveryAddressDetails']['name'],
                'receivername' => $payload['deliveryAddressDetails']['name'],
                'receiver_pincode' => $payload['deliveryAddressDetails']['pincode'],
                'receivercity' => $payload['deliveryAddressDetails']['city'],
                'deliverylocation' => $payload['deliveryAddressDetails']['city'],
                'receiverstate' => $payload['deliveryAddressDetails']['state'],
                'receiveraddress' => $payload['deliveryAddressDetails']['address1'] . ' ' . ($payload['deliveryAddressDetails']['address2'] ?? ''),
                'receivercontactno' => $payload['deliveryAddressDetails']['phone'],
                'sendername' => $payload['pickupAddressDetails']['name'],
                'sender_pincode' => $payload['pickupAddressDetails']['pincode'],
                'sendercity' => $payload['pickupAddressDetails']['city'],
                'pickuplocation' => $payload['pickupAddressDetails']['city'],
                'senderstate' => $payload['pickupAddressDetails']['state'],
                'senderaddress' => $payload['pickupAddressDetails']['address1'] . ' ' . ($payload['pickupAddressDetails']['address2'] ?? ''),
                'sendercontactno' => $payload['pickupAddressDetails']['phone'],
                'payment_mode' => $payload['paymentMode'],
                'total_amount' => $payload['totalAmount'],
                'collectable_amount' => $payload['collectableAmount'],
                'weight' => $payload['Shipment']['weight'],
                'dimension' => [
                    'l' => (float) ($payload['Shipment']['length'] / 10),
                    'b' => (float) ($payload['Shipment']['breadth'] / 10),
                    'h' => (float) ($payload['Shipment']['height'] / 10),
                ],
                'booking_date' => now(),
                'invoice_no' => $invoiceLink,
                'pices' => isset($payload['Shipment']['numberOfBoxes']) ? (int) $payload['Shipment']['numberOfBoxes'] : 1,
                'refrenceno' => $delhiveryWaybill,
                'value' => $payload['collectableAmount'],
            ]);

            // Save items
            foreach ($payload['Shipment']['items'] as $item) {
                $booking->items()->create([
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'skuCode' => $item['skuCode'],
                    'itemPrice' => $item['itemPrice'],
                ]);
            }

            // Add log
            $booking->bookingLogs()->create([
                'status' => 'Booked',
                'remark' => 'Shipment created via API',
                'bookingno' => $booking->id,
                'currentstatus' => 'Booked',
                'createdbyy' => 'API',
            ]);

            // Shipping label - Get from Delhivery API
            $shippingLabelUrl = null;
            if ($delhiveryWaybill) {
                try {
                    \Log::info("Fetching Delhivery shipping labels for LR: {$delhiveryWaybill}");
                    
                    // Get label URLs from Delhivery API
                    $labelUrlsResponse = $this->delhiveryService->getLabelUrls(
                        $delhiveryWaybill,
                        $request->attributes->get('test')
                    );
                    
                    if ($labelUrlsResponse['success'] && !empty($labelUrlsResponse['label_urls'])) {
                        // Download and store labels
                        $shippingLabelUrl = $this->delhiveryService->downloadAndStoreLabels(
                            $delhiveryWaybill,
                            $labelUrlsResponse['label_urls'],
                            $booking->id,
                            $request->attributes->get('test')
                        );
                        
                        if ($shippingLabelUrl) {
                            \Log::info("Delhivery shipping label saved: {$shippingLabelUrl}");
                        } else {
                            \Log::warning("Failed to download/store Delhivery shipping labels for LR: {$delhiveryWaybill}");
                        }
                    } else {
                        \Log::warning("Failed to get label URLs from Delhivery for LR: {$delhiveryWaybill}. Message: " . ($labelUrlsResponse['message'] ?? 'Unknown error'));
                    }
                } catch (\Exception $e) {
                    \Log::error("Error fetching Delhivery shipping label: " . $e->getMessage());
                    // Continue without label - don't fail the entire request
                }
            }

            // ✅ Success response
            $response = response()->json([
                'status' => 'SUCCESS',
                'waybill' => $delhiveryWaybill,
                'courierName' => 'Delhivery',
                'shippingLabel' => $shippingLabelUrl
            ], 200);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_create', $executionTime, $delhiveryWaybill);

            return $response;

        } catch (\Exception $e) {
            // Log full exception details for debugging
            \Log::error('Delhivery Waybill Exception: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $errorMessage = $e->getMessage();
            // Ensure error message is always a string
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage);
            }
            
            $response = response()->json([
                'status' => 'FAILED',
                'reason' => 'SYSTEM_ERROR',
                'message' => $errorMessage
            ], 500);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_create', $executionTime);

            return $response;
        }
    }

    /**
     * Prepare warehouse data from payload
     */
    private function prepareWarehouseData(array $payload): array
    {
        $pickup = $payload['pickupAddressDetails'];
        
        return [
            'pin_code' => $pickup['pincode'],
            'city' => $pickup['city'],
            'state' => $pickup['state'],
            'country' => $pickup['country'] ?? 'India',
            'name' => "Warehouse_{$pickup['pincode']}",
            'address_details' => [
                'address' => $pickup['address1'] . ' ' . ($pickup['address2'] ?? ''),
                'contact_person' => $pickup['name'],
                'phone_number' => $pickup['phone'],
            ],
            'business_hours' => [
                'MON' => ['start_time' => '09:00', 'close_time' => '18:00'],
                'TUE' => ['start_time' => '09:00', 'close_time' => '18:00'],
                'WED' => ['start_time' => '09:00', 'close_time' => '18:00'],
                'THU' => ['start_time' => '09:00', 'close_time' => '18:00'],
                'FRI' => ['start_time' => '09:00', 'close_time' => '18:00'],
            ],
            'pick_up_hours' => [
                'MON' => ['start_time' => '10:00', 'close_time' => '17:00'],
                'TUE' => ['start_time' => '10:00', 'close_time' => '17:00'],
                'WED' => ['start_time' => '10:00', 'close_time' => '17:00'],
                'THU' => ['start_time' => '10:00', 'close_time' => '17:00'],
                'FRI' => ['start_time' => '10:00', 'close_time' => '17:00'],
            ],
            'pick_up_days' => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
            'business_days' => ['MON', 'TUE', 'WED', 'THU', 'FRI'],
            'ret_address' => [
                'pin' => $pickup['pincode'],
                'address' => $pickup['address1'] . ' ' . ($pickup['address2'] ?? ''),
            ],
        ];
    }

    /**
     * Map request payload to Delhivery LTL manifest format
     * 
     * Note: Delhivery LTL requires a doc_file (invoice PDF) in the payload.
     * The doc_file should be a valid file path to an invoice PDF.
     */
    private function mapToDelhiveryPayload(array $payload, string $invoiceLink, $warehouse): array
    {
        $pickup = $payload['pickupAddressDetails'];
        $delivery = $payload['deliveryAddressDetails'];
        
        // Prepare invoices array
        $invoices = [
            [
                'ewaybill' => '',
                'inv_num' => $invoiceLink,
                'inv_amt' => (float) $payload['totalAmount'],
                'inv_qr_code' => '',
            ]
        ];

        // Prepare shipment details - must be array of objects
        // Note: master should be boolean false (not string "False")
        $shipmentDetails = [
            [
                'order_id' => (string) $payload['Shipment']['orderCode'],
                'box_count' => isset($payload['Shipment']['numberOfBoxes']) ? (int) $payload['Shipment']['numberOfBoxes'] : 1,
                'description' => collect($payload['Shipment']['items'])->pluck('name')->implode(', '),
                'weight' => (int) $payload['Shipment']['weight'], // in grams
                'waybills' => [],
                'master' => false, // boolean false (not string "False")
            ]
        ];

        // Prepare dimensions
        $dimensions = [
            [
                'box_count' => isset($payload['Shipment']['numberOfBoxes']) ? (int) $payload['Shipment']['numberOfBoxes'] : 1,
                'length' => (float) ($payload['Shipment']['length'] / 10), // Convert to cm
                'width' => (float) ($payload['Shipment']['breadth'] / 10),
                'height' => (float) ($payload['Shipment']['height'] / 10),
            ]
        ];

        // Prepare dropoff location
        $dropoffLocation = [
            'consignee_name' => $delivery['name'],
            'address' => $delivery['address1'] . ' ' . ($delivery['address2'] ?? ''),
            'city' => $delivery['city'],
            'state' => $delivery['state'],
            'zip' => $delivery['pincode'],
            'phone' => $delivery['phone'],
            'email' => $delivery['email'] ?? '',
        ];

        // Prepare billing address
        // According to docs: either pan_number OR gst_number is mandatory (not both empty)
        $panNumber = !empty($pickup['pan']) ? $pickup['pan'] : null;
        $gstNumber = !empty($pickup['gstin']) ? $pickup['gstin'] : null;
        
        // If both are empty, use a placeholder (or you can throw an error)
        // GST format regex: ^(UR$)|(TE$)|([0-3]{1}[0-9]{1}[A-Z]{5}[0-9]{4}[A-Z]{1}[0-9]{1}[Z,z]{1}[0-9A-Z]{1}$)
        // Format: [0-3][0-9][A-Z]{5}[0-9]{4}[A-Z][0-9][Zz][0-9A-Z]
        if (empty($panNumber) && empty($gstNumber)) {
            // Get state code - use stateCode if available, otherwise default to 27 (Maharashtra)
            $stateCode = '27'; // Default to Maharashtra
            if (!empty($pickup['stateCode'])) {
                // Extract numeric state code (first 2 digits)
                $stateCodeNum = preg_replace('/[^0-9]/', '', $pickup['stateCode']);
                if (!empty($stateCodeNum) && (int)$stateCodeNum >= 0 && (int)$stateCodeNum <= 37) {
                    $stateCode = str_pad($stateCodeNum, 2, '0', STR_PAD_LEFT);
                }
            }
            
            // Ensure state code starts with 0-3 (GST requirement)
            if ((int)$stateCode > 37 || (int)substr($stateCode, 0, 1) > 3) {
                $stateCode = '27'; // Default to Maharashtra if invalid
            }
            
            // Generate valid GST number: [state_code][A-Z]{5}[0-9]{4}[A-Z][0-9][Z][A-Z]
            // Example: 27AEBPM1234C1Z5
            $gstNumber = $stateCode . 'AEBPM1234C1Z5';
            \Log::warning('Both PAN and GST numbers are missing. Using placeholder GST: ' . $gstNumber);
        }
        
        $billingAddress = [
            'name' => $pickup['name'],
            'company' => $pickup['name'],
            'consignor' => $pickup['name'],
            'address' => $pickup['address1'] . ' ' . ($pickup['address2'] ?? ''),
            'city' => $pickup['city'],
            'state' => $pickup['state'],
            'pin' => $pickup['pincode'],
            'phone' => $pickup['phone'],
        ];
        
        // Only include pan_number or gst_number if they have values
        if (!empty($panNumber)) {
            $billingAddress['pan_number'] = $panNumber;
        }
        if (!empty($gstNumber)) {
            $billingAddress['gst_number'] = $gstNumber;
        }

        // Prepare doc_data
        $docData = [
            [
                'doc_type' => 'INVOICE_COPY',
                'doc_meta' => [
                    'invoice_num' => [$invoiceLink],
                ],
            ]
        ];

        // Use pickup_location_id if available (from FAAS), otherwise use pickup_location_name
        $mappedPayload = [
            'lrn' => '',
            'payment_mode' => strtolower($payload['paymentMode']),
            'weight' => (float) ($payload['Shipment']['weight']), // Weight in grams as per API docs
            'dropoff_location' => $dropoffLocation,
            'rov_insurance' => true,
            'invoices' => $invoices,
            'shipment_details' => $shipmentDetails,
            'dimensions' => $dimensions,
            'doc_data' => $docData,
            'fm_pickup' => false,
            // Note: freight_mode is only required for retail clients, not for B2B accounts
            'billing_address' => $billingAddress,
        ];
        
        // Use pickup_location_name for newly created warehouses (they may not be immediately available by ID)
        // For newly created warehouses, use name; for existing warehouses, try ID first
        $useId = false;
        if (isset($warehouse->warehouse_id) && !empty($warehouse->warehouse_id)) {
            // Check if warehouse was created recently (within last 5 minutes)
            if (isset($warehouse->created_at) && $warehouse->created_at) {
                $createdAt = is_string($warehouse->created_at) ? strtotime($warehouse->created_at) : (is_object($warehouse->created_at) ? $warehouse->created_at->timestamp : time());
                $fiveMinutesAgo = time() - 300; // 5 minutes in seconds
                if ($createdAt < $fiveMinutesAgo) {
                    $useId = true;
                }
            } else {
                // If no created_at, assume it's an existing warehouse and use ID
                $useId = true;
            }
        }
        
        if ($useId) {
            $mappedPayload['pickup_location_id'] = $warehouse->warehouse_id;
            \Log::info('Using pickup_location_id: ' . $warehouse->warehouse_id);
        } else {
            $mappedPayload['pickup_location_name'] = $warehouse->name;
            \Log::info('Using pickup_location_name: ' . $warehouse->name . ' (warehouse may be newly created)');
        }
        
        // Add cod_amount if payment mode is COD (mandatory for COD)
        if (strtolower($payload['paymentMode']) === 'cod') {
            $mappedPayload['cod_amount'] = (float) $payload['collectableAmount'];
        }

        // Generate or fetch invoice PDF and add to payload as 'doc_file'
        // The doc_file is required by Delhivery LTL API
        try {
            $docFile = null;
            
            // Option 1: If invoice_link is a URL, download it
            if (filter_var($invoiceLink, FILTER_VALIDATE_URL)) {
                $tempFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.pdf';
                $fileContent = @file_get_contents($invoiceLink);
                if ($fileContent !== false) {
                    file_put_contents($tempFile, $fileContent);
                    $docFile = $tempFile;
                }
            }
            
            // Option 2: Generate a simple PDF invoice (fallback)
            if (!$docFile) {
                $tempFile = storage_path('app/temp/invoice_' . $invoiceLink . '_' . time() . '.pdf');
                $tempDir = dirname($tempFile);
                if (!file_exists($tempDir)) {
                    mkdir($tempDir, 0755, true);
                }
                
                // Create a minimal PDF file as fallback
                $docFile = $this->createMinimalPdf($tempFile, $invoiceLink);
            }
            
            if ($docFile && file_exists($docFile)) {
                $mappedPayload['doc_file'] = $docFile;
            }
        } catch (\Exception $e) {
            \Log::error('Error preparing doc_file for Delhivery: ' . $e->getMessage());
            // Continue without doc_file - API might still work
        }

        return $mappedPayload;
    }

    /**
     * Create a minimal PDF file as fallback
     */
    private function createMinimalPdf(string $filePath, string $invoiceNumber): ?string
    {
        try {
            // Create a simple text-based PDF content
            $pdfContent = "%PDF-1.4\n";
            $pdfContent .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
            $pdfContent .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
            $pdfContent .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> >> >> >>\nendobj\n";
            $pdfContent .= "4 0 obj\n<< /Length 44 >>\nstream\nBT\n/F1 12 Tf\n100 700 Td\n(Invoice: {$invoiceNumber}) Tj\nET\nendstream\nendobj\n";
            $pdfContent .= "xref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000306 00000 n \ntrailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n400\n%%EOF";
            
            file_put_contents($filePath, $pdfContent);
            return $filePath;
        } catch (\Exception $e) {
            \Log::error('Failed to create minimal PDF: ' . $e->getMessage());
            return null;
        }
    }
    public function cancelWaybill(Request $request): JsonResponse
{
    $startTime = microtime(true);
    
    // Authenticate the request using token
    $authError = $this->authenticateRequest($request);
    if ($authError) {
        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $authError, 'waybill_cancel', $executionTime);
        return $authError;
    }

    // Validate request payload
    $validator = Validator::make($request->all(), [
        'waybill' => 'required|string'
    ]);

    if ($validator->fails()) {
        $response = response()->json([
            'status' => 'VALIDATION_ERROR',
            'message' => 'Invalid request data',
            'errors' => $validator->errors()
        ], 400);
        
        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $response, 'waybill_cancel', $executionTime);
        return $response;
    }

    try {
        $waybill = $request->input('waybill');

        // Find booking by waybill
        $booking = booking::where('waybills', $waybill)->first();

        if (!$booking) {
            $response = response()->json([
                'status' => 'FAILED',
                'waybill' => $waybill,
                'errorMessage' => 'Waybill not found'
            ], 404);
            
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_cancel', $executionTime, $waybill);
            return $response;
        }

        // If already cancelled
        if ($booking->status === 'CANCELLED') {
            $response = response()->json([
                'status' => 'FAILED',
                'waybill' => $waybill,
                'errorMessage' => 'Pickup already cancelled'
            ], 400);
            
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_cancel', $executionTime, $waybill);
            return $response;
        }

        // Update booking status
        $booking->update([
            'status' => 'CANCELLED'
        ]);

        // Create booking log
        $booking->bookingLogs()->create([
            'status' => 'CANCELLED',
            'remark' => 'Pickup cancelled via API request',
            'bookingno' => $booking->id,
            'currentstatus' => 'CANCELLED',
            'createdbyy' => 'API', // can be dynamic if you have user_id
            'deliverydate' => null,
            'expecteddeliverydate' => null
        ]);

        $response = response()->json([
            'status' => 'SUCCESS',
            'waybill' => $waybill,
            'errorMessage' => 'Pickup is successfully cancelled'
        ], 200);

        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $response, 'waybill_cancel', $executionTime, $waybill);
        
        return $response;

    } catch (\Exception $e) {
        $response = response()->json([
            'status' => 'FAILED',
            'waybill' => $request->input('waybill'),
            'errorMessage' => 'Pickup is not cancelled due to error: ' . $e->getMessage()
        ], 500);
        
        $executionTime = microtime(true) - $startTime;
        $this->logApiRequest($request, $response, 'waybill_cancel', $executionTime, $request->input('waybill'));
        
        return $response;
    }
}

public function waybillDetails(Request $request): JsonResponse
{
    // Authenticate request
    $authError = $this->authenticateRequest($request);
    if ($authError) {
        return $authError;
    }

    // Validate query parameters
    $validator = Validator::make($request->all(), [
        'waybills' => 'required|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'Status' => 'FAILED',
            'message' => 'Invalid request parameters',
            'waybillDetails' => []
        ], 400);
    }

    try {
        // Split waybills by comma
         $waybillsParam = trim($request->query('waybills'), "\"' ");
        $waybillList = array_filter(array_map('trim', explode(',', $waybillsParam)));

        if (count($waybillList) > 50) {
            return response()->json([
                'Status' => 'FAILED',
                'message' => 'Maximum 50 waybills allowed per request',
                'waybillDetails' => []
            ], 400);
        }


        $details = [];

        foreach ($waybillList as $waybill) {
            $booking = booking::where('waybills', $waybill)->first();

            if (!$booking) {
                $details[] = [
                    "waybill" => $waybill,
                    "currentStatus" => "not_found", // already lowercase
                    "current_sub_status" => "",
                    "current_status_remark" => "Waybill not found",
                    "statusDate" => now()->format('d-M-Y H:i:s'),
                    "shipping_provider" => "",
                    "current_location" => "",
                    "current_pincode" => "",
                    "current_city" => "",
                    "current_state" => "",
                    "current_country" => "India",
                    "latitude" => "",
                    "longitude" => "",
                    "expected_date_of_delivery" => "",
                    "promised_date_of_delivery" => "",
                    "payment_type" => "",
                    "weight" => "",
                    "dimensions" => [
                        "l" => "",
                        "b" => "",
                        "h" => ""
                    ],
                    "delivery_agent_name" => "",
                    "delivery_agent_number" => "",
                    "attempt_count" => "",
                    "ndr_code" => "",
                    "ndr_reason" => "",
                    "next_delivery_date" => "",
                    "cir_pickup_datetime" => "",
                    "tracking_history" => [],
                    "parent_awb" => "",
                    "rto_awb" => "",
                    "rto_reason" => ""
                ];
            } else {
                $details[] = [
                    "waybill" => $waybill,
                    "currentStatus" => strtolower($booking->status ?? ""), // lowercase
                    "current_sub_status" => "",
                    "current_status_remark" => "",
                    "statusDate" => $booking->updated_at ? $booking->updated_at->format('d-M-Y H:i:s') : "",
                    "shipping_provider" => $booking->modeoftrans ?? "",
                    "current_location" => $booking->receivercity ?? "",
                    "current_pincode" => $booking->receiver_pincode ?? "",
                    "current_city" => $booking->receivercity ?? "",
                    "current_state" => $booking->receiverstate ?? "",
                    "current_country" => "India",
                    "latitude" => "",
                    "longitude" => "",
                    "expected_date_of_delivery" => $booking->expecteddeliverydate ?? "",
                    "promised_date_of_delivery" => "",
                    "payment_type" => $booking->service_type ?? "",
                    "weight" => $booking->weight ?? "",
                    "dimensions" => [
                        "l" => $booking->dimension['l'] ?? "",
                        "b" => $booking->dimension['b'] ?? "",
                        "h" => $booking->dimension['h'] ?? ""
                    ],
                    "delivery_agent_name" => "",
                    "delivery_agent_number" => "",
                    "attempt_count" => $booking->attempt_count ?? "",
                    "ndr_code" => "",
                    "ndr_reason" => "",
                    "next_delivery_date" => "",
                    "cir_pickup_datetime" => "",
                    "tracking_history" => $booking->bookingLogs
                        ->sortByDesc('created_at')
                        ->map(function($log) {
                            return [
                                "date_time" => $log->created_at->format('d-M-Y H:i:s'),
                                "status" => strtolower($log->status ?? ""), // lowercase
                                "sub_status" => strtolower($log->currentstatus ?? ""), // lowercase
                                "remark" => $log->remark ?? "",
                                "location" => strtolower($log->currentstatus ?? ""), // lowercase
                                "pincode" => "",
                                "city" => "",
                                "state" => "",
                                "country" => "India"
                            ];
                        })->values(),
                    "parent_awb" => "",
                    "rto_awb" => "",
                    "rto_reason" => ""
                ];
            }
        }

        return response()->json([
            'Status' => 'SUCCESS',
            'waybillDetails' => $details,
            'message' => 'Waybill details fetched successfully'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'Status' => 'FAILED',
            'message' => 'Error fetching waybill details: ' . $e->getMessage(),
            'waybillDetails' => []
        ], 500);
    }
}
public function bluedart(Request $request): JsonResponse
{
        $tokenUrl = "https://apigateway.bluedart.com/in/transportation/token/v1/login";

        // 🔹 Fetch new JWT token
        \Log::info("Fetching new JWT token...");
        try {
            $res = Http::withHeaders([
                    "ClientID"     => "D7FGUzOG0AvjIGT6uGLRXs6AH8GbzhbA",
                    "ClientSecret" => "bIkWdoWf3vXyjFVW",
                    "Content-Type" => "application/json",
                ])
                ->withBody(json_encode([
                    "email"    => "admin@gmail.com",
                    "password" => "123456"
                ]), 'application/json')
                ->get($tokenUrl);

            $body = $res->json();
            if ($res->successful() && isset($body['JWTToken'])) {
                $jwt = $body['JWTToken'];
                \Log::info("✅ Got JWT token: " . $jwt);
                
                return response()->json([
                    'status' => 'SUCCESS',
                    'token' => $jwt,
                    'message' => 'JWT token fetched successfully'
                ], 200);
            } else {
                \Log::error("❌ Failed to get JWT token. Status: {$res->status()} Body: " . $res->body());
                return response()->json([
                    'status' => 'FAILED',
                    'message' => 'Failed to get JWT token from BlueDart'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error("💥 Error fetching JWT: " . $e->getMessage());
            return response()->json([
                'status' => 'FAILED',
                'message' => 'Error fetching JWT token: ' . $e->getMessage()
            ], 500);
        }
    }



}