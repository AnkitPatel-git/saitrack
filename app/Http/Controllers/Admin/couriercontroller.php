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
use App\Http\Controllers\admin\DateTime;
class couriercontroller extends Controller
{
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
    
// public function index()
// {
//  $datas = booking::select(['id', 'booking_date', 'created_at', 'forwordingno', 'cust_name', 'pickuplocation', 'deliverylocation', 'status',]);

//     return Datatables::of($datas)
//         ->addColumn('booking_date', function ($data) {
//             // Format the booking_date column to include both date and time
//             return $data->booking_date->format('Y-m-d H:i:s'); // Adjust the format as needed
//         })
//         ->addColumn('action', function ($data) {
//             return view('admin.booking.actions', compact('data'))->render();
//         })
//         ->make(true);
// }

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
      
        $datas = booking::where('forwordingno',$request->forwordingno)->exists();
        if($datas == "True"){
            session()->flash('alert-warning', 'forwording no Already Exist');
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
    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //     'currentstatus' => 'required',
    //     'status' => 'required',
    //     ]);
        
    //     $data = booking::where('id',$id)->exists();
     
    //     if($data ==false){
    //         session()->flash('alert-warning', 'Booking Not Found');
    //         return back();
    //         }
    //     booking::where('id',$id)->update(['status' => $request->status]);
    //     $newentry = new bookinglog;
    //     $newentry->bookingno = $id;
    //     $newentry->currentstatus = $request->currentstatus;
    //     $newentry->createdbyy = Auth::id();
    //     $newentry->status = $request->status;
    //     $newentry->remark = $request->remark;
    //     $newentry->deliverydate = $request->deliverydate;
    //      $newentry->expecteddeliverydate = $request->expecteddeliverydate;
    //     $newentry->save();
    //     session()->flash('alert-success', 'Booking Updated Successfully');
    //     return redirect('/Admin/booking');
    // }
    
    
    
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
    // public function updateall($id)
    // {
        
    //      $request->validate([
    //     'cust_name' => 'required',
    //     'forwordingno' => 'required',
    //     ]);
        
    // }
    
    public function updateall(Request $request, $id)
    { 
        // dd('new');
    $booking = booking::findOrFail($id);
 
    $existingBooking = booking::where('forwordingno', $request->forwordingno)->where('id', '!=', $id)->exists();
    if ($existingBooking) {
        session()->flash('alert-warning', 'Forwarding number already exists');
        return redirect()->back();
    }  
    $booking->update([
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
    ]);

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
    
    
    
    
//     public function BulkPincode(Request $request)
// {
   
//     $this->validateExcelUpload($request);
     
//     $filePath = $this->handleFileUpload($request);
    
//     if (!$filePath) {
//         return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
//     }

//     $spreadsheet = IOFactory::load($filePath);
   
//     $sheet = $spreadsheet->getActiveSheet();
//     $sheetData = $this->readExcelSheet($sheet, 'A2:M');
    
//     $insertData = [];
//     foreach ($sheetData as $row) {
//         $existingRecord = DB::table('Pincode')->where('pincode', $row[0])->first();

//         if (!$existingRecord) {
//             $insertData[] = [
//                 'pincode' => $row[0],
//                 'area' => $row[1],
//                 'district' => $row[2],
//                 'state' => $row[3],
//                 'air-servie' => $row[4],
//                 'edlkmair' => $row[5],
//                 'embargo' => $row[6],
//                 'tat-air' => $row[7],
//                 'surface-service' => $row[8],
//                 'edlkmsurface' => $row[9],
//                 'tat-surface' => $row[10],
//                 'dp-service' => $row[11],
//                 'tatdp' => $row[12],
//             ];
//         }
//     }
//     if (!empty($insertData)) {
//     DB::table('Pincode')->insert($insertData);
//     }

//     return redirect('/Admin/Pincode')->with('alert-success', 'Pincode data added successfully');
// }

public function BulkPincode(Request $request)
{
    $this->validateExcelUpload($request);

    $filePath = $this->handleFileUpload($request);

    if (!$filePath) {
        return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
    }
    Excel::import(new PincodeImport, $filePath);
    
    // $array = Excel::toArray([], $filePath);

    // $this->processArray($array);
    // Excel::filter('chunk')->import(new PincodeImport, $filePath, null, true, null, 'Xlsx', function ($results) {
    //     $this->processChunk($results);
    // });
    
    return redirect('/Admin/Pincode')->with('alert-success', 'Pincode data added successfully');
}

private function processChunk($results)
{
    $insertData = [];

    foreach ($results->toArray() as $row) {
        $existingRecord = DB::table('Pincode')->where('pincode', $row[0])->first();

        if (!$existingRecord) {
            $insertData[] = [
                'pincode' => $row[0],
                'area' => $row[1],
                'district' => $row[2],
                'state' => $row[3],
                'air-servie' => $row[4],
                'edlkmair' => $row[5],
                'embargo' => $row[6],
                'tat-air' => $row[7],
                'surface-service' => $row[8],
                'edlkmsurface' => $row[9],
                'tat-surface' => $row[10],
                'dp-service' => $row[11],
                'tatdp' => $row[12],
            ];
        }
    }

    if (!empty($insertData)) {
        DB::table('Pincode')->insert($insertData);
    }
}

// public function BulkBooking(Request $request)
// {
//     $this->validateExcelUpload($request);
//     $filePath = $this->handleFileUpload($request);

//     if (!$filePath) {
//         return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
//     }
    
//     $spreadsheet = IOFactory::load($filePath);
//     $sheet = $spreadsheet->getActiveSheet();
//     $sheetData = $this->readExcelSheet($sheet, 'A3:Z');
//     // dd($sheetData);
    
//     $filteredData = [];
// foreach ($sheetData as $rowData) {
//     $isEmptyRow = true;
//     foreach ($rowData as $cellData) {
//         if (!empty($cellData)) {
//             $isEmptyRow = false;
//             break;
//         }
//     }
//     if (!$isEmptyRow) {
//         $filteredData[] = $rowData;
//     }
// }

// // dd($filteredData);

//     DB::beginTransaction();

//     try {
//         foreach ($filteredData as $row) {
//             if (in_array(null, $row, true)) {
//                 DB::rollBack();
//                 return redirect()->back()->with('alert-error', 'Data Missing');
//             }

//             $existingRecord = Booking::where('forwordingno', $row[2])->first();
       
//             if (!$existingRecord) {
//                 $newscanpoint = new booking;
//                 $newscanpoint->cust_name = $row[3];
//                 $newscanpoint->modeoftrans = $row[1];
//                 $newscanpoint->forwordingno = $row[2];
//                 $newscanpoint->pickuplocation = $row[4];
//                 $newscanpoint->deliverylocation = $row[5];
//                 $newscanpoint->product_type = $row[6];
//                 $newscanpoint->weight = $row[8];
//                 $newscanpoint->vol_weight = $row[9];
//                 $newscanpoint->charg_weight = $row[10];
//                 $newscanpoint->client_name = $row[11];
//                 $newscanpoint->pickupaddress = $row[12];
//                 // $newscanpoint->pickup_state = $row[];
//                 // $newscanpoint->pickupcity = $row[0];
//                 $newscanpoint->pickup_pincode = $row[13];
//                 $newscanpoint->sendercontactno = $row[14];
//                 $newscanpoint->con_client_name = $row[15];
//                 $newscanpoint->receiveraddress = $row[16];
//                 // $newscanpoint->receiverstate = $row[];
//                 // $newscanpoint->receivercity = $row[0];
//                 $newscanpoint->receiver_pincode = $row[17];
//                 $newscanpoint->receivercontactno = $row[18];
//                 $newscanpoint->refrenceno = $row[23];
//                 $newscanpoint->content = $row[24];
//                 $newscanpoint->status = 'Shipped';
//                 // $newscanpoint->booking_date = date("Y-m-d H:i:s", ($row[22] - 25569) * 86400);
//                 $dateTimestamp = ($row[22] - 25569) * 86400;
//                 // $timeSeconds = $row[23] * 86400;
//                 $timeSeconds = ($row[23] - floor($row[23])) * 86400;
//                 $dateTimeTimestamp = $dateTimestamp + $timeSeconds;
//                 $newscanpoint->booking_date = date("Y-m-d H:i:s", $dateTimeTimestamp);
                
//                 dd($newscanpoint);
//                 // $newscanpoint->save();
              
//                 $newentry = new bookinglog;
//                 $newentry->bookingno = $newscanpoint->id;
//                 $newentry->currentstatus = $row[4];
//                 $newentry->status = 'Shipped';
//                 $newentry->remark = 'Shipped';
//                 $newentry->deliverydate = date("Y-m-d H:i:s", (($row[22] - 25569) * 86400));
               
                
//                 $newentry->createdbyy = Auth::id();
//                 // $newentry->save();
//             }
//         }

//         DB::commit();
//         return redirect('/Admin/booking')->with('alert-success', 'Booking Created Successfully');
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return redirect()->back()->with('alert-error', 'An error occurred while processing the data.');
//     }
// }

public function BulkBooking(Request $request)
{ 
    try {
        $this->validateExcelUpload($request);
         
        $filePath = $this->handleFileUpload($request);
     
        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }

        // Load spreadsheet with error handling
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
        } catch (\Exception $e) {
            Log::error('BulkBooking: Failed to load spreadsheet - ' . $e->getMessage());
            return redirect()->back()->with('alert-error', 'Failed to load Excel file: ' . $e->getMessage());
        }

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheetData = $this->readExcelSheet($sheet, 'A4:AH');
        } catch (\Exception $e) {
            Log::error('BulkBooking: Failed to read sheet data - ' . $e->getMessage());
            return redirect()->back()->with('alert-error', 'Failed to read Excel sheet: ' . $e->getMessage());
        }
        
        $filteredData = [];
        foreach ($sheetData as $rowData) {
            $isEmptyRow = true;
            foreach ($rowData as $cellData) {
                if (!empty($cellData)) {
                    $isEmptyRow = false;
                    break;
                }
            }
            if (!$isEmptyRow) {
                $filteredData[] = $rowData;
            }
        }
        
        if (empty($filteredData)) {
            Log::warning('BulkBooking: No data found in Excel file after filtering');
            return redirect()->back()->with('alert-error', 'No valid data found in the Excel file. Please ensure the file contains data starting from row 4.');
        }
        
        Log::info('BulkBooking: Processing ' . count($filteredData) . ' rows');
        
        DB::beginTransaction();

        $processedCount = 0;
        $skippedCount = 0; 
        
        foreach ($filteredData as $rowIndex => $row) {
            $rowNumber = $rowIndex + 5; // +5 because data starts at row 4, and rowIndex is 0-based
            
            // Check if row has enough columns
            if (!isset($row[33]) || count($row) < 34) {
                DB::rollBack();
                Log::error('BulkBooking: Insufficient columns in row ' . $rowNumber . '. Expected at least 34 columns, got ' . count($row));
                return redirect()->back()->with('alert-error', 'Row ' . $rowNumber . ': Insufficient data columns. Expected at least 34 columns.');
            }
             
            if (in_array(null, $row, true)) {
                DB::rollBack();
                Log::error('BulkBooking: Missing data in row ' . $rowNumber);
                return redirect()->back()->with('alert-error', 'Row ' . $rowNumber . ': Data Missing. Please ensure all required fields are filled.');
            } 
            
            $existingRecord = Booking::where('forwordingno', $row[2])->first();
       
            if (!$existingRecord) {
                $processedCount++;
                
                $newscanpoint = new Booking;
                $newscanpoint->cust_name = $row[3];
                $newscanpoint->modeoftrans = $row[1];
                $newscanpoint->forwordingno = $row[2];
                $newscanpoint->pickuplocation = $row[4];
                $newscanpoint->deliverylocation = $row[5];
                $newscanpoint->product_type = $row[6];
                $newscanpoint->weight = $this->cleanNumericValue($row[8]);
                $newscanpoint->vol_weight = $this->cleanNumericValue($row[9]);
                $newscanpoint->charg_weight = $this->cleanNumericValue($row[10]);
                $newscanpoint->client_name = $row[11];
                $newscanpoint->pickupaddress = $row[12];
                $newscanpoint->pickup_pincode = $row[13];
                $newscanpoint->sendercontactno = $row[14];
                $newscanpoint->con_client_name = $row[15];
                $newscanpoint->receiveraddress = $row[16];
                $newscanpoint->receiver_pincode = $row[17];
                $newscanpoint->receivercontactno = $row[18];
                $newscanpoint->rto_office_name = $row[19];
                $newscanpoint->rto_address = $row[20];
                $newscanpoint->rto_pincode = $row[21];
                $newscanpoint->refrenceno = $row[24];
                $newscanpoint->content = $row[25];
                $newscanpoint->pices = $this->cleanNumericValue($row[26]);
                // Clean value field - remove newlines and extract first numeric value
                $newscanpoint->value = $this->cleanNumericValue($row[27]);
                $newscanpoint->invoice_no = $row[28];
                $newscanpoint->waybills = $row[29];
                $newscanpoint->dims = $row[30];
                $newscanpoint->service_type = $row[31];
                $newscanpoint->delivery_type = $row[32];
                $newscanpoint->claimid = $row[33];
                $newscanpoint->status = 'Booked';
                
                // Parse booking date with error handling
                try {
                    if (empty($row[22])) {
                        throw new \Exception('Booking date is empty');
                    }
                    $bookingDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $row[22])->format('Y-m-d H:i:s');
                } catch (\Exception $dateError) {
                    DB::rollBack();
                    Log::error('BulkBooking: Invalid booking date format in row ' . $rowNumber . '. Value: ' . ($row[22] ?? 'empty') . '. Error: ' . $dateError->getMessage());
                    return redirect()->back()->with('alert-error', 'Row ' . $rowNumber . ': Invalid booking date format. Expected format: dd/mm/yyyy HH:mm:ss. Found: ' . ($row[22] ?? 'empty'));
                }
                
                $newscanpoint->booking_date = $bookingDateTime;
                $newscanpoint->pickupcity = Pincode::where('pincode',$row[13])->value('district');
                $recPincodeData = Pincode::where('pincode', $row[17])->first();
                
                if ($recPincodeData) {
                    $newscanpoint->receivercity = $recPincodeData->district;
                    $newscanpoint->receiverstate = $recPincodeData->state;
                } else {
                    $newscanpoint->receivercity = '';
                    $newscanpoint->receiverstate = '';
                }
                
                try {
                    $newscanpoint->save();
                } catch (\Exception $saveError) {
                    DB::rollBack();
                    $errorMessage = $saveError->getMessage();
                    
                    // Provide more helpful message for data truncation errors
                    if (strpos($errorMessage, 'Data truncated') !== false) {
                        $fieldName = 'unknown field';
                        if (strpos($errorMessage, "column 'value'") !== false) {
                            $fieldName = 'value';
                            $errorMessage = 'Value field contains invalid data (possibly multiple lines). Original value: ' . ($row[27] ?? 'empty');
                        } elseif (strpos($errorMessage, "column 'weight'") !== false) {
                            $fieldName = 'weight';
                            $errorMessage = 'Weight field contains invalid data. Original value: ' . ($row[8] ?? 'empty');
                        } elseif (preg_match("/column '([^']+)'/", $errorMessage, $matches)) {
                            $fieldName = $matches[1];
                            $errorMessage = "Field '{$fieldName}' contains invalid data that cannot be stored.";
                        }
                    }
                    
                    Log::error('BulkBooking: Failed to save booking in row ' . $rowNumber . '. Forwarding No: ' . ($row[2] ?? 'N/A') . '. Error: ' . $saveError->getMessage() . ' | Full trace: ' . $saveError->getTraceAsString());
                    return redirect()->back()->with('alert-error', 'Row ' . $rowNumber . ': Failed to save booking. ' . $errorMessage);
                }
               
                $newentry = new bookinglog;
                $newentry->bookingno = $newscanpoint->id;
                $newentry->currentstatus = $row[4];
                $newentry->status = 'Booked';
                $newentry->remark = 'Booked';
                $newentry->deliverydate = $bookingDateTime;
                $newentry->createdbyy = Auth::id();
                
                // Parse expected delivery date with error handling
                try {
                    if (empty($row[23])) {
                        throw new \Exception('Expected delivery date is empty');
                    }
                    $newentry->expecteddeliverydate = Carbon::createFromFormat('d/m/Y', $row[23]);
                } catch (\Exception $dateError) {
                    DB::rollBack();
                    Log::error('BulkBooking: Invalid expected delivery date format in row ' . $rowNumber . '. Value: ' . ($row[23] ?? 'empty') . '. Error: ' . $dateError->getMessage());
                    return redirect()->back()->with('alert-error', 'Row ' . $rowNumber . ': Invalid expected delivery date format. Expected format: dd/mm/yyyy. Found: ' . ($row[23] ?? 'empty'));
                }
                
                try {
                    $newentry->save();
                } catch (\Exception $saveError) {
                    DB::rollBack();
                    Log::error('BulkBooking: Failed to save booking log in row ' . $rowNumber . '. Error: ' . $saveError->getMessage());
                    return redirect()->back()->with('alert-error', 'Row ' . $rowNumber . ': Failed to save booking log. Error: ' . $saveError->getMessage());
                }
            } else {
                $skippedCount++;
                Log::info('BulkBooking: Skipping row ' . $rowNumber . ' - Forwarding No ' . ($row[2] ?? 'N/A') . ' already exists');
            }
        }
 
