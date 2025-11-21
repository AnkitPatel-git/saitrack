<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pincode;
use Illuminate\Support\Facades\DB;
use App\Models\booking as Booking;
use App\Http\Resources\BookingResource;
use App\Models\bookinglog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::query();
           // Filter bookings for the authenticated user
    $query->where('assign_to', auth()->id());
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('booking_date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('sort_by') && $request->has('order')) {
            $query->orderBy($request->sort_by, $request->order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginate the results (default 10 per page)
        $pageSize = $request->get('page_size', 10);
        $bookings = $query->paginate($pageSize);

        // return BookingResource::collection($bookings);
        return response()->json([
                'status' => $bookings->isNotEmpty() ? true : false,
                'data' => BookingResource::collection($bookings),
                'links' => [
                    'first' => $bookings->url(1),
                    'last' => $bookings->url($bookings->lastPage()),
                    'prev' => $bookings->previousPageUrl(),
                    'next' => $bookings->nextPageUrl(),
                ],
                'meta' => [
                    'current_page' => $bookings->currentPage(),
                    'from' => $bookings->firstItem(),
                    'last_page' => $bookings->lastPage(),
                    'links' => $bookings->linkCollection(),
                    'path' => $bookings->url($bookings->currentPage()),
                    'per_page' => $bookings->perPage(),
                    'to' => $bookings->lastItem(),
                    'total' => $bookings->total(),
                ]
            ]);
    }
    
 public function dashboard(Request $request)
{
    $query = Booking::query();
     // Filter bookings for the authenticated user
    $query->where('assign_to', auth()->id());

    // Filter by date range if start_date and end_date are provided
    if ($request->has('start_date') && $request->has('end_date')) {
        $query->whereBetween('booking_date', [$request->start_date, $request->end_date]);
    }

    // Count statuses with case-insensitive logic
    $totalCount = (clone $query)
        ->count();
        
    $completedCount = (clone $query)
        ->whereRaw('LOWER(status) = ?', [strtolower('delivered')])
        ->count();

    $ongoingCount = (clone $query)
        ->whereRaw('LOWER(status) != ?', [strtolower('delivered')])
        ->count();

    // Static values for now, update logic as required
    $loadedCount = 0;
    $notificationCount = 0;

    // Return response
    return response()->json([
        'status' => $completedCount || $ongoingCount || $loadedCount ? true : false,
        'data' => [
            'total' => $totalCount,
            'completed' => $completedCount,
            'ongoing' => $ongoingCount,
            'loaded' => $loadedCount,
            'notification' => $notificationCount,
        ]
    ]);
}

public function sticker_print(Request $request)
{
    $forwordingno = preg_replace('/\s+/', '', $request->awb_no);
    $split = explode(",", $forwordingno);

    // Fetch only the required fields
    $datas = Booking::whereIn('forwordingno', $split)
        ->select([
            'pickuplocation',
            'deliverylocation',
            'charg_weight',
            'pices',
            'booking_date',
            'dims',
            'dimension',
            'forwordingno',
            'delivery_type',
            'invoice_no',
            'value',
            'waybills',
            'con_client_name',
            'receiveraddress',
            'receiver_pincode',
            'receivercontactno',
            'content',
        ])
        ->get();

    return response()->json([
        'status' => $datas->isNotEmpty(), // Returns true if there's any data, false otherwise
        'data' => $datas
    ]);
}




    public function show($id)
{
     $userId = auth()->id();

    $booking = Booking::find($id);

    if (!$booking) {
        return response()->json([
            'status' => false,
            'data' =>''
        ], 404);
    }

    // Return the booking data with status true
    return response()->json([
        'status' => true,
        'data' => new BookingResource($booking)
    ]);
}




public function createBooking(Request $request)
{
    // Validate incoming request data
    $validator = Validator::make($request->all(), [
        'modeoftrans' => 'required|string',
        'forwordingno' => 'required|string|unique:booking,forwordingno',
        'cust_name' => 'required|string',
        'pickuplocation' => 'required|string',
        'deliverylocation' => 'required|string',
        'product_type' => 'required|string',
        'weight' => 'required|numeric',
        'vol_weight' => 'required|numeric',
        'charg_weight' => 'required|numeric',
        'client_name' => 'required|string',
        'pickupaddress' => 'required|string',
        'pickup_pincode' => 'required|string',
        'sendercontactno' => 'required|string',
        'con_client_name' => 'required|string',
        'receiveraddress' => 'required|string',
        'receiver_pincode' => 'required|string',
        'receivercontactno' => 'required|string',
        'rto_office_name' => 'nullable|string',
        'rto_address' => 'nullable|string',
        'rto_pincode' => 'nullable|string',
        'refrenceno' => 'nullable|string',
        'content' => 'nullable|string',
        'pices' => 'nullable|numeric',
        'value' => 'nullable|numeric',
        'invoice_no' => 'nullable|string',
        'waybills' => 'nullable|string',
        'dims' => 'nullable|string',
        'dimension' => 'nullable|array',
        'service_type' => 'required|string',
        'delivery_type' => 'required|string',
        'booking_date' => 'required|date_format:d/m/Y H:i:s',
    ]);


    // If validation fails, return error response
    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }
     
    // Begin database transaction
    DB::beginTransaction();

    try {
        // Creating new booking record
        $newBooking = new Booking();
        $newBooking->cust_name = $request->cust_name;
        $newBooking->modeoftrans = $request->modeoftrans;
        $newBooking->forwordingno = $request->forwordingno;
        $newBooking->pickuplocation = $request->pickuplocation;
        $newBooking->deliverylocation = $request->deliverylocation;
        $newBooking->product_type = $request->product_type;
        $newBooking->weight = $request->weight;
        $newBooking->vol_weight = $request->vol_weight;
        $newBooking->charg_weight = $request->charg_weight;
        $newBooking->client_name = $request->client_name;
        $newBooking->pickupaddress = $request->pickupaddress;
        $newBooking->pickup_pincode = $request->pickup_pincode;
        $newBooking->sendercontactno = $request->sendercontactno;
        $newBooking->con_client_name = $request->con_client_name;
        $newBooking->receiveraddress = $request->receiveraddress;
        $newBooking->receiver_pincode = $request->receiver_pincode;
        $newBooking->receivercontactno = $request->receivercontactno;
        $newBooking->rto_office_name = $request->rto_office_name;
        $newBooking->rto_address = $request->rto_address;
        $newBooking->rto_pincode = $request->rto_pincode;
        $newBooking->refrenceno = $request->refrenceno;
        $newBooking->content = $request->content;
        $newBooking->pices = $request->pices;
        $newBooking->value = $request->value;
        $newBooking->invoice_no = $request->invoice_no;
        $newBooking->waybills = $request->waybills;
        $newBooking->dims = $request->dims;
        $newBooking->dimension = $request->dimension;
        $newBooking->service_type = $request->service_type;
        $newBooking->delivery_type = $request->delivery_type;
        $newBooking->assign_to =auth()->id();
        $newBooking->created_by =auth()->id();
        $newBooking->status = 'Booked';
  
        // Process booking date & time
        $bookingDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $request->booking_date)->format('Y-m-d H:i:s');
        $newBooking->booking_date = $bookingDateTime;

        // Set pickup city from pincode
        $newBooking->pickupcity = Pincode::where('pincode', $request->pickup_pincode)->value('district');

        // Set receiver city and state from receiver pincode
        $recPincodeData = Pincode::where('pincode', $request->receiver_pincode)->first();
        if ($recPincodeData) {
            $newBooking->receivercity = $recPincodeData->district;
            $newBooking->receiverstate = $recPincodeData->state;
        } else {
            $newBooking->receivercity = '';
            $newBooking->receiverstate = '';
        }

        // Save the booking record
        $newBooking->save();
        $existingRecord = Booking::where('forwordingno', $request->forwordingno)->first();

        // Log the booking status
        $newBookingLog = new BookingLog();
        $newBookingLog->bookingno = $newBooking->id;
        $newBookingLog->currentstatus = 'Booked';  // You can replace this based on logic
        $newBookingLog->status = 'Booked';
        $newBookingLog->remark = 'Booked';
        $newBookingLog->deliverydate = $bookingDateTime;
        $newBookingLog->createdbyy = Auth::id();  // Assuming the user is authenticated
        $newBookingLog->save();

        // Commit the transaction
        DB::commit();

        // Return a successful response
        return response()->json([
            'status' => 'success',
            'message' => 'Booking created successfully',
            'data' => $newBooking
        ], 201);

    } catch (\Exception $e) {
        // Rollback transaction in case of error
        DB::rollBack();

        // Return error response
        return response()->json([
            'status' => 'error',
            'message' => 'An error occurred while processing the data.',
            'error' => $e->getMessage()
        ], 500);
    }
}


    public function updateStatus(Request $request, $id)
    {
        // Validate incoming data
        $request->validate([
            'status' => 'string',
            'currentaddress' => 'string',
            'remark' => 'string',
            'deliverydate' => 'string',
            'expecteddeliverydate' => 'string',
        ]);
         $bookingDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $request->deliverydate)->format('Y-m-d H:i:s');
        $bookingDateTime2 = Carbon::createFromFormat('d/m/Y H:i:s', $request->expecteddeliverydate)->format('Y-m-d H:i:s');
        // Find and update the booking
        $booking = Booking::findOrFail($id);
         $data = [
                            'bookingno' => $booking->id,
                            'currentstatus' => $request->currentaddress,
                            'createdbyy' => Auth::id(),
                            'status' => $request->status,
                            'remark' => $request->remark,
                            'deliverydate' => $bookingDateTime,
                            'expecteddeliverydate' => $bookingDateTime2,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
        DB::table('bookinglog')->insert($data);
         Booking::where('id', $id)->update(['status' => $request->status]);
        // Return updated booking as resource
        return new BookingResource($booking);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => 'Booking deleted successfully'], 200);
    }
   
}
