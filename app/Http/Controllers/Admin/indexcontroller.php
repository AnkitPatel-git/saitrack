<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\booking;
use App\Models\Pincode;
use App\Models\DeliveryLog;
use db;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class indexcontroller extends Controller
{
    public function dashboard()
    {   

        // $data= array();
        $data['Total'] = booking::orderBy('id', 'DESC')->count();
        $data['Shipped'] = booking::where('status', '=', 'Shipped')->count();
        $data['outfordeliverd'] = booking::where('status', '=', 'Out For Deliver')->count();
        $data['deliverd'] = booking::where('status', '=', 'Delivered')->count();
        $data['intransit'] = booking::where('status', '=', 'Intransit')->count();
        $data['rto'] = booking::where('status', '=', 'RTO')->count();
        $data['Totalpincode'] = Pincode::count();
        $data['tat1'] = booking::whereIn('status', ['Intransit', 'Shipped', 'Out For Deliver'])
    ->whereBetween('created_at', [now()->subDays(5), now()->subDays(3)])
    ->count();
     $data['tat2'] = booking::whereIn('status', ['Intransit', 'Shipped', 'Out For Deliver'])
    ->whereBetween('created_at', [now()->subDays(10), now()->subDays(5)])
    ->count();
     $data['tat3'] = booking::whereIn('status', ['Intransit', 'Shipped', 'Out For Deliver'])
    ->whereBetween('created_at', [now()->subDays(500), now()->subDays(10)])
    ->count();
        return view('admin/dashboard',compact('data'));
    }
  public function misreport()
{
    // Calculate the date 50 days ago
    $fiftyDaysAgo = Carbon::now()->subDays(50);

    $bookings = booking::select([
        'waybills as Waybill_No',
        'refrenceno as Forwording_No',
        'pickuplocation as Origin_Area',
        'deliverylocation as Destination_Area',
        'product_type as Product_Code',
        'booking_date as Pu_Date',
        'clientid as Customer_Code',
        'client_name as SHIPPER',
        'con_client_name as CONSIGNEE',
        'pices as PIECES',
        'charg_weight as Bill_Weight',
        'weight as Actual_Weight',
        'pices as Qty',
        'dimension',
    ])
    ->whereDate('booking_date', '>=', $fiftyDaysAgo)
    ->orderBy('id', 'DESC')
    ->get()
    ->map(function ($booking) {
        $length = $booking->dimension['length'] ?? null;
        $breadth = $booking->dimension['breadth'] ?? null;
        $height = $booking->dimension['height'] ?? null;
        $dimWeight = $booking->dimension['dim_weight'] ?? null;

        return [
            'Waybill_No'         => $booking->Waybill_No,
            'Forwording_No'         => $booking->Forwording_No,
            'Origin_Area'        => $booking->Origin_Area,
            'Destination_Area'   => $booking->Destination_Area,
            'Product_Code'       => $booking->Product_Code,
            'Pu_Date'            => $booking->Pu_Date,
            'Customer_Code'      => $booking->Customer_Code,
            'SHIPPER'            => $booking->SHIPPER,
            'CONSIGNEE'          => $booking->CONSIGNEE,
            'PIECES'             => $booking->PIECES,
            'Bill_Weight'        => $booking->Bill_Weight,
            'Actual_Weight'      => $booking->Actual_Weight,
            'Qty'                => $booking->Qty,
            'Length'             => $length,
            'Breadth'            => $breadth,
            'Height'             => $height,
            'Dimensional_Weight' => $dimWeight,
            'Chargeable_Weight'  => $booking->Bill_Weight,
        ];
    });

    return view('admin.reports.misreport', ['bookings' => $bookings]);
}
public function downloadMisReport(Request $request)
{
    // Get bookings with relationships
    $query = booking::with(['bookingLogs' => function($q) {
        $q->orderBy('created_at', 'desc');
    }])
    ->with('items')
    ->orderBy('created_at', 'desc');

    // Apply filters if provided
    if ($request->has('date_from') && $request->date_from) {
        $query->whereDate('booking_date', '>=', $request->date_from);
    }
    if ($request->has('date_to') && $request->date_to) {
        $query->whereDate('booking_date', '<=', $request->date_to);
    }
    if ($request->has('client_name') && $request->client_name) {
        $query->where('client_name', 'like', '%' . $request->client_name . '%');
    }
    if ($request->has('status') && $request->status) {
        $query->where('status', $request->status);
    }

    $bookings = $query->get();

    // Prepare data for export
    $exportData = [];
    $srNo = 1;

    // If no bookings found, return empty Excel file
    if (!$bookings->isEmpty()) {
        // Get delivery logs for forwarding AWB and vendor info
        $deliveryLogs = DeliveryLog::whereIn('booking_id', $bookings->pluck('id'))
            ->get()
            ->groupBy('booking_id');

    foreach ($bookings as $booking) {
        // Get latest booking log
        $latestLog = $booking->bookingLogs->first();
        
        // Get delivery log for this booking
        $deliveryLog = $deliveryLogs->get($booking->id) ? $deliveryLogs->get($booking->id)->first() : null;
        
        // Get pincode data for zone/ODA calculation
        $pickupPincode = Pincode::where('pincode', $booking->pickup_pincode)->first();
        $receiverPincode = Pincode::where('pincode', $booking->receiver_pincode)->first();
        
        // Extract dimensions
        $dimension = $booking->dimension ?? [];
        $length = $dimension['L'] ?? $dimension['length'] ?? '';
        $breadth = $dimension['B'] ?? $dimension['breadth'] ?? '';
        $height = $dimension['H'] ?? $dimension['height'] ?? '';
        
        // Calculate TAT
        $tat = '';
        if ($latestLog && $latestLog->expecteddeliverydate && $booking->booking_date) {
            $expectedDate = \Carbon\Carbon::parse($latestLog->expecteddeliverydate);
            $bookingDate = \Carbon\Carbon::parse($booking->booking_date);
            $tat = $bookingDate->diffInDays($expectedDate) . ' days';
        }
        
        // Determine ODA/NON ODA (you may need to adjust this logic based on your business rules)
        $odaStatus = 'NON ODA';
        if ($receiverPincode && isset($receiverPincode->{'dp-service'})) {
            $odaStatus = $receiverPincode->{'dp-service'} == 'ODA' ? 'ODA' : 'NON ODA';
        }
        
        // Get zone (you may need to calculate this based on your business logic)
        $zone = '';
        if ($pickupPincode && $receiverPincode) {
            // Zone calculation logic - adjust based on your requirements
            $zone = $pickupPincode->state == $receiverPincode->state ? 'Local' : 'Interstate';
        }
        
        // ODA Distance
        $odaDistance = '';
        if ($receiverPincode && isset($receiverPincode->edlkmsurface)) {
            $odaDistance = $receiverPincode->edlkmsurface;
        }

        $exportData[] = [
            'SR.NO' => $srNo++,
            'Pickup date' => $booking->booking_date ? \Carbon\Carbon::parse($booking->booking_date)->format('d/m/Y') : '',
            'Connection Date' => $booking->created_at ? \Carbon\Carbon::parse($booking->created_at)->format('d/m/Y') : '',
            'SB AWB NO' => $booking->forwordingno ?? '',
            'Forwarding AWB NO.' => ($deliveryLog && $deliveryLog->awb_number) ? $deliveryLog->awb_number : ($booking->waybills ?? ''),
            'Vendor' => ($deliveryLog && $deliveryLog->delivery_provider) ? $deliveryLog->delivery_provider : '',
            'Department ( For Ajanta Pharma)' => '', // Add this field to booking model if needed
            'Referance No (PO / Inv / Other Remarks)' => $booking->refrenceno ?? '',
            'Eway Bill Number' => '', // Add this field to booking model if needed
            'CLIENT NAME' => $booking->client_name ?? '',
            'SENDER NAME (Consignor)' => $booking->pickup_name ?? '',
            'PICK LOCATION' => $booking->pickuplocation ?? '',
            'Receiver \ Consignee NAME' => $booking->con_client_name ?? '',
            'DESTINATION' => $booking->deliverylocation ?? '',
            'Pincode' => $booking->receiver_pincode ?? '',
            'ContacT Number' => $booking->receivercontactno ?? '',
            'MODE' => $booking->modeoftrans ?? '',
            'ZONE' => $zone,
            'ODA Distance' => $odaDistance,
            'ODA/NON ODA' => $odaStatus,
            'Material' => $booking->content ?? '',
            'INV AMOUNT' => $booking->value ?? '',
            'Qty' => $booking->pices ?? '',
            'ACTUAL WGT' => $booking->weight ?? '',
            'L' => $length,
            'B' => $breadth,
            'H' => $height,
            'Dimension Weight' => $booking->vol_weight ?? '',
            'Charged Weight' => $booking->charg_weight ?? '',
            'Expected Date of Delivery' => $latestLog && $latestLog->expecteddeliverydate ? \Carbon\Carbon::parse($latestLog->expecteddeliverydate)->format('d/m/Y') : '',
            'Delivery Status' => $booking->status ?? '',
            'Delivery Date' => $latestLog && $latestLog->deliverydate ? \Carbon\Carbon::parse($latestLog->deliverydate)->format('d/m/Y') : '',
            'Receiver Name' => $booking->con_client_name ?? '',
            'TAT' => $tat,
        ];
    }
    }

    // Export to Excel
    return Excel::download(new class($exportData) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithStyles, \Maatwebsite\Excel\Concerns\WithColumnWidths {
        protected $data;

        public function __construct($data)
        {
            $this->data = $data;
        }

        public function array(): array
        {
            return $this->data;
        }

        public function headings(): array
        {
            return [
                'SR.NO',
                'Pickup date',
                'Connection Date',
                'SB AWB NO',
                'Forwarding AWB NO.',
                'Vendor',
                'Department ( For Ajanta Pharma)',
                'Referance No (PO / Inv / Other Remarks)',
                'Eway Bill Number',
                'CLIENT NAME',
                'SENDER NAME (Consignor)',
                'PICK LOCATION',
                'Receiver \ Consignee NAME',
                'DESTINATION',
                'Pincode',
                'ContacT Number',
                'MODE',
                'ZONE',
                'ODA Distance',
                'ODA/NON ODA',
                'Material',
                'INV AMOUNT',
                'Qty',
                'ACTUAL WGT',
                'L',
                'B',
                'H',
                'Dimension Weight',
                'Charged Weight',
                'Expected Date of Delivery',
                'Delivery Status',
                'Delivery Date',
                'Receiver Name',
                'TAT',
            ];
        }

        public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
        {
            return [
                1 => ['font' => ['bold' => true]],
            ];
        }

        public function columnWidths(): array
        {
            return [
                'A' => 8,   // SR.NO
                'B' => 12,  // Pickup date
                'C' => 15,  // Connection Date
                'D' => 15,  // SB AWB NO
                'E' => 18,  // Forwarding AWB NO.
                'F' => 12,  // Vendor
                'G' => 25,  // Department
                'H' => 30,  // Referance No
                'I' => 18,  // Eway Bill Number
                'J' => 20,  // CLIENT NAME
                'K' => 25,  // SENDER NAME
                'L' => 15,  // PICK LOCATION
                'M' => 25,  // Receiver NAME
                'N' => 15,  // DESTINATION
                'O' => 10,  // Pincode
                'P' => 15,  // Contact Number
                'Q' => 10,  // MODE
                'R' => 10,  // ZONE
                'S' => 12,  // ODA Distance
                'T' => 12,  // ODA/NON ODA
                'U' => 15,  // Material
                'V' => 12,  // INV AMOUNT
                'W' => 8,   // Qty
                'X' => 12,  // ACTUAL WGT
                'Y' => 8,   // L
                'Z' => 8,   // B
                'AA' => 8,  // H
                'AB' => 15, // Dimension Weight
                'AC' => 15, // Charged Weight
                'AD' => 20, // Expected Date of Delivery
                'AE' => 15, // Delivery Status
                'AF' => 15, // Delivery Date
                'AG' => 20, // Receiver Name
                'AH' => 10, // TAT
            ];
        }
    }, 'MIS_Report_' . date('Y-m-d') . '.xlsx');
}


}
