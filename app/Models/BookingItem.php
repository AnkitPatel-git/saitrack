<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingItem extends Model
{
    use HasFactory;

    protected $table = 'booking_items';
    protected $fillable = [
        'booking_id',
        'name',
        'description',
        'quantity',
        'skuCode',
        'itemPrice',
        'imageURL',
        'hsnCode',
        'tags',
        'brand',
        'color',
        'category',
        'size',
        'item_details',
        'ean',
        'return_reason'
    ];

    protected $casts = [
        'item_details' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
