<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\booking;
use App\Models\Pincode;
use App\Models\bookinglog;
use App\Models\ApiLog;
use Auth;
use Session;
use DB;
use file;
use DataTables;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PincodeImport;

class couriercontroller extends Controller
{
    // Column mapping for BulkBooking - makes it easier to maintain
    private const BOOKING_COLUMNS = [
        'modeoftrans' => 1,
        'forwordingno' => 2,
        'cust_name' => 3,
        'pickuplocation' => 4,
        'deliverylocation' => 5,
        'product_type' => 6,
        'weight' => 8,
        'vol_weight' => 9,
        'charg_weight' => 10,
        'client_name' => 11,
        'pickupaddress' => 12,
        'pickup_pincode' => 13,
        'sendercontactno' => 14,
        'con_client_name' => 15,
        'receiveraddress' => 16,
        'receiver_pincode' => 17,
        'receivercontactno' => 18,
        'rto_office_name' => 19,
        'rto_address' => 20,
        'rto_pincode' => 21,
        'booking_date' => 22,
        'expected_delivery_date' => 23,
        'refrenceno' => 24,
        'content' => 25,
        'pices' => 26,
        'value' => 27,
        'invoice_no' => 28,
        'waybills' => 29,
        'dims' => 30,
        'service_type' => 31,
        'delivery_type' => 32,
        'claimid' => 33,
    ];

    private const BOOKING_START_ROW = 4;
    private const BOOKING_MIN_COLUMNS = 34;

    // Column mapping for Bulkupdate
    private const UPDATE_COLUMNS = [
        'forwordingno' => 0,
        'currentstatus' => 1,
        'remark' => 2,
        'status' => 3,
        'deliverydate' => 4,
        'expecteddeliverydate' => 5,
    ];

    private const UPDATE_START_ROW = 2;
    private const UPDATE_MIN_COLUMNS = 6;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
 
