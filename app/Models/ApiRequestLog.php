<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $table = 'api_request_logs';

    protected $fillable = [
        'endpoint',
        'method',
        'request_data',
        'response_data',
        'status_code',
        'execution_time',
        'user_agent',
        'ip_address',
        'waybill_number',
        'api_type', // 'waybill_create', 'waybill_cancel', etc.
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'execution_time' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