        DB::commit();
        
        $message = 'Booking processing completed. ';
        if ($processedCount > 0) {
            $message .= $processedCount . ' booking(s) created. ';
        }
        if ($skippedCount > 0) {
            $message .= $skippedCount . ' booking(s) skipped (already exist).';
        }
        if ($processedCount == 0 && $skippedCount == 0) {
            $message = 'No bookings were processed. Please check your data.';
        }
        
        Log::info('BulkBooking: Completed - Processed: ' . $processedCount . ', Skipped: ' . $skippedCount);
        return redirect('/Admin/booking')->with('alert-success', $message);
        
    } catch (\Carbon\Exceptions\InvalidFormatException $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Log::error('BulkBooking: Date format error - ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
        return redirect()->back()->with('alert-error', 'Date format error: ' . $e->getMessage() . '. Please check your date formats (dd/mm/yyyy for dates, dd/mm/yyyy HH:mm:ss for date-time).');
    } catch (\Illuminate\Database\QueryException $e) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Log::error('BulkBooking: Database error - ' . $e->getMessage() . ' | SQL: ' . ($e->getSql() ?? 'N/A'));
        return redirect()->back()->with('alert-error', 'Database error: ' . $e->getMessage() . '. Please check your data and try again.');
    } catch (\Exception $e) { 
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        Log::error('BulkBooking: Unexpected error - ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine() . ' | Trace: ' . $e->getTraceAsString());
        return redirect()->back()->with('alert-error', 'Error occurred: ' . $e->getMessage() . '. Please check the logs for more details.');
    }
}


