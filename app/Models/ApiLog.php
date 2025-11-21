<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_log';

    public $timestamps = true;

    protected $fillable = [
        'code',
        'displayOrderCode',
        'channel',
        'source',
        'displayOrderDateTime',
        'status',
        'created',
        'updated',
        'fulfillmentTat',
        'notificationEmail',
        'notificationMobile',
        'customerGSTIN',
    ];

    protected $casts = [
        'displayOrderDateTime' => 'datetime',
        'created'              => 'datetime',
        'updated'              => 'datetime',
        'fulfillmentTat'       => 'datetime',
    ];

    // Mutators to convert from milliseconds to datetime
    public function setDisplayOrderDateTimeAttribute($value)
    {
        $this->attributes['displayOrderDateTime'] = Carbon::createFromTimestampMs($value);
    }

    public function setCreatedAttribute($value)
    {
        $this->attributes['created'] = Carbon::createFromTimestampMs($value);
    }

    public function setUpdatedAttribute($value)
    {
        $this->attributes['updated'] = Carbon::createFromTimestampMs($value);
    }

    public function setFulfillmentTatAttribute($value)
    {
        $this->attributes['fulfillmentTat'] = Carbon::createFromTimestampMs($value);
    }
}
