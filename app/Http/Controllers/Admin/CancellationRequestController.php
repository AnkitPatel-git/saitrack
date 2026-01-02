<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CancellationRequest;
use App\Models\booking;
use Session;

class CancellationRequestController extends Controller
{
    /**
     * Display a listing of cancellation requests.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $cancellationRequests = CancellationRequest::with(['booking', 'webhook'])
            ->orderBy('created_at', 'DESC')
            ->get();
        
        return view('admin.cancellation.index', compact('cancellationRequests'));
    }

    /**
     * Update the status of a cancellation request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', [
                CancellationRequest::STATUS_REQUESTED,
                CancellationRequest::STATUS_PROCESSING,
                CancellationRequest::STATUS_MAILED,
                CancellationRequest::STATUS_CANCELLED,
                CancellationRequest::STATUS_DECLINED,
            ]),
            'remarks' => 'nullable|string|max:500',
        ]);

        $cancellationRequest = CancellationRequest::findOrFail($id);
        
        $cancellationRequest->status = $request->status;
        
        if ($request->filled('remarks')) {
            $cancellationRequest->remarks = $request->remarks;
        }
        
        if ($request->filled('error_message')) {
            $cancellationRequest->error_message = $request->error_message;
        }
        
        $cancellationRequest->save();

        Session::flash('alert-success', 'Cancellation request status updated successfully!');
        
        return redirect()->route('cancellation-requests.index');
    }

    /**
     * Show the form for editing a cancellation request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $cancellationRequest = CancellationRequest::with(['booking', 'webhook'])->findOrFail($id);
        
        return view('admin.cancellation.edit', compact('cancellationRequest'));
    }
}

