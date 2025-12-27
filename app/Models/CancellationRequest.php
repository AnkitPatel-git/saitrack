<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationRequest extends Model
{
    use HasFactory;

    protected $table = 'cancellation_requests';
    public $timestamps = true;

    protected $fillable = [
        'waybill',
        'booking_id',
        'status',
        'client_id',
        'remarks',
        'provider',
        'error_message'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_REQUESTED = 'requested';
    const STATUS_PROCESSING = 'processing';
    const STATUS_MAILED = 'mailed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_DECLINED = 'declined';

    /**
     * Get the booking associated with this cancellation request
     */
    public function booking()
    {
        return $this->belongsTo(booking::class, 'booking_id');
    }

    /**
     * Get the webhook/client associated with this cancellation request
     */
    public function webhook()
    {
        return $this->belongsTo(Webhook::class, 'client_id');
    }

    /**
     * Check if status is a valid cancellation status
     */
    public static function isValidStatus($status): bool
    {
        return in_array($status, [
            self::STATUS_REQUESTED,
            self::STATUS_PROCESSING,
            self::STATUS_MAILED,
            self::STATUS_CANCELLED,
            self::STATUS_DECLINED,
        ]);
    }
}

