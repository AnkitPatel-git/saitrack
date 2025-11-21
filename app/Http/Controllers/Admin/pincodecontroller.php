<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pincode;
use Auth;
use Session;
use DB;
use DataTables;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
class pincodecontroller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    $datas = Pincode::select(['id', 'pincode', 'area','district', 'state']);
    return Datatables::of($datas)
        ->addColumn('action', function ($data) {
            return view('admin.pincode.actions', compact('data'))->render();
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
       $request->validate ([
           'pincode' => 'required',  
        ]);
         $data=new Pincode;
        $data->pincode =  $request->get('pincode');  
        $data->city =  $request->get('city');
        $data->state =  $request->get('state');
        //  dd($data);
        $data->save();  
        return redirect('Admin/Pincode');   
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
      public function show($id)
    { 
        $datas= Pincode::find($id); 
        return view('admin/pincode/edit', compact('datas'));  
    }
    
      public function update(Request $request, $id)
    {
         $request->validate ([
            'pincode' => ['required',], 
        ]);
        $data = Pincode::find($id); 
          $data->pincode =  $request->get('pincode');
        $data->city =  $request->get('city');
        $data->state =  $request->get('state');
        $data->save();    
        return redirect('Admin/Pincode');    
       
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
  
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
        public function destroy($id)
        {
            $data=Pincode::find($id);  
            $data->delete();  
            return back();
         }
         
         public function pancard()
        {
            return view('admin/pancard/index');  
         }
        public function pancardcheck(Request $request)
        {
    
        $api_key = '9551K2F0NTBRIQC2L630XYS6HAU8V89DJWGPDZO17B43EMC74A';
        $client_order_id = "1";
        $pan_number = $request->input('Pancard');
    
      
        $response = Http::get('https://api.apiseva.co.in/api/sftp_comn/pan_verify', [
            'api_key' => $api_key,
            'client_order_id' => $client_order_id,
            'pan_number' => $pan_number,
        ]);
         $data = $response->json();
//         $data = [
//   "error_code" => "SPC-200",
//   "status" => "SUCCESS",
//   "message" => "success",
//   "merchant_trans_id" => "SPPANV2312081609511700MSnZ4lV7B2uCT",
//   "detail" =>  [
//     "data" => [
//       "client_id" => "pan_comprehensive_masecqWpDNwxEWJubGyj",
//       "pan_number" => "AWGPP6678K",
//       "full_name" => "SHANTARAM BABOO PATANKAR",
//       "full_name_split" =>  [
//                 "SUNIL",
//                 "KUMAR",
//                 "KOHAR"
//             ],
//       "masked_aadhaar" => "",
//       "address"=> [
//                 "line_1"=> "BAHULA",
//                 "line_2"=> "BAHULA",
//                 "street_name"=> "Bahula S.O",
//                 "zip"=> "713322",
//                 "city"=> "Mukundapur",
//                 "state"=> "WEST BENGAL",
//                 "country"=> "INDIA",
//                 "full"=> "BAHULA BAHULA Bahula S.O 713322 Mukundapur WEST BENGAL INDIA"
//             ],
//       "email" => null,
//       "phone_number" => null,
//       "gender" => "M",
//       "dob" => "1957-06-01",
//       "input_dob" => null,
//       "aadhaar_linked" => false,
//       "dob_verified" => false,
//       "dob_check" => false,
//       "category" => "person",
//       "less_info" => true,
//     ],
//     "status_code" => 200,
//     "success" => true,
//     "message" => null,
//     "message_code" => "success",
//   ],
// ];
        // dd($data);
        return view('admin/pancard/report', compact('data'));
    }
    
}