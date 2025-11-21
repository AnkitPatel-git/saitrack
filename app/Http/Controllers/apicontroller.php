<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\booking;
use App\Models\ApiLog;
use App\Models\bookinglog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
class apicontroller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     public function login(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check user credentials
        if (!Auth::attempt(['mobile' => $request->mobile, 'password' => $request->password])) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        // Check if the user is active
        if ($user->status !== 1) {
            return response()->json(['error' => 'Account is inactive.'], 403);
        }

        // Generate a token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ]);
    }
    
    public function index()
    {
    $output['User']='Authenticated';
    $output['response']=true;
    $output['message']='Data Found';
    $output['data'] = booking::orderBy('id', 'DESC')->get();
    foreach($output['data'] as $data){
        $data['ShipmentLog'] = bookinglog::where('bookingno', $data->id)->select('currentstatus', 'created_at')->get();
    }
    return response()->json($output);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
public function show($id)
{
    
    $booking = Booking::where('forwordingno', $id)
        ->select('id', 'forwordingno','deliverylocation', 'pickuplocation','status','product_type','content','weight','vol_weight','charg_weight','pices','value','service_type','delivery_type','invoice_no','waybills','dims',
        'pickupaddress','pickup_name','pickupcity','pickup_pincode','sendercontactno','con_client_name','receiveraddress','receiverstate','receivercity','receiver_pincode',
        'receivercontactno','booking_date','rto_office_name', 'rto_address', 'rto_pincode', 'created_at', 'updated_at')
        ->with(['bookingLogs' => function ($query) {
            $query->select('bookingno', 'status','remark','currentstatus', 'deliverydate','expecteddeliverydate', 'created_at', 'updated_at')
                  ->orderBy('created_at', 'DESC');
        }])
        ->first();

    if (!$booking) {
        return response()->json(['error' => 'Booking not found'], 404);
    }
     $bookingArray = $booking->toArray();
    $bookingData = Arr::except($bookingArray, ['id']);

    
    if (isset($bookingData['booking_logs'])) {
        $bookingData['booking_logs'] = array_map(function ($log) {
            return Arr::except($log, ['bookingno']);
        }, $bookingData['booking_logs']);
    }

    $output['data'] = $bookingData;

    return response()->json($output, 200);
}

public function pod($id)
{
    $booking = Booking::where('forwordingno', $id)
        ->select('pod', 'status')
        ->first();

    if (!$booking) {
        return response()->json([
            'success' => false,
            'message' => 'Booking not found'
        ], 404);
    }

    if ($booking->status !== "Delivered") {
        return response()->json([
            'success' => false,
            'message' => 'Booking not Delivered'
        ], 403); // 403 is more appropriate for "forbidden"
    }

    $baseUrl = 'https://track.sbexpresscargo.com/storage/';
    $podPath = ltrim($booking->pod, '/'); // avoid double slashes
    $podUrl = $baseUrl . $podPath;

    return response()->json([
        'success' => true,
        'message' => 'POD found successfully',
        'data' => [
            'pod_url' => $podUrl
        ]
    ], 200);
}



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     
 public function ndr_update(Request $request)
{
      // Define validation rules
    $validator = Validator::make($request->all(), [
        'action' => 'required|string|in:INITIATE_RTO',
        'waybill' => 'required|string',
        'address' => 'required|string',
        'drop_pincode' => 'required|string|digits:6', // Assuming pincode is a 6-digit number
        'phone_number' => 'required|string|digits:10', // Assuming phone number is a 10-digit number
        'preferred_date' => 'required|date|after:today', // Date should be in the future
    ]);

    // Check if validation fails
    if ($validator->fails()) {
        // Return a JSON response with error messages and status code 412
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 412);
    }
    
    // Retrieve the Booking data using the waybill
    $data = Booking::where('waybills', $request->waybill)->first();

    // Check if the booking data is found
    if (!$data) {
        return response()->json("Waybill not found", 404);
    }

    // Handle the "INITIATE_RTO" action
    if ($request->action === "INITIATE_RTO") {
        // Perform the update on the Booking model
         $updated = Booking::where('waybills', $request->waybill)->update([
            'rto_address' => $request->address,
            'rto_pincode' => $request->drop_pincode,
            'preferred_date' => $request->preferred_date,
            'rto_phone_number' => $request->phone_number,
        ]);

        // Check if the update was successful
        if ($updated) {
            return response()->json("RTO update successful", 200);
        } else {
            return response()->json("Failed to update RTO details", 500);
        }
    }
    
    // Return a response for an unrecognized action
    return response()->json("Action not recognized", 400);
}



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function runWareeApijob()
{
    $authResponse = Http::withHeaders([
        'Content-Type' => 'Application/json'
    ])->get('https://waaree.unicommerce.co.in/oauth/token?grant_type=password&client_id=my-trusted-client&username=ambikaprasad@sbexpresscargo.com&password=P@ssw0rd$321');

     if (!$authResponse->successful()) {
        return response()->json([
            'error' => 'Auth failed',
            'response' => $authResponse->body() 
        ], 500);
    }
    $token = $authResponse->json('access_token') ?? $authResponse->json('accessToken');
  $fromDate = Carbon::now()->subDays(30)->startOfDay()->toIso8601String(); // 30 days ago
$toDate = Carbon::now()->endOfDay()->toIso8601String(); // today
   
    $ordersResponse = Http::withHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $token,
        ])->post('https://waaree.unicommerce.co.in/services/rest/v1/oms/saleOrder/search', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
        
    foreach ($ordersResponse['elements'] as $order) {
    ApiLog::updateOrInsert(
        ['code' => $order['code']], // Match by code
        [
            'displayOrderCode'    => $order['displayOrderCode'],
            'channel'             => $order['channel'],
            'source'              => $order['source'],
            'displayOrderDateTime'=> (string)$order['displayOrderDateTime'],
            'status'              => $order['status'],
            'created'             => (string)$order['created'],
            'updated'             => (string)$order['updated'],
            'fulfillmentTat'      => (string)$order['fulfillmentTat'],
            'notificationEmail'   => $order['notificationEmail'],
            'notificationMobile'  => $order['notificationMobile'],
            'customerGSTIN'       => $order['customerGSTIN'],
            'client'              =>"waaree"
        ]
    );
}
    
    return response()->json([
        'message' => 'Fetched info',
        'auth_response' => $ordersResponse->body() 
    ], 200);
}
}