// public function Bulkupdate(Request $request)
// {
//     $this->validateExcelUpload($request);

//     $filePath = $this->handleFileUpload($request);

//     if (!$filePath) {
//         return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
//     }

//     $spreadsheet = IOFactory::load($filePath);
//     $sheet = $spreadsheet->getActiveSheet();
//     $sheetData = $this->readExcelSheet($sheet, 'A2:E');


// $filteredData = [];
//     foreach ($sheetData as $rowData) {
//         $isEmptyRow = true;
//         foreach ($rowData as $cellData) {
//             if (!empty($cellData)) {
//                 $isEmptyRow = false;
//                 break;
//             }
//         }
//         if (!$isEmptyRow) {
//             $filteredData[] = $rowData;
//         }
//     }
  
//     DB::beginTransaction();

//     try {
//         foreach ($filteredData as $row) {
//             if (in_array(null, $row, true)) {
//                 DB::rollBack();
//                 return redirect()->back()->with('alert-error', 'Data Missing');
//             }

//             $existingRecord = Booking::where('forwordingno', $row[0])
//             ->where('status', '!=', 'Delivered')
//             ->first();
              
//             if ($existingRecord) {
//                 Booking::where('forwordingno', $row[0])->update(['status' => $row[3]]);
                 
//                 $newentry = new bookinglog;
//                 $newentry->bookingno = $existingRecord->id;
//                 $newentry->currentstatus = $row[1];
//                 $newentry->createdbyy = Auth::id();
//                 $newentry->status = $row[3];
//                 $newentry->remark = $row[2];
//                 $newentry->deliverydate =Carbon::createFromFormat('d/m/Y H:i:s', $row[4])->format('Y-m-d H:i:s');
//                 $newentry->save();
//             }
//         }

