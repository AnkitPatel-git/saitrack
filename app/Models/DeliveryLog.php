<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\booking;

class DeliveryLog extends Model
{
    use HasFactory;

    protected $table = 'delivery_logs';
    public $timestamps = true;

    protected $fillable = [
        'booking_id',
        'delivery_provider',
        'api_endpoint',
        'request_payload',
        'response_data',
        'status_code',
        'is_success',
        'awb_number',
        'token_number',
        'error_message',
        'provider_specific_data',
    ];

    protected $casts = [
        'is_success' => 'boolean',
        'request_payload' => 'array',
        'response_data' => 'array',
        'provider_specific_data' => 'array',
    ];
}
