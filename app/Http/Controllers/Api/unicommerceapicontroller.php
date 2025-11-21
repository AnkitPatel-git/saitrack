<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\booking;
use App\Models\bookinglog;
use App\Models\ApiRequestLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\BlueDartService; 

class UnicommerceApiController extends Controller
{
     protected $bluedartService;

    public function __construct(BlueDartService $bluedartService)
    {
        $this->bluedartService = $bluedartService;
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
        return Webhook::where('token', $token)
                     ->where('is_active', 1)
                     ->first();
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
        

        /**
         * ✅ Fixed BlueDart Mapper - Complete mapping according to BlueDart API requirements
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
                'ReturnAddress1' => $payload['returnAddressDetails']['address1'],
                'ReturnAddress2' => $payload['returnAddressDetails']['address2'] ?? '',
                'ReturnAddress3' => '',
                'ReturnAddressinfo' => '',
                'ReturnContact' => $payload['returnAddressDetails']['name'],
                'ReturnEmailID' => $payload['returnAddressDetails']['email'] ?? '',
                'ReturnLatitude' => isset($payload['returnAddressDetails']['latitude']) && $payload['returnAddressDetails']['latitude'] !== '' 
    ? number_format((float) $payload['returnAddressDetails']['latitude'], 6, '.', '') 
    : null,

'ReturnLongitude' => isset($payload['returnAddressDetails']['longitude']) && $payload['returnAddressDetails']['longitude'] !== '' 
    ? number_format((float) $payload['returnAddressDetails']['longitude'], 6, '.', '') 
    : null,
                'ReturnMaskedContactNumber' => '',
                'ReturnMobile' => $payload['returnAddressDetails']['phone'],
                'ReturnPincode' => $payload['returnAddressDetails']['pincode'],
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