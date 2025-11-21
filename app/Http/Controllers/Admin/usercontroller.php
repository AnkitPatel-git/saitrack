<?php 
namespace App\Http\Controllers\admin; 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Auth;
use Session;
use DB;
use Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class usercontroller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $datas = user::orderBy('id', 'DESC')->where('name', '!=', 'admin')->get();
         return view('admin.user.index',compact('datas'));
        //  return view('admin.user.index');
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
           'name' => 'required',  
        ]);
         $data=new user;
        $data->name =  $request->get('name');  
        $data->email =  $request->get('email');
        $data->mobile =  $request->get('mobile');
        $data->role =  1;
        $data->forceFill([
            'password' => Hash::make($request['password']),
        ]);
         //  dd($data);
        $data->save();  
        $data->assignRole("SubAdmin");
        return redirect('Admin/user/');   
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
     public function statusupdate(Request $request ,$id)
    {
         $data = user::find($id)  
    				->select('status')
    				->where('id','=',$id)
    				->first();
    
    	//Check user status
    	if($data->status == '1'){
    		$status = '0';
    	}else{
    		$status = '1';
    	}
       
    	//update product status
    	$values = array('status' => $status );
    	 $data = user::where('id',$id)->update($values);
    	 return redirect('Admin/user/');
    }
    
     public function show($id)
    {
        $datas= user::find($id);  
        // dd($datas);
        return view('admin.user.edit',compact('datas'));
    }
      
    public function update(Request $request, $id)
        {
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email,' . $id, 
                'mobile' => 'required'
            ]);
         
            $user = User::findOrFail($id);
         
            $user->name = $request->input('name');
            $user->email = $request->input('email'); 
             $user->mobile = $request->input('mobile'); 
            
            if ($request->filled('password')) {
                $user->password = Hash::make($request->input('password'));
            } 
            $user->save(); 
            
            return redirect()->route('user.index', $user->id)->with('success', 'User updated successfully');
        }  
      
      public function edit($id)
{
    // Method implementation
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
        
    
}