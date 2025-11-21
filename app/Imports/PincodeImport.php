<?php

namespace App\Imports;

use App\Models\Pincode;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PincodeImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        
        $existingRecord = DB::table('Pincode')->where('pincode', $row['pincode'])->first();
    
        if (!$existingRecord) {
            return new Pincode([
                'pincode' => $row['pincode'],
                'area' => $row['area'],
                'district' => $row['district'],
                'state' => $row['state'],
                'air-servie' => $row['air_service'],
                'edlkmair' => $row['edl_km_air'],
                'embargo' => $row['embargo'],
                'tat-air' => $row['tat_air'],
                'surface-service' => $row['surface_service'],
                'edlkmsurface' => $row['edl_km_surface'],
                'tat-surface' => $row['tat_surface'],
                'dp-service' => $row['dp_service'],
                'tatdp' => $row['tat_dp'],
            ]);
        }

        return null; // Skip existing records
    }

    public function batchSize(): int
    {
        return 100;
    }
    
    public function chunkSize(): int
    {
        return 100;
    }
}
