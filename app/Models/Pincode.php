<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pincode extends Model
{
     use HasFactory;
    protected $table = 'Pincode';
    public $timestamps = true;
    protected $fillable = [
        'pincode','area','state','district','air-servie','edlkmair','embargo','tat-air','surface-service','edlkmsurface','tat-surface','dp-service','tatdp'];
}