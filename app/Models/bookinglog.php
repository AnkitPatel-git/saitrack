<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bookinglog extends Model
{
      use HasFactory;
    protected $table = 'bookinglog';
    public $timestamps = true;
    protected $fillable = [
        'status','remark','bookingno','currentstatus','createdbyy','deliverydate','expecteddeliverydate'
    ];
}