//         DB::commit();
//         return redirect('/Admin/booking')->with('alert-success', 'Booking Updated Successfully');
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return redirect()->back()->with('alert-error', 'An error occurred while processing the data.');
//     }
// }


public function Bulkupdate(Request $request)
{
    $this->validateExcelUpload($request);

    $filePath = $this->handleFileUpload($request);

    if (!$filePath) {
        return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
    }

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $this->readExcelSheet($sheet, 'A2:F');
    } catch (\Exception $e) {
        Log::error('Error loading spreadsheet: ' . $e->getMessage());
        return redirect()->back()->with('alert-error', 'Error loading spreadsheet.');
    }

    $filteredData = [];
    foreach ($sheetData as $rowData) {
        $isEmptyRow = true;
        foreach ($rowData as $cellData) {
            if (!empty($cellData)) {
                $isEmptyRow = false;
                break;
            }
        }
        if (!$isEmptyRow) {
            $filteredData[] = $rowData;
        }
    }
    

    DB::beginTransaction();
    
    try {
        foreach ($filteredData as $row) {
            // Log each row for debugging
            Log::info('Processing row: ' . json_encode($row));
               
            if (in_array(null, $row, true)) {
                DB::rollBack();
                return redirect()->back()->with('alert-error', 'Data Missing');
            }

            $existingRecord = Booking::where('forwordingno', $row[0])
                ->where('status', '!=', 'Delivered')
                ->first();
                 
            if ($existingRecord) {
                Booking::where('id', $existingRecord->id)->update(['status' => $row[3]]);
                
                // $newentry = new BookingLog;
                // $newentry->bookingno = $existingRecord->id;
                // $newentry->currentstatus = $row[1];
                // $newentry->createdby = Auth::id();
                // $newentry->status = $row[3];
                // $newentry->remark = $row[2];
                //  $newentry->deliverydate = null;
                // // Validate and format date
               

                // $newentry->save();
                
                if (is_numeric($row[4])) {
    // Convert Excel serial date-time to PHP DateTime
    $excelStartDate = Carbon::create(1899, 12, 30); // Excel's base date for serials
    $date = $excelStartDate->addDays(floor($row[4]))
                                           ->addSeconds(($row[4] - floor($row[4])) * 86400) // Add time portion
                                           ->format('Y-m-d H:i:s');
} else {
    // Handle as a normal string date
    try {
        $date= Carbon::createFromFormat('d/m/Y H:i:s', $row[4])->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        $date = null; // Set to null if invalid
    }
}
 if (is_numeric($row[5])) {
    // Convert Excel serial date-time to PHP DateTime
    $excelStartDate2 = Carbon::create(1899, 12, 30); // Excel's base date for serials
    $date2 = $excelStartDate2->addDays(floor($row[5]))
                                           ->addSeconds(($row[5] - floor($row[5])) * 86400) // Add time portion
                                           ->format('Y-m-d H:i:s');
} else {
    // Handle as a normal string date
    try {
        $date2= Carbon::createFromFormat('d/m/Y H:i:s', $row[5])->format('Y-m-d H:i:s');
    } catch (\Exception $e) {
        $date2 = null; // Set to null if invalid
    }
}

                $data = [
    'bookingno' => $existingRecord->id,
    'currentstatus' => $row[1],
    'createdbyy' => Auth::id(),
    'status' => $row[3],
    'remark' => $row[2],
    'expecteddeliverydate' => $date2,
    'deliverydate' =>$date,
    'created_at' => now(), // Add timestamps if your table has them
    'updated_at' => now(),
     
];
DB::table('bookinglog')->insert($data);
            }
        }

        DB::commit();
        return redirect('/Admin/booking')->with('alert-success', 'Booking Updated Successfully');
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error during bulk update: ' . $e->getMessage());
        return redirect()->back()->with('alert-error', 'An error occurred while processing the data.');
    }
}

  
 



