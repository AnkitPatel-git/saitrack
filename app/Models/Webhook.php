<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Webhook extends Model
{
    use HasFactory;

    protected $table = 'webhook_orgs';
    public $timestamps = true;

    protected $fillable = [
        'organization_name',
        'contact_email',
        'api_key',
        'api_secret',
        'is_active',
        'token',
        'test',
        'service_provider'
    ];
}
