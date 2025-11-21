<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $table = 'warehouses';
    public $timestamps = true;

    protected $fillable = [
        'pin_code',
        'city',
        'state',
        'country',
        'name',
        'address_details',
        'business_hours',
        'pick_up_hours',
        'pick_up_days',
        'business_days',
        'ret_address',
        'warehouse_id', // Store the ID returned from Delhivery API
        'is_active',
    ];

    protected $casts = [
        'address_details' => 'array',
        'business_hours' => 'array',
        'pick_up_hours' => 'array',
        'pick_up_days' => 'array',
        'business_days' => 'array',
        'ret_address' => 'array',
        'is_active' => 'boolean',
    ];
}
