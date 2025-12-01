<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PincodeServiceabilityCache extends Model
{
    use HasFactory;

    protected $table = 'pincode_serviceability_cache';
    
    public $timestamps = false; // We use cached_at instead
    
    protected $fillable = [
        'pincode',
        'weight',
        'test',
        'serviceBy',
        'is_serviceable',
        'serviceability_data',
        'full_response_data',
        'cached_at',
    ];

    protected $casts = [
        'pincode' => 'string',
        'weight' => 'decimal:2',
        'test' => 'boolean',
        'serviceBy' => 'string',
        'is_serviceable' => 'boolean',
        'serviceability_data' => 'array',
        'full_response_data' => 'array',
        'cached_at' => 'datetime',
    ];

    /**
     * Find cache entry
     */
    public static function findCache(string $pincode, float $weight, bool $test, string $serviceBy): ?self
    {
        return self::where('pincode', $pincode)
            ->where('weight', $weight)
            ->where('test', $test)
            ->where('serviceBy', $serviceBy)
            ->first();
    }

    /**
     * Create or update cache entry
     */
    public static function storeCache(
        string $pincode,
        float $weight,
        bool $test,
        string $serviceBy,
        bool $isServiceable,
        ?array $serviceabilityData = null,
        ?array $fullResponseData = null
    ): self {
        return self::updateOrCreate(
            [
                'pincode' => $pincode,
                'weight' => $weight,
                'test' => $test,
                'serviceBy' => $serviceBy,
            ],
            [
                'is_serviceable' => $isServiceable,
                'serviceability_data' => $serviceabilityData,
                'full_response_data' => $fullResponseData,
                'cached_at' => now(),
            ]
        );
    }
}