    public function index()
        {
        $datas = booking::select(['id', 'booking_date', 'created_at', 'forwordingno', 'cust_name', 'pickuplocation', 'deliverylocation', 'status',]);

        return Datatables::of($datas)
            ->addColumn('action', function ($data) {
                return view('admin.booking.actions', compact('data'))->render();
            })
            ->make(true);
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
        $datas = booking::where('forwordingno', $request->forwordingno)->exists();
        if ($datas) {
            session()->flash('alert-warning', 'Forwarding number already exists');
            return redirect('/Admin/booking');
        }
        $newscanpoint = new booking;
        $newscanpoint->cust_name = $request->cust_name;
        $newscanpoint->forwordingno = $request->forwordingno;
        $newscanpoint->refrenceno = $request->refrenceno;
        $newscanpoint->pickuplocation = $request->pickuplocation;
        $newscanpoint->deliverylocation = $request->deliverylocation;
        $newscanpoint->product_type = $request->product_type;
        $newscanpoint->content = $request->content;
        $newscanpoint->weight = $request->weight;
        $newscanpoint->vol_weight = $request->vol_weight;
        $newscanpoint->charg_weight = $request->charg_weight;
        $newscanpoint->pices = $request->pices ?? 1;
        $newscanpoint->client_name = $request->client_name;
        $newscanpoint->pickupaddress = $request->pickupaddress;
        $newscanpoint->pickup_name = $request->pickup_name;
        $newscanpoint->pickupcity = $request->pickupcity;
        $newscanpoint->pickup_pincode = $request->pickup_pincode;
        $newscanpoint->sendercontactno = $request->sendercontactno;
        $newscanpoint->con_client_name = $request->con_client_name;
        $newscanpoint->receiveraddress = $request->receiveraddress;
        $newscanpoint->receiverstate = $request->reciverstate;
        $newscanpoint->receivercity = $request->receivercity;
        $newscanpoint->receiver_pincode = $request->receiver_pincode;
        $newscanpoint->receivercontactno = $request->receivercontactno;
        $newscanpoint->status = 'Booked';
        $newscanpoint->booking_date = $request->booking_date;
        
        // Store dimensions (Length, Breadth, Height) in dimension array
        if ($request->length || $request->breadth || $request->height) {
            $newscanpoint->dimension = [
                'l' => $request->length ? (float) $request->length : null,
                'b' => $request->breadth ? (float) $request->breadth : null,
                'h' => $request->height ? (float) $request->height : null,
            ];
        }
        
        $newscanpoint->save();
      
        $newentry = new bookinglog;
        $newentry->bookingno = $newscanpoint->id;
        $newentry->currentstatus = $request->pickuplocation;
        $newentry->status = 'Booked';
        $newentry->remark = 'Booked';
        $newentry->createdbyy = Auth::id();
        $newentry->save();

        session()->flash('alert-success', 'Booking Created Successfully');
        return redirect('/Admin/booking');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = booking::where('id',$id)->first();
        if(!$data){
            session()->flash('alert-warning', 'Wrong forwording Number');
            return redirect('/Admin/booking');
            }
        $datalogs = bookinglog::where('bookingno', $data->id)->get();
        if($data->status =="Delivered"){
            return view('admin.booking.show',compact('data','datalogs'));
        }
        return view('admin.booking.edit',compact('data','datalogs'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showdetails($id)
    {
      $datas= booking::find($id);  
          return view('admin.booking.show',compact('data','datas'));
    }
        public function uploadpod($id)
    {
      $data= booking::find($id);  
     
          return view('admin.booking.uploadpod', compact('data'));
    }
 public function submitpod(Request $request, $id)
{
    // Validate the request
    $request->validate([
        'img_file' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    // Find the booking
    $booking = Booking::find($id);

    if ($booking) {
        // Check if a file is uploaded
        if ($request->hasFile('img_file')) {
            // Get the uploaded file
            $file = $request->file('img_file');

            // Generate a unique filename with timestamp
            $fileName = time() . '_' . $file->getClientOriginalName();

            // Define the destination path
            $destinationPath = public_path('storage/image/pod/' . date('Y') . '/' . date('m'));

            // Ensure the directory exists
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Move the file to the destination path
            $file->move($destinationPath, $fileName);

            // Save the relative file path in the database
            $relativePath = 'image/pod/' . date('Y') . '/' . date('m') . '/' . $fileName;
            $booking->pod = $relativePath;
            $booking->save();

            // Flash success message
            session()->flash('alert-success', 'POD uploaded and booking updated successfully.');
        } else {
            session()->flash('alert-danger', 'File upload failed.');
        }
    } else {
        session()->flash('alert-danger', 'Booking not found.');
    }

    // Redirect back
    return redirect('/Admin/booking');
}



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $request->validate([
        'currentstatus' => 'required',
        'status' => 'required',
    ]);
    
    $data = booking::where('id', $id)->exists();
    
    if (!$data) {
        session()->flash('alert-warning', 'Booking Not Found');
        return back();
    }
    
    booking::where('id', $id)->update(['status' => $request->status]);
    
    // Retrieve the last expected delivery date if not provided in the request
    $lastExpectedDeliveryDate = bookinglog::where('bookingno', $id)
                                          ->orderBy('created_at', 'desc')
                                          ->value('expecteddeliverydate');

    $newentry = new bookinglog;
    $newentry->bookingno = $id;
    $newentry->currentstatus = $request->currentstatus;
    $newentry->createdbyy = Auth::id();
    $newentry->status = $request->status;
    $newentry->remark = $request->remark;
    $newentry->deliverydate = $request->deliverydate;
    $newentry->expecteddeliverydate = $request->expecteddeliverydate ?? $lastExpectedDeliveryDate;
    $newentry->save();
    
    session()->flash('alert-success', 'Booking Updated Successfully');
    return redirect('/Admin/booking');
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateall(Request $request, $id)
    {
        $booking = booking::findOrFail($id);
 
    $existingBooking = booking::where('forwordingno', $request->forwordingno)->where('id', '!=', $id)->exists();
    if ($existingBooking) {
        session()->flash('alert-warning', 'Forwarding number already exists');
        return redirect()->back();
    }  
    $updateData = [
        'cust_name' => $request->cust_name,
        'forwordingno' => $request->forwordingno,
        'refrenceno' => $request->refrenceno,
        'pickuplocation' => $request->pickuplocation,
        'deliverylocation' => $request->deliverylocation,
        'product_type' => $request->product_type,
        'content' => $request->content,
        'weight' => $request->weight,
        'vol_weight' => $request->vol_weight,
        'charg_weight' => $request->charg_weight,
        'pices' => $request->pices ?? 1,
        'client_name' => $request->client_name,
        'pickupaddress' => $request->pickupaddress,
        'pickup_name' => $request->pickup_name,
        'pickupcity' => $request->pickupcity,
        'pickup_pincode' => $request->pickup_pincode,
        'sendercontactno' => $request->sendercontactno,
        'con_client_name' => $request->con_client_name,
        'receiveraddress' => $request->receiveraddress,
        'receiverstate' => $request->receiverstate,
        'receivercity' => $request->receivercity,
        'receiver_pincode' => $request->receiver_pincode,
        'receivercontactno' => $request->receivercontactno,
        'booking_date' => $request->booking_date,
    ];
    
    // Store dimensions (Length, Breadth, Height) in dimension array
    if ($request->length || $request->breadth || $request->height) {
        $updateData['dimension'] = [
            'l' => $request->length ? (float) $request->length : null,
            'b' => $request->breadth ? (float) $request->breadth : null,
            'h' => $request->height ? (float) $request->height : null,
        ];
    }
    
    $booking->update($updateData);

    session()->flash('alert-success', 'Booking updated successfully');
    return redirect('/Admin/booking');
}
    

    
    public function invoice($id)
    {
 
            $datas= booking::find($id); 
            return view('admin/booking/invoice', compact('datas'));  
    }
    
     public function wareeinvoice($id)
    {
 
            $datas= booking::find($id); 
            return view('admin/booking/wareeinvoice', compact('datas'));  
    }
    
     public function deleteBooking($id)
    {

    $booking = Booking::find($id);

    if ($booking) {
        bookinglog::where('bookingno', $booking->id)->delete();
        $booking->delete();

        session()->flash('alert-success', 'Booking and associated logs successfully deleted');
    } else {
        session()->flash('alert-danger', 'Booking not found');
    }

    return redirect('/Admin/booking');
    }
    
    public function BulkPincode(Request $request)
    {
        $this->validateExcelUpload($request);

        $filePath = $this->handleFileUpload($request);

        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }
        
        Excel::import(new PincodeImport, $filePath);
        
        return redirect('/Admin/Pincode')->with('alert-success', 'Pincode data added successfully');
    }

    public function BulkBooking(Request $request)
{ 
    try {
        $this->validateExcelUpload($request);
        $filePath = $this->handleFileUpload($request);
     
        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }

        $sheet = $this->loadExcelSheet($filePath);
        if (!$sheet) {
            return redirect()->back()->with('alert-error', 'Failed to load Excel file');
        }

        $sheetData = $this->readExcelSheet($sheet, 'A' . self::BOOKING_START_ROW . ':AH');
        $filteredData = $this->filterEmptyRows($sheetData);
        
        if (empty($filteredData)) {
            Log::warning('BulkBooking: No data found in Excel file after filtering');
            return redirect()->back()->with('alert-error', 'No valid data found in the Excel file. Please ensure the file contains data starting from row ' . self::BOOKING_START_ROW . '.');
        }
        
        Log::info('BulkBooking: Processing ' . count($filteredData) . ' rows');
        
        $result = $this->processBookingRows($filteredData);
        
        // Check if processing failed (error message returned)
        if (isset($result['processed']) && $result['processed'] == 0 && isset($result['skipped']) && $result['skipped'] == 0) {
            return redirect()->back()->with('alert-error', $result['message']);
        }
        
        return redirect('/Admin/booking')->with('alert-success', $result['message']);
        
    } catch (\Carbon\Exceptions\InvalidFormatException $e) {
        $this->rollbackTransaction();
        Log::error('BulkBooking: Date format error - ' . $e->getMessage());
        return redirect()->back()->with('alert-error', 'Date format error: ' . $e->getMessage() . '. Please check your date formats (dd/mm/yyyy for dates, dd/mm/yyyy HH:mm:ss for date-time).');
    } catch (\Illuminate\Database\QueryException $e) {
        $this->rollbackTransaction();
        Log::error('BulkBooking: Database error - ' . $e->getMessage());
        return redirect()->back()->with('alert-error', 'Database error: ' . $e->getMessage() . '. Please check your data and try again.');
    } catch (\Exception $e) { 
        $this->rollbackTransaction();
        Log::error('BulkBooking: Unexpected error - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
        return redirect()->back()->with('alert-error', 'Error occurred: ' . $e->getMessage() . '. Please check the logs for more details.');
    }
}

    public function Bulkupdate(Request $request)
{
    try {
        $this->validateExcelUpload($request);
        $filePath = $this->handleFileUpload($request);

        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }

        $sheet = $this->loadExcelSheet($filePath);
        if (!$sheet) {
            return redirect()->back()->with('alert-error', 'Failed to load Excel file');
        }

        $sheetData = $this->readExcelSheet($sheet, 'A' . self::UPDATE_START_ROW . ':F');
        $filteredData = $this->filterEmptyRows($sheetData);

        if (empty($filteredData)) {
            return redirect()->back()->with('alert-error', 'No valid data found in the Excel file.');
        }

        $result = $this->processUpdateRows($filteredData);
        
        if (!$result['success']) {
            return redirect()->back()->with('alert-error', $result['message']);
        }

        return redirect('/Admin/booking')->with('alert-success', $result['message']);
        
    } catch (\Exception $e) {
        $this->rollbackTransaction();
        Log::error('Bulkupdate: Error - ' . $e->getMessage());
        return redirect()->back()->with('alert-error', 'An error occurred while processing the data: ' . $e->getMessage());
    }
}

/**
 * Process update rows from Excel data
 */
private function processUpdateRows(array $filteredData): array
{
    DB::beginTransaction();
    
    $updatedCount = 0;
    $skippedCount = 0;
    
    try {
        foreach ($filteredData as $rowIndex => $row) {
            $rowNumber = $rowIndex + self::UPDATE_START_ROW + 1;
            
            // Validate row structure
            if (count($row) < self::UPDATE_MIN_COLUMNS) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Row {$rowNumber}: Insufficient data columns. Expected at least " . self::UPDATE_MIN_COLUMNS . " columns."
                ];
            }
            
            // Check for required fields
            $forwordingno = $row[self::UPDATE_COLUMNS['forwordingno']] ?? null;
            $status = $row[self::UPDATE_COLUMNS['status']] ?? null;
            
            if (empty($forwordingno) || empty($status)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => "Row {$rowNumber}: Required fields (Forwarding No or Status) are missing."
                ];
            }

            $existingRecord = booking::where('forwordingno', $forwordingno)
                ->where('status', '!=', 'Delivered')
                ->first();
                 
            if ($existingRecord) {
                // Update booking status
                booking::where('id', $existingRecord->id)->update(['status' => $status]);
                
                // Parse dates
                $deliveryDate = $this->parseExcelDate($row[self::UPDATE_COLUMNS['deliverydate']] ?? null);
                $expectedDeliveryDate = $this->parseExcelDate($row[self::UPDATE_COLUMNS['expecteddeliverydate']] ?? null);
                
                // Create booking log entry
                DB::table('bookinglog')->insert([
                    'bookingno' => $existingRecord->id,
                    'currentstatus' => $row[self::UPDATE_COLUMNS['currentstatus']] ?? null,
                    'createdbyy' => Auth::id(),
                    'status' => $status,
                    'remark' => $row[self::UPDATE_COLUMNS['remark']] ?? null,
                    'expecteddeliverydate' => $expectedDeliveryDate,
                    'deliverydate' => $deliveryDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $updatedCount++;
            } else {
                $skippedCount++;
                Log::info("Bulkupdate: Skipping row {$rowNumber} - Forwarding No {$forwordingno} not found or already delivered");
            }
        }

        DB::commit();
        
        $message = "Booking update completed. {$updatedCount} booking(s) updated.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} booking(s) skipped.";
        }
        
        return ['success' => true, 'message' => $message];
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Bulkupdate: Processing error - ' . $e->getMessage());
        return ['success' => false, 'message' => 'Error processing data: ' . $e->getMessage()];
    }
}

/**
 * Parse Excel date (handles both Excel serial dates and string dates)
 */
private function parseExcelDate($value): ?string
{
    if (empty($value)) {
        return null;
    }
    
    // Check if it's an Excel serial date (numeric)
    if (is_numeric($value)) {
        try {
            $excelStartDate = Carbon::create(1899, 12, 30);
            return $excelStartDate->addDays(floor($value))
                ->addSeconds(($value - floor($value)) * 86400)
                ->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::warning('Failed to parse Excel serial date: ' . $value);
            return null;
        }
    }
    
    // Try to parse as string date
    $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'];
    
    foreach ($formats as $format) {
        try {
            return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            continue;
        }
    }
    
    Log::warning('Failed to parse date: ' . $value);
    return null;
}


public function bulkreport(Request $request)
{
    $datas = $this->getBookingsFromRequest($request);
    
    if ($datas === null) {
        return redirect()->back()->with('alert-error', 'Failed to process request. Please provide forwarding numbers or upload a valid Excel file.');
    }

    return view('admin/booking/Bulkinvoice', compact('datas'));
}
    
public function bulksticker(Request $request)
{
    $datas = $this->getBookingsFromRequest($request);
    
    if ($datas === null) {
        return redirect()->back()->with('alert-error', 'Failed to process request. Please provide forwarding numbers or upload a valid Excel file.');
    }
      
    return view('admin/bulkreport/bulksticker', compact('datas'));
}

public function bulkreportwaree(Request $request)
{
    $datas = $this->getBookingsFromRequest($request);
    
    if ($datas === null) {
        return redirect()->back()->with('alert-error', 'Failed to process request. Please provide forwarding numbers or upload a valid Excel file.');
    }

    return view('admin/booking/wareebulkinvoice', compact('datas'));
}

/**
 * Get bookings from request (either from text input or Excel file)
 */
private function getBookingsFromRequest(Request $request)
{
    if (!empty($request->forwordingno)) {
        // Get from comma-separated text input
        $forwordingno = preg_replace('/\s+/', '', $request->forwordingno);
        $split = array_filter(explode(",", $forwordingno));
        
        if (empty($split)) {
            return null;
        }
        
        return booking::whereIn('forwordingno', $split)->get();
    }
    
    // Get from Excel file
    try {
        $this->validateExcelUpload($request);
        $filePath = $this->handleFileUpload($request);
        
        if (!$filePath) {
            return null;
        }
        
        $sheet = $this->loadExcelSheet($filePath);
        if (!$sheet) {
            return null;
        }
        
        $endRow = $sheet->getHighestRow();
        $range = 'A2:A' . $endRow;
        $sheetData = $sheet->rangeToArray($range, null, true, false);
        
        // Flatten array and filter empty values
        $forwordingnos = [];
        foreach ($sheetData as $row) {
            if (!empty($row[0])) {
                $forwordingnos[] = trim($row[0]);
            }
        }
        
        if (empty($forwordingnos)) {
            return null;
        }
        
        return booking::whereIn('forwordingno', $forwordingnos)->get();
        
    } catch (\Exception $e) {
        Log::error('getBookingsFromRequest: Error - ' . $e->getMessage());
        return null;
    }
}
    public function client_api_logs()
    {
        return view('admin.booking.wareeapilogs');
    }
public function clientApiLogsAjax()
{
    $query = ApiLog::select(['id','code' ,'displayOrderCode', 'channel', 'notificationMobile', 'status', 'displayOrderDateTime', 'created', 'created_at']);

    return DataTables::of($query)
        ->editColumn('displayOrderDateTime', function ($row) {
            return \Carbon\Carbon::parse($row->displayOrderDateTime)->toDayDateTimeString();
        })
        ->editColumn('created', function ($row) {
            return \Carbon\Carbon::parse($row->created)->toDayDateTimeString();
        })
        ->make(true);
}
private function validateExcelUpload($request)
{
    $request->validate([
        'excel_file' => 'required|mimes:xls,xlsx',
    ]);
}

private function handleFileUpload($request)
{
    $file = $request->file('excel_file');
    $folder = public_path('storage/excel/' . date('Y') . '/' . date('m'));
    
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }
    
    $fileName = time() . '_' . $file->getClientOriginalName();
    $file->move($folder, $fileName);
    $filePath = $folder . '/' . $fileName;

    return is_readable($filePath) ? $filePath : null;
}

private function cleanNumericValue($value)
{
    if (empty($value) || $value === null) {
        return null;
    }
    
    // Convert to string and remove newlines, carriage returns, and extra whitespace
    $cleaned = trim(str_replace(["\n", "\r", "\t"], ' ', (string)$value));
    
    // Extract the first number if there are multiple numbers separated by spaces
    if (preg_match('/^(\d+(?:\.\d+)?)/', $cleaned, $matches)) {
        return $matches[1];
    }
    
    // If no number found, try to extract any number from the string
    if (preg_match('/(\d+(?:\.\d+)?)/', $cleaned, $matches)) {
        return $matches[1];
    }
    
    // Return null if no valid number found
    return null;
}

private function readExcelSheet($sheet, $range, $headerRow = 1)
{
    $startRow = $headerRow + 1;
    $endRow = $sheet->getHighestRow();
    $range = $range . $endRow;
    
    return $sheet->rangeToArray($range, null, true, false);
}

/**
 * Load Excel sheet from file path
 */
private function loadExcelSheet($filePath)
{
    try {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        return $spreadsheet->getActiveSheet();
    } catch (\Exception $e) {
        Log::error('Failed to load spreadsheet - ' . $e->getMessage());
        return null;
    }
}

/**
 * Filter out empty rows from Excel data
 */
private function filterEmptyRows(array $sheetData): array
{
    $filteredData = [];
    foreach ($sheetData as $rowData) {
        if (!$this->isEmptyRow($rowData)) {
            $filteredData[] = $rowData;
        }
    }
    return $filteredData;
}

/**
 * Check if a row is empty
 */
private function isEmptyRow(array $row): bool
{
    foreach ($row as $cellData) {
        if (!empty($cellData)) {
            return false;
        }
    }
    return true;
}

/**
 * Process booking rows from Excel data
 */
private function processBookingRows(array $filteredData): array
{
    DB::beginTransaction();
    
    $processedCount = 0;
    $skippedCount = 0;
    
    foreach ($filteredData as $rowIndex => $row) {
        $rowNumber = $rowIndex + self::BOOKING_START_ROW + 1;
        
        // Validate row structure
        $validation = $this->validateBookingRow($row, $rowNumber);
        if (!$validation['valid']) {
            DB::rollBack();
            return ['message' => $validation['error'], 'processed' => 0, 'skipped' => 0];
        }
        
        $forwordingno = $row[self::BOOKING_COLUMNS['forwordingno']];
        $existingRecord = booking::where('forwordingno', $forwordingno)->first();
        
        if ($existingRecord) {
            $skippedCount++;
            Log::info("BulkBooking: Skipping row {$rowNumber} - Forwarding No {$forwordingno} already exists");
            continue;
        }
        
        try {
            $booking = $this->createBookingFromRow($row, $rowNumber);
            $this->createBookingLog($booking, $row, $rowNumber);
            $processedCount++;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("BulkBooking: Failed to process row {$rowNumber} - " . $e->getMessage());
            return ['message' => "Row {$rowNumber}: " . $e->getMessage(), 'processed' => 0, 'skipped' => 0];
        }
    }
    
    DB::commit();
    
    $message = $this->buildProcessingMessage($processedCount, $skippedCount);
    Log::info("BulkBooking: Completed - Processed: {$processedCount}, Skipped: {$skippedCount}");
    
    return ['message' => $message, 'processed' => $processedCount, 'skipped' => $skippedCount];
}

/**
 * Validate booking row structure and data
 */
private function validateBookingRow(array $row, int $rowNumber): array
{
    if (count($row) < self::BOOKING_MIN_COLUMNS) {
        return [
            'valid' => false,
            'error' => "Row {$rowNumber}: Insufficient data columns. Expected at least " . self::BOOKING_MIN_COLUMNS . " columns, got " . count($row) . "."
        ];
    }
    
    // Check for required fields (non-null)
    $requiredFields = ['forwordingno', 'cust_name', 'pickuplocation', 'deliverylocation'];
    foreach ($requiredFields as $field) {
        $colIndex = self::BOOKING_COLUMNS[$field];
        if (empty($row[$colIndex])) {
            return [
                'valid' => false,
                'error' => "Row {$rowNumber}: Required field '{$field}' is missing or empty."
            ];
        }
    }
    
    // Validate booking date is not more than 3 days old
    $bookingDateValue = $row[self::BOOKING_COLUMNS['booking_date']] ?? null;
    if (!empty($bookingDateValue)) {
        try {
            $bookingDate = $this->parseBookingDateForValidation($bookingDateValue);
            $threeDaysAgo = Carbon::now()->subDays(3)->startOfDay();
            
            // Check if booking date is older than 3 days (not including exactly 3 days ago)
            if ($bookingDate->lt($threeDaysAgo)) {
                $formattedDate = $bookingDate->format('d/m/Y H:i:s');
                $today = Carbon::now()->format('d/m/Y');
                return [
                    'valid' => false,
                    'error' => "Row {$rowNumber}: Booking date ({$formattedDate}) is more than 3 days old. Only bookings from the last 3 days (from {$today}) are allowed."
                ];
            }
        } catch (\Exception $e) {
            // If date parsing fails, let it fail later in createBookingFromRow
            // We'll just skip this validation if the date format is invalid
        }
    }
    
    return ['valid' => true];
}

/**
 * Create booking from Excel row data
 */
private function createBookingFromRow(array $row, int $rowNumber): booking
{
    $booking = new booking;
    
    // Map all fields using column constants
    $booking->modeoftrans = $row[self::BOOKING_COLUMNS['modeoftrans']] ?? null;
    $booking->forwordingno = $row[self::BOOKING_COLUMNS['forwordingno']];
    $booking->cust_name = $row[self::BOOKING_COLUMNS['cust_name']];
    $booking->pickuplocation = $row[self::BOOKING_COLUMNS['pickuplocation']];
    $booking->deliverylocation = $row[self::BOOKING_COLUMNS['deliverylocation']];
    $booking->product_type = $row[self::BOOKING_COLUMNS['product_type']] ?? null;
    $booking->weight = $this->cleanNumericValue($row[self::BOOKING_COLUMNS['weight']] ?? null);
    $booking->vol_weight = $this->cleanNumericValue($row[self::BOOKING_COLUMNS['vol_weight']] ?? null);
    $booking->charg_weight = $this->cleanNumericValue($row[self::BOOKING_COLUMNS['charg_weight']] ?? null);
    $booking->client_name = $row[self::BOOKING_COLUMNS['client_name']] ?? null;
    $booking->pickupaddress = $row[self::BOOKING_COLUMNS['pickupaddress']] ?? null;
    $booking->pickup_pincode = $row[self::BOOKING_COLUMNS['pickup_pincode']] ?? null;
    $booking->sendercontactno = $row[self::BOOKING_COLUMNS['sendercontactno']] ?? null;
    $booking->con_client_name = $row[self::BOOKING_COLUMNS['con_client_name']] ?? null;
    $booking->receiveraddress = $row[self::BOOKING_COLUMNS['receiveraddress']] ?? null;
    $booking->receiver_pincode = $row[self::BOOKING_COLUMNS['receiver_pincode']] ?? null;
    $booking->receivercontactno = $row[self::BOOKING_COLUMNS['receivercontactno']] ?? null;
    $booking->rto_office_name = $row[self::BOOKING_COLUMNS['rto_office_name']] ?? null;
    $booking->rto_address = $row[self::BOOKING_COLUMNS['rto_address']] ?? null;
    $booking->rto_pincode = $row[self::BOOKING_COLUMNS['rto_pincode']] ?? null;
    $booking->refrenceno = $row[self::BOOKING_COLUMNS['refrenceno']] ?? null;
    $booking->content = $row[self::BOOKING_COLUMNS['content']] ?? null;
    $booking->pices = $this->cleanNumericValue($row[self::BOOKING_COLUMNS['pices']] ?? null);
    $booking->value = $this->cleanNumericValue($row[self::BOOKING_COLUMNS['value']] ?? null);
    $booking->invoice_no = $row[self::BOOKING_COLUMNS['invoice_no']] ?? null;
    $booking->waybills = $row[self::BOOKING_COLUMNS['waybills']] ?? null;
    $booking->dims = $row[self::BOOKING_COLUMNS['dims']] ?? null;
    $booking->service_type = $row[self::BOOKING_COLUMNS['service_type']] ?? null;
    $booking->delivery_type = $row[self::BOOKING_COLUMNS['delivery_type']] ?? null;
    $booking->claimid = $row[self::BOOKING_COLUMNS['claimid']] ?? null;
    $booking->status = 'Booked';
    
    // Parse and set booking date
    $bookingDateValue = $row[self::BOOKING_COLUMNS['booking_date']] ?? null;
    if (empty($bookingDateValue)) {
        throw new \Exception('Booking date is required');
    }
    $booking->booking_date = $this->parseDateTime($bookingDateValue, 'd/m/Y H:i:s', $rowNumber, 'booking date');
    
    // Set city and state from pincode before saving
    $this->setLocationFromPincode($booking, $row);
    
    $booking->save();
    
    return $booking;
}

/**
 * Set pickup and receiver city/state from pincode
 */
private function setLocationFromPincode(booking $booking, array $row): void
{
    // Set pickup city
    if (!empty($booking->pickup_pincode)) {
        $pickupPincodeData = Pincode::where('pincode', $booking->pickup_pincode)->first();
        $booking->pickupcity = $pickupPincodeData ? $pickupPincodeData->district : null;
    }
    
    // Set receiver city and state
    if (!empty($booking->receiver_pincode)) {
        $receiverPincodeData = Pincode::where('pincode', $booking->receiver_pincode)->first();
        if ($receiverPincodeData) {
            $booking->receivercity = $receiverPincodeData->district;
            $booking->receiverstate = $receiverPincodeData->state;
        } else {
            $booking->receivercity = null;
            $booking->receiverstate = null;
        }
    }
}

/**
 * Create booking log entry
 */
private function createBookingLog(booking $booking, array $row, int $rowNumber): void
{
    $bookingLog = new bookinglog;
    $bookingLog->bookingno = $booking->id;
    $bookingLog->currentstatus = $row[self::BOOKING_COLUMNS['pickuplocation']];
    $bookingLog->status = 'Booked';
    $bookingLog->remark = 'Booked';
    $bookingLog->deliverydate = $booking->booking_date;
    $bookingLog->createdbyy = Auth::id();
    
    // Parse expected delivery date
    $expectedDateValue = $row[self::BOOKING_COLUMNS['expected_delivery_date']] ?? null;
    if (!empty($expectedDateValue)) {
        $bookingLog->expecteddeliverydate = $this->parseDate($expectedDateValue, 'd/m/Y', $rowNumber, 'expected delivery date');
    }
    
    $bookingLog->save();
}

/**
 * Parse booking date for validation (returns Carbon instance)
 * Handles both Excel serial dates and string date formats
 */
private function parseBookingDateForValidation($value): Carbon
{
    if (empty($value)) {
        throw new \Exception('Booking date is empty');
    }
    
    // Check if it's an Excel serial date (numeric)
    if (is_numeric($value)) {
        try {
            $excelStartDate = Carbon::create(1899, 12, 30);
            return $excelStartDate->addDays(floor($value))
                ->addSeconds(($value - floor($value)) * 86400);
        } catch (\Exception $e) {
            throw new \Exception("Invalid Excel serial date format. Found: {$value}");
        }
    }
    
    // Try to parse as string date
    $formats = [
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'd-m-Y H:i:s',
        'd/m/Y',
        'Y-m-d',
        'd-m-Y',
        'm/d/Y H:i:s',
        'm/d/Y',
    ];
    
    foreach ($formats as $format) {
        try {
            return Carbon::createFromFormat($format, $value);
        } catch (\Exception $e) {
            continue;
        }
    }
    
    throw new \Exception("Invalid booking date format. Found: {$value}");
}

/**
 * Parse date-time string with multiple format support
 * Handles both Excel serial dates and string date formats
 */
private function parseDateTime($value, string $format, int $rowNumber, string $fieldName): string
{
    if (empty($value)) {
        throw new \Exception("{$fieldName} is empty");
    }
    
    // Check if it's an Excel serial date (numeric)
    if (is_numeric($value)) {
        try {
            $excelStartDate = Carbon::create(1899, 12, 30);
            return $excelStartDate->addDays(floor($value))
                ->addSeconds(($value - floor($value)) * 86400)
                ->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            throw new \Exception("Invalid Excel serial date format for {$fieldName}. Found: {$value}");
        }
    }
    
    // Try the primary format
    try {
        return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        // Try alternative formats
        $alternativeFormats = [
            'd/m/Y H:i',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd-m-Y H:i:s',
            'd/m/Y',
            'Y-m-d',
            'm/d/Y H:i:s',
            'm/d/Y',
        ];
        
        foreach ($alternativeFormats as $altFormat) {
            try {
                return Carbon::createFromFormat($altFormat, $value)->format('Y-m-d H:i:s');
            } catch (\Exception $e2) {
                continue;
            }
        }
        
        throw new \Exception("Invalid {$fieldName} format. Expected: {$format}. Found: {$value}");
    }
}

/**
 * Parse date string with multiple format support
 * Handles both Excel serial dates and string date formats
 */
private function parseDate($value, string $format, int $rowNumber, string $fieldName): Carbon
{
    if (empty($value)) {
        throw new \Exception("{$fieldName} is empty");
    }
    
    // Check if it's an Excel serial date (numeric)
    if (is_numeric($value)) {
        try {
            $excelStartDate = Carbon::create(1899, 12, 30);
            return $excelStartDate->addDays(floor($value))
                ->addSeconds(($value - floor($value)) * 86400);
        } catch (\Exception $e) {
            throw new \Exception("Invalid Excel serial date format for {$fieldName}. Found: {$value}");
        }
    }
    
    // Try the primary format
    try {
        return Carbon::createFromFormat($format, $value);
    } catch (\Exception $e) {
        // Try alternative formats
        $alternativeFormats = [
            'd/m/Y',
            'Y-m-d',
            'd-m-Y',
            'm/d/Y',
        ];
        
        foreach ($alternativeFormats as $altFormat) {
            try {
                return Carbon::createFromFormat($altFormat, $value);
            } catch (\Exception $e2) {
                continue;
            }
        }
        
        throw new \Exception("Invalid {$fieldName} format. Expected: {$format}. Found: {$value}");
    }
}

/**
 * Build processing completion message
 */
private function buildProcessingMessage(int $processedCount, int $skippedCount): string
{
    if ($processedCount == 0 && $skippedCount == 0) {
        return 'No bookings were processed. Please check your data.';
    }
    
    $message = 'Booking processing completed. ';
    if ($processedCount > 0) {
        $message .= $processedCount . ' booking(s) created. ';
    }
    if ($skippedCount > 0) {
        $message .= $skippedCount . ' booking(s) skipped (already exist).';
    }
    
    return trim($message);
}

/**
 * Rollback database transaction if active
 */
private function rollbackTransaction(): void
{
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
}
}

