<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\bookinglog;
class booking extends Model
{
     use HasFactory;
    protected $table = 'booking';
    public $timestamps = true;
    protected $fillable = [
        'cust_name','modeoftrans','forwordingno','refrenceno','deliverylocation','pickuplocation','status','product_type','content','weight','vol_weight','charg_weight','client_name',
        'pickupaddress','pickup_name','pickupcity','pickup_pincode','sendercontactno','con_client_name','receiveraddress','receiverstate','receivercity','receiver_pincode',
        'receivercontactno','booking_date','rto_office_name', 'rto_address', 'rto_pincode','preferred_date','rto_phone_number','pices','value','service_type','delivery_type','invoice_no','waybills','dims','clientid','pod',
        'created_by','assign_to','dimension','claimid','lr_number'
    ];
    protected $casts = [
    'dimension' => 'array',
];
     public function bookingLogs()
    {
        return $this->hasMany(bookinglog::class, 'bookingno', 'id');
    }
    public function items()
{
    return $this->hasMany(BookingItem::class, 'booking_id');
}
}
