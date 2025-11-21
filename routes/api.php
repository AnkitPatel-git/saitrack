<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apicontroller;
use App\Http\Controllers\Api\unicommerceapicontroller;
use App\Http\Controllers\Api\BookingController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/login', [apicontroller::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', function (Request $request) {
         $user = $request->user();
        return response()->json([
        'status' => $user ? true : false,
        'data' => $user ? $user : null,
    ]);
    });
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::get('/dashboard', [BookingController::class, 'dashboard']);
    Route::post('/sticker_print', [BookingController::class, 'sticker_print']);
    Route::post('/createBooking', [BookingController::class, 'createBooking']);
     Route::post('/updateStatus/{id}', [BookingController::class, 'updateStatus']);
    
    
    
    
    return response()->json(['error' => 'Unauthenticated'], 401);
});
   Route::get('/shipment/Details/{id}', [apicontroller::class, 'show']);
   Route::get('/shipment/pod/{id}', [apicontroller::class, 'pod']);
   Route::post('/shipment/ndr_update', [apicontroller::class, 'ndr_update']);
   
   Route::get('/runwareeapijob', [apicontroller::class, 'runWareeApijob']);
   
//  unicommerce
     Route::post('/unicommerce/authToken', [unicommerceapicontroller::class, 'authToken']);
     Route::post('/unicommerce/waybill', [unicommerceapicontroller::class, 'waybill']);
     Route::post('/unicommerce/cancel', [unicommerceapicontroller::class, 'cancelWaybill']);
     Route::get('/unicommerce/waybillDetails', [unicommerceapicontroller::class, 'waybillDetails']);
     Route::get('/unicommerce/test-logging', [unicommerceapicontroller::class, 'testBlueDartLogging']);
     Route::get('/unicommerce/test-sticker/{waybill}', [unicommerceapicontroller::class, 'testShippingLabel']);