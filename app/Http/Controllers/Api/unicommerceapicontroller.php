<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\booking;
use App\Models\bookinglog;
use App\Models\ApiRequestLog;
use App\Models\Warehouse;
use App\Models\CancellationRequest;
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
    // Cast test to int (0 = production, 1 = test) - preserve 0 value correctly
    $request->attributes->set('test', (int) $webhook->test);
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

        // Route to appropriate service based on webhook configuration
        if ($serviceProvider === 'delhivery') {
            return $this->handleDelhiveryWaybill($request, $payload, $invoiceLink, $startTime);
        } else {
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
      
        // Get test value (0 = production, 1 = test) - ensure it's an integer
        $test = (int) $request->attributes->get('test', 1);
      
        $bluedartResponse = $this->bluedartService->createWaybill($bluedartPayload,null,$request->attributes->get('clientid'), $test);
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
     * 
     * Serviceability checks are performed for BOTH:
     * 1. Delivery pincode (destination) - MUST be serviceable
     * 2. Pickup/Sender pincode (origin) - MUST be serviceable (with fallback option)
     * 
     * Both checks use the Delhivery serviceability API and results are cached in database.
     */
    private function handleDelhiveryWaybill(Request $request, array $payload, string $invoiceLink, float $startTime): JsonResponse
    {
        try {
            // Extract pincodes and weight
            $deliveryPincode = $payload['deliveryAddressDetails']['pincode'];
            $senderPincode = $payload['pickupAddressDetails']['pincode'];
            $weightInKg = (float) ($payload['Shipment']['weight'] / 1000); // Convert grams to kg
            // Get test value (0 = production, 1 = test) - ensure it's an integer
            $test = (int) $request->attributes->get('test', 1);
            $isProduction = !$test && app()->environment('production');
            
            // ============================================
            // Step 1 & 2: Check BOTH pincodes serviceability in PARALLEL (OPTIMIZED)
            // ============================================
            $isProduction = !$test && app()->environment('production');
            
            // Use parallel checking for better performance
            $serviceabilityResults = $this->delhiveryService->checkMultiplePincodeServiceability([
                ['pincode' => $deliveryPincode, 'weight' => $weightInKg, 'key' => 'delivery'],
                ['pincode' => $senderPincode, 'weight' => $weightInKg, 'key' => 'pickup'],
            ], $test);
            
            $deliveryServiceabilityCheck = $serviceabilityResults['delivery'] ?? [];
            $senderServiceabilityCheck = $serviceabilityResults['pickup'] ?? [];

            // Delivery pincode MUST be serviceable - fail immediately if not
            if (empty($deliveryServiceabilityCheck) || !$deliveryServiceabilityCheck['success'] || !$deliveryServiceabilityCheck['is_serviceable']) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'reason' => 'PINCODE_NOT_SERVICEABLE',
                    'message' => 'Delivery pincode ' . $deliveryPincode . ' is not serviceable by Delhivery for weight ' . number_format($weightInKg, 3, '.', '') . ' kg',
                    'details' => [
                        'pincode_type' => 'delivery',
                        'pincode' => $deliveryPincode,
                        'weight_kg' => number_format($weightInKg, 3, '.', ''),
                        'serviceability_data' => $deliveryServiceabilityCheck['serviceability_data'] ?? [],
                        'api_response' => $deliveryServiceabilityCheck['data'] ?? null,
                        'cached' => $deliveryServiceabilityCheck['cached'] ?? false,
                        'check_success' => $deliveryServiceabilityCheck['success'] ?? false
                    ]
                ], 400);

                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                return $response;
            }

            // Fallback pincode if sender pincode is not serviceable
            $fallbackPincode = '400059';
            $useFallbackPincode = false;

            if (empty($senderServiceabilityCheck) || !$senderServiceabilityCheck['success'] || !$senderServiceabilityCheck['is_serviceable']) {
                // Check fallback pincode serviceability
                $fallbackServiceabilityCheck = $this->delhiveryService->checkPincodeServiceability(
                    $fallbackPincode,
                    $weightInKg,
                    $test
                );
                
                if ($fallbackServiceabilityCheck['success'] && $fallbackServiceabilityCheck['is_serviceable']) {
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
            
            // Use the test value we already determined at the start of the function
            $warehouse = $this->delhiveryService->getOrCreateWarehouse(
                $senderPincode,
                $warehouseData,
                $test
            );

            // If warehouse creation fails, try fallback pincode 400059
            if (!$warehouse && !$useFallbackPincode) {
                // Try fallback pincode
                $fallbackWarehouseData = $this->prepareWarehouseData($payload);
                $fallbackWarehouseData['pin_code'] = $fallbackPincode;
                $fallbackWarehouseData['name'] = "Warehouse_{$fallbackPincode}";
                
                // Use the same test value as above
                $warehouse = $this->delhiveryService->getOrCreateWarehouse(
                    $fallbackPincode,
                    $fallbackWarehouseData,
                    $test
                );
            }

            // ✅ FAIL EARLY: If warehouse creation/retrieval failed, return error immediately
            // Do not proceed with manifest creation if warehouse is not available
            if (!$warehouse) {
                
                // Try to find any existing warehouse in DB as last resort
                // First try with matching test and serviceBy
                $isTest = (bool) $test;
                $serviceBy = 'delhivery';
                
                $existingWarehouse = Warehouse::where('pin_code', $payload['pickupAddressDetails']['pincode'])
                    ->where('test', $isTest)
                    ->where('serviceBy', $serviceBy)
                    ->where('is_active', true)
                    ->first();
                    
                if (!$existingWarehouse) {
                    $existingWarehouse = Warehouse::where('pin_code', $fallbackPincode)
                        ->where('test', $isTest)
                        ->where('serviceBy', $serviceBy)
                        ->where('is_active', true)
                        ->first();
                }
                
                // If still not found, try without test/serviceBy filter (fallback)
                if (!$existingWarehouse) {
                    $existingWarehouse = Warehouse::where('pin_code', $payload['pickupAddressDetails']['pincode'])
                        ->where('is_active', true)
                        ->first();
                }
                
                if (!$existingWarehouse) {
                    $existingWarehouse = Warehouse::where('pin_code', $fallbackPincode)
                        ->where('is_active', true)
                        ->first();
                }
                
                if ($existingWarehouse && !empty($existingWarehouse->name)) {
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
            
            // ✅ Additional check: Ensure warehouse has name
            // Warehouse must have a name to use pickup_location_name
            if ($warehouse && empty($warehouse->name)) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'reason' => 'WAREHOUSE_NOT_CONFIGURED',
                    'message' => 'Warehouse for pincode ' . $warehouse->pin_code . ' exists but is not properly configured. ' .
                                 'Warehouse has no name. Please ensure the warehouse is properly configured before creating shipments.',
                    'details' => [
                        'pincode' => $warehouse->pin_code,
                        'warehouse_name' => $warehouse->name ?? 'N/A',
                        'warehouse_name_missing' => true
                    ]
                ], 400);

                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                return $response;
            }
            
            /**
             * ✅ Delhivery Mapper - Map request to Delhivery LTL manifest format
             */
            $mappedRequest = $this->mapToDelhiveryPayload($payload, $invoiceLink, $warehouse, $test);

            // Call Delhivery API - use the test value we already determined
            $delhiveryResponse = $this->delhiveryService->createWaybill(
                $mappedRequest,
                null,
                $request->attributes->get('clientid'),
                $test
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
            
            // If still no waybill after automatic polling, poll once more (optimized - no sleep)
            if (!$delhiveryWaybill && $jobId) {
                // Poll status one more time immediately (no sleep for faster response)
                $statusResult = $this->delhiveryService->getManifestStatus($jobId, $test);
                
                if ($statusResult['success'] && ($statusResult['lr_number'] || !empty($statusResult['awb_numbers']))) {
                    // Get LR number (primary tracking ID) or first AWB number
                    $delhiveryWaybill = $statusResult['lr_number'] ?? 
                                      (isset($statusResult['awb_numbers']) && !empty($statusResult['awb_numbers']) 
                                       ? $statusResult['awb_numbers'][0] : null);
                    
                    // Update delhiveryResponse with statusResult data if not already present
                    if (!isset($delhiveryResponse['lr_number']) && isset($statusResult['lr_number'])) {
                        $delhiveryResponse['lr_number'] = $statusResult['lr_number'];
                    }
                    if (!isset($delhiveryResponse['master_waybill']) && isset($statusResult['master_waybill'])) {
                        $delhiveryResponse['master_waybill'] = $statusResult['master_waybill'];
                    }
                    if (!isset($delhiveryResponse['awb_numbers']) && isset($statusResult['awb_numbers'])) {
                        $delhiveryResponse['awb_numbers'] = $statusResult['awb_numbers'];
                    }
                } else {
                    // Return job_id so client can poll later
                    $response = response()->json([
                        'status' => 'PENDING',
                        'reason' => 'ASYNC_PROCESSING',
                        'message' => 'Manifest is being processed. Use job_id to poll status via GET /manifest?job_id={job_id}',
                        'job_id' => $jobId,
                        'details' => array_merge($delhiveryResponse['data'] ?? [], [
                            'request_id' => $delhiveryResponse['data']['request_id'] ?? null,
                            'success' => true
                        ])
                    ], 202);
                    
                    $executionTime = microtime(true) - $startTime;
                    $this->logApiRequest($request, $response, 'waybill_create', $executionTime);
                    return $response;
                }
            }
            
            // Extract LR number, AWB numbers, and MAWB from response
            $lrNumber = $delhiveryResponse['lr_number'] ?? null;
            $awbNumbers = $delhiveryResponse['awb_numbers'] ?? [];
            $masterWaybill = $delhiveryResponse['master_waybill'] ?? null;
            
            // Use MAWB if available, otherwise fallback to LR number or waybill
            // Store MAWB in waybills and forwordingno fields
            $waybillToStore = $masterWaybill ?? $delhiveryWaybill;
            
            // Use LR number as primary identifier for labels (more reliable than waybill)
            $labelIdentifier = $lrNumber ?? $delhiveryWaybill;

            // ✅ Save booking in DB (use transaction for data consistency)
            $itemNames = collect($payload['Shipment']['items'])->pluck('name')->toArray();
            $contentString = implode(', ', $itemNames);

            \DB::beginTransaction();
            try {
                $booking = booking::create([
                'waybills' => $waybillToStore, // Store MAWB in waybills field
                'lr_number' => $lrNumber, // Store LR number for Delhivery shipments
                'cust_name' => 'Waree',
                'clientid' => $request->attributes->get('clientid'),
                'forwordingno' => $waybillToStore, // Store MAWB in forwordingno field
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
                'refrenceno' => $waybillToStore, // Store MAWB in refrenceno as well
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
                
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

            // Shipping label - Get from Delhivery API with optimized retries
            // Labels may not be immediately available after manifest creation, so we retry
            // Always attempt to get labels when manifest is successfully created
            $shippingLabelUrl = null;
            if ($labelIdentifier) {
                // Optimized: Reduced wait time and retries for faster response
                $maxLabelRetries = $isProduction ? 3 : 5;
                $labelRetryDelay = $isProduction ? 1 : 2; // seconds
                
                // Reduced initial wait - only 1s in production, 2s in test
                if (!$isProduction) {
                    usleep(1000000); // 1 second
                }
                
                for ($labelAttempt = 1; $labelAttempt <= $maxLabelRetries; $labelAttempt++) {
                    try {
                        if ($labelAttempt > 1) {
                            usleep($labelRetryDelay * 1000000); // Convert to microseconds
                        }
                        
                        // Get label URLs from Delhivery API - use LR number if available, otherwise use waybill
                        $labelUrlsResponse = $this->delhiveryService->getLabelUrls(
                            $labelIdentifier,
                            $test
                        );
                        
                        if ($labelUrlsResponse['success'] && !empty($labelUrlsResponse['label_urls'])) {
                            // Download and store labels - pass invoice number to add to PDF
                            $shippingLabelUrl = $this->delhiveryService->downloadAndStoreLabels(
                                $labelIdentifier,
                                $labelUrlsResponse['label_urls'],
                                $booking->id,
                                $test,
                                $booking->invoice_no
                            );
                            
                            if ($shippingLabelUrl) {
                                \Log::info("Delhivery shipping label saved successfully: {$shippingLabelUrl} (attempt {$labelAttempt})");
                                break; // Success, exit retry loop
                            } else {
                                \Log::warning("Failed to download/store Delhivery shipping labels for identifier: {$labelIdentifier} (attempt {$labelAttempt})");
                            }
                        } else {
                            // If this is not the last attempt, continue to retry
                            if ($labelAttempt < $maxLabelRetries) {
                                continue;
                            }
                        }
                    } catch (\Exception $e) {
                        // If this is not the last attempt, continue to retry
                        if ($labelAttempt < $maxLabelRetries) {
                            continue;
                        }
                    }
                }
                
                // If still no label after all retries, continue without label (don't fail request)
                if (!$shippingLabelUrl && !$isProduction) {
                    \Log::warning("Could not retrieve shipping label for identifier: {$labelIdentifier} after {$maxLabelRetries} attempts.");
                }
            }

            // ✅ Success response - Return MAWB instead of LR number
            $response = response()->json([
                'status' => 'SUCCESS',
                'waybill' => $waybillToStore, // Return MAWB (stored in waybills/forwordingno)
                'courierName' => 'Delhivery',
                'shippingLabel' => $shippingLabelUrl
            ], 200);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'waybill_create', $executionTime, $delhiveryWaybill);

            return $response;

        } catch (\Exception $e) {
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
    private function mapToDelhiveryPayload(array $payload, string $invoiceLink, $warehouse, int $test = 1): array
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
        // Determine if dev/test: test = 1 or environment is not production
        $isDev = $test || !app()->environment('production');
        
        $mappedPayload = [
            'lrn' => '',
            'payment_mode' => strtolower($payload['paymentMode']),
            'weight' => (float) ($payload['Shipment']['weight']), // Weight in grams as per API docs
            'freight_mode' => 'fop',
            'dropoff_location' => $dropoffLocation,
            'rov_insurance' => true,
            'invoices' => $invoices,
            'shipment_details' => $shipmentDetails,
            'dimensions' => $dimensions,
            'doc_data' => $docData,
            'fm_pickup' => false,
            'billing_address' => $billingAddress,
        ];
        
        // Remove freight_mode in dev/test environments
        
        
        // Always use pickup_location_name (warehouse name) instead of warehouse_id
        $mappedPayload['pickup_location_name'] = $warehouse->name;
        \Log::info('Using pickup_location_name: ' . $warehouse->name);
        
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
            // Get test value (0 = production, 1 = test) - ensure it's an integer
            $test = (int) $request->attributes->get('test', 1);

            // Find booking by waybill, forwordingno (both contain MAWB for Delhivery), or LR number
            // For Delhivery: API will receive MAWB (stored in waybills/forwordingno), but cancellation requires LR number
            $booking = booking::where('waybills', $waybill)
                ->orWhere('forwordingno', $waybill)
                ->orWhere('lr_number', $waybill)
                ->first();

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

            // Determine service provider
            // Delhivery shipments have lr_number, BlueDart doesn't
            $serviceProvider = null;
            $cancelIdentifier = $waybill;
            
            if (!empty($booking->lr_number)) {
                // Delhivery shipment - use LR number for cancellation (API requires LR, not MAWB)
                $serviceProvider = $this->delhiveryService;
                $cancelIdentifier = $booking->lr_number;
            } else {
                // BlueDart shipment - use waybill number
                $serviceProvider = $this->bluedartService;
            }

            // Step 1: Call service API to cancel waybill
            $cancelResult = $serviceProvider->cancelWaybill($cancelIdentifier, $booking->id, $test);

            // Step 2: If API cancellation successful, update database
            if ($cancelResult['success']) {
                // Update booking status
                $booking->update([
                    'status' => 'CANCELLED'
                ]);

                // Create booking log
                $booking->bookingLogs()->create([
                    'status' => 'CANCELLED',
                    'remark' => 'Pickup cancelled via API request - ' . $serviceProvider->getProviderName(),
                    'bookingno' => $booking->id,
                    'currentstatus' => 'CANCELLED',
                    'createdbyy' => 'API',
                    'deliverydate' => null,
                    'expecteddeliverydate' => null
                ]);

                $response = response()->json([
                    'status' => 'SUCCESS',
                    'waybill' => $waybill,
                    'errorMessage' => 'Pickup is successfully cancelled',
                    'provider' => $serviceProvider->getProviderName(),
                ], 200);
            } else {
                // API cancellation failed
                $errorMsg = $cancelResult['message'] ?? 'Unknown error';
                
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'errorMessage' => 'Failed to cancel waybill: ' . $errorMsg,
                    'provider' => $serviceProvider->getProviderName(),
                    'details' => $cancelResult['data'] ?? []
                ], 400);
            }

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

    /**
     * Request cancellation of a running order by waybill number
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function requestCancellation(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        // Authenticate the request using token
        $authError = $this->authenticateRequest($request);
        if ($authError) {
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $authError, 'cancel_request', $executionTime);
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
            $this->logApiRequest($request, $response, 'cancel_request', $executionTime);
            return $response;
        }

        try {
            $waybill = $request->input('waybill');
            $clientId = $request->attributes->get('clientid');

            // Find booking by waybill
            $booking = booking::where('waybills', $waybill)
                ->orWhere('forwordingno', $waybill)
                ->orWhere('lr_number', $waybill)
                ->first();

            if (!$booking) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'message' => 'Waybill not found'
                ], 404);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'cancel_request', $executionTime, $waybill);
                return $response;
            }

            // Check if order is already cancelled
            if ($booking->status === 'CANCELLED') {
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'message' => 'Order is already cancelled'
                ], 400);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'cancel_request', $executionTime, $waybill);
                return $response;
            }

            // Check if there's already a pending cancellation request
            $existingRequest = CancellationRequest::where('waybill', $waybill)
                ->whereIn('status', [
                    CancellationRequest::STATUS_REQUESTED,
                    CancellationRequest::STATUS_PROCESSING
                ])
                ->first();

            if ($existingRequest) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'message' => 'Cancellation request already exists',
                    'request_id' => $existingRequest->id,
                    'current_status' => $existingRequest->status
                ], 400);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'cancel_request', $executionTime, $waybill);
                return $response;
            }

            // Determine service provider
            $provider = null;
            if (!empty($booking->lr_number)) {
                $provider = 'Delhivery';
            } else {
                $provider = 'BlueDart';
            }

            // Create cancellation request
            $cancellationRequest = CancellationRequest::create([
                'waybill' => $waybill,
                'booking_id' => $booking->id,
                'status' => CancellationRequest::STATUS_REQUESTED,
                'client_id' => $clientId,
                'provider' => $provider,
                'remarks' => $request->input('remarks', 'Cancellation requested via API')
            ]);

            $response = response()->json([
                'status' => 'SUCCESS',
                'message' => 'Cancellation request created successfully',
                'request_id' => $cancellationRequest->id,
                'waybill' => $waybill,
                'cancellation_status' => $cancellationRequest->status,
                'created_at' => $cancellationRequest->created_at->toIso8601String()
            ], 200);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'cancel_request', $executionTime, $waybill);
            
            return $response;

        } catch (\Exception $e) {
            $response = response()->json([
                'status' => 'FAILED',
                'waybill' => $request->input('waybill'),
                'message' => 'Failed to create cancellation request: ' . $e->getMessage()
            ], 500);
            
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'cancel_request', $executionTime, $request->input('waybill'));
            
            return $response;
        }
    }

    /**
     * Get cancellation request status by waybill number
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCancellationStatus(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        // Authenticate the request using token
        $authError = $this->authenticateRequest($request);
        if ($authError) {
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $authError, 'cancel_status', $executionTime);
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
            $this->logApiRequest($request, $response, 'cancel_status', $executionTime);
            return $response;
        }

        try {
            $waybill = $request->input('waybill');

            // Find cancellation request by waybill
            $cancellationRequest = CancellationRequest::where('waybill', $waybill)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$cancellationRequest) {
                $response = response()->json([
                    'status' => 'NOT_FOUND',
                    'waybill' => $waybill,
                    'message' => 'No cancellation request found for this waybill',
                    'cancellation_status' => null
                ], 404);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'cancel_status', $executionTime, $waybill);
                return $response;
            }

            // Get booking details
            $booking = $cancellationRequest->booking;

            // Build booking details in the same format as waybillDetails
            $waybillDetails = null;
            if ($booking) {
                $waybillDetails = [
                    "waybill" => $waybill,
                    "currentStatus" => strtolower($booking->status ?? ""),
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
                                "status" => strtolower($log->status ?? ""),
                                "sub_status" => strtolower($log->currentstatus ?? ""),
                                "remark" => $log->remark ?? "",
                                "location" => strtolower($log->currentstatus ?? ""),
                                "pincode" => "",
                                "city" => "",
                                "state" => "",
                                "country" => "India"
                            ];
                        })->values(),
                    "parent_awb" => "",
                    "rto_awb" => "",
                    "rto_reason" => "",
                    "epod" => $booking->pod
                ];
            }

            $response = response()->json([
                'status' => 'SUCCESS',
                'waybill' => $waybill,
                'request_id' => $cancellationRequest->id,
                'cancellation_status' => $cancellationRequest->status,
                'provider' => $cancellationRequest->provider,
                'remarks' => $cancellationRequest->remarks,
                'error_message' => $cancellationRequest->error_message,
                'created_at' => $cancellationRequest->created_at->toIso8601String(),
                'updated_at' => $cancellationRequest->updated_at->toIso8601String(),
                'waybillDetails' => $waybillDetails
            ], 200);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'cancel_status', $executionTime, $waybill);
            
            return $response;

        } catch (\Exception $e) {
            $response = response()->json([
                'status' => 'FAILED',
                'waybill' => $request->input('waybill'),
                'message' => 'Failed to fetch cancellation status: ' . $e->getMessage()
            ], 500);
            
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'cancel_status', $executionTime, $request->input('waybill'));
            
            return $response;
        }
    }

    /**
     * Get POD (Proof of Delivery) for a waybill
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getPod(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        // Authenticate the request using token
        $authError = $this->authenticateRequest($request);
        if ($authError) {
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $authError, 'pod_get', $executionTime);
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
            $this->logApiRequest($request, $response, 'pod_get', $executionTime);
            return $response;
        }

        try {
            $waybill = $request->input('waybill');

            // Find booking by waybill, forwordingno, or lr_number
            $booking = booking::where('waybills', $waybill)
                ->orWhere('forwordingno', $waybill)
                ->orWhere('lr_number', $waybill)
                ->first();

            if (!$booking) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'message' => 'Waybill not found'
                ], 404);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'pod_get', $executionTime, $waybill);
                return $response;
            }

            // Check if booking is delivered
            if (strtolower($booking->status) !== 'delivered') {
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'message' => 'Booking is not delivered yet. Current status: ' . $booking->status,
                    'current_status' => strtolower($booking->status)
                ], 400);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'pod_get', $executionTime, $waybill);
                return $response;
            }

            // Check if POD exists
            if (empty($booking->pod)) {
                $response = response()->json([
                    'status' => 'FAILED',
                    'waybill' => $waybill,
                    'message' => 'POD not available for this waybill'
                ], 404);
                
                $executionTime = microtime(true) - $startTime;
                $this->logApiRequest($request, $response, 'pod_get', $executionTime, $waybill);
                return $response;
            }

            // Build POD URL
            $baseUrl = 'https://track.sbexpresscargo.com/storage/';
            $podPath = ltrim($booking->pod, '/'); // avoid double slashes
            $podUrl = $baseUrl . $podPath;

            $response = response()->json([
                'status' => 'SUCCESS',
                'waybill' => $waybill,
                'message' => 'POD retrieved successfully',
                'pod_url' => $podUrl,
                'delivery_status' => strtolower($booking->status)
            ], 200);

            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'pod_get', $executionTime, $waybill);
            
            return $response;

        } catch (\Exception $e) {
            $response = response()->json([
                'status' => 'FAILED',
                'waybill' => $request->input('waybill'),
                'message' => 'Failed to fetch POD: ' . $e->getMessage()
            ], 500);
            
            $executionTime = microtime(true) - $startTime;
            $this->logApiRequest($request, $response, 'pod_get', $executionTime, $request->input('waybill'));
            
            return $response;
        }
    }

}