public function bulkreport(Request $request)
    {

    if ($request->forwordingno != null) {

        $forwordingno = preg_replace('/\s+/', '', $request->forwordingno);
        $split = explode(",", $forwordingno);
        $datas = booking::whereIn('forwordingno', $split)->get();
    } else {
        $this->validateExcelUpload($request);
        $filePath = $this->handleFileUpload($request);
        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $endRow = $sheet->getHighestRow();
        $range = 'A' . '2:' . 'A' . $endRow;
        $sheetData = $sheet->rangeToArray($range, null, true, false);
        // dd($sheetData);
        $datas = booking::whereIn('forwordingno', $sheetData)->get();
    
        
    }

    return view('admin/booking/Bulkinvoice', compact('datas'));
    }
    
    public function bulksticker(Request $request)
    {
      

    if ($request->forwordingno != null) {

        $forwordingno = preg_replace('/\s+/', '', $request->forwordingno);
        $split = explode(",", $forwordingno);
        $datas = booking::whereIn('forwordingno', $split)->get();
    } else {
        $this->validateExcelUpload($request);
        $filePath = $this->handleFileUpload($request);
        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $endRow = $sheet->getHighestRow();
        $range = 'A' . '2:' . 'A' . $endRow;
        $sheetData = $sheet->rangeToArray($range, null, true, false);
        // dd($sheetData);
        $datas = booking::whereIn('forwordingno', $sheetData)->get();
    
        
    }
      
    return view('admin/bulkreport/bulksticker', compact('datas'));
    }
    
    
    public function bulkreportwaree(Request $request)
    {

    if ($request->forwordingno != null) {

        $forwordingno = preg_replace('/\s+/', '', $request->forwordingno);
        $split = explode(",", $forwordingno);
        $datas = booking::whereIn('forwordingno', $split)->get();
    } else {
        $this->validateExcelUpload($request);
        $filePath = $this->handleFileUpload($request);
        if (!$filePath) {
            return redirect()->back()->with('alert-error', 'Corrupt file or data missing');
        }
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        
        $endRow = $sheet->getHighestRow();
        $range = 'A' . '2:' . 'A' . $endRow;
        $sheetData = $sheet->rangeToArray($range, null, true, false);
        // dd($sheetData);
        $datas = booking::whereIn('forwordingno', $sheetData)->get();
    
        
    }

    return view('admin/booking/wareebulkinvoice', compact('datas'));
    }
public function client_api_logs()
{
    // Fetch all API logs (you can add ->orderBy(), ->paginate(), etc. as needed)
    // $datas = ApiLog::orderBy('created_at', 'desc')->get();

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
}
