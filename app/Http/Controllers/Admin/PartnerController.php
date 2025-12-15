<?php 
namespace App\Http\Controllers\admin; 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Webhook;
use Auth;
use Session;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $datas = Webhook::orderBy('id', 'DESC')->get();
        return view('admin.partner.index', compact('datas'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Webhook::findOrFail($id);
        return view('admin.partner.edit', compact('data'));
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
            'service_provider' => 'nullable|string|max:255'
        ]);
     
        $partner = Webhook::findOrFail($id);
        $partner->service_provider = $request->input('service_provider');
        $partner->save(); 
        
        return redirect()->route('partner.index')->with('alert-success', 'Service provider updated successfully');
    }
}

