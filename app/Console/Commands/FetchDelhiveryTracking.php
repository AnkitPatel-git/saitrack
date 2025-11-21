<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\booking;
use App\Models\bookinglog;
use Carbon\Carbon;

class FetchDelhiveryTracking extends Command
{
    protected $signature = 'tracking:delhivery';
    protected $description = 'Fetch shipment tracking data from Delhivery API and save scans into bookinglog';

    public function handle()
    {
        $loginUrl   = "https://ltl-clients-api.delhivery.com/ums/login";
        $trackUrl   = "https://ltl-clients-api.delhivery.com/lrn/track";

        // 🔹 Step 1: Login and get JWT
        $this->info("Fetching Delhivery JWT...");
        try {
            $res = Http::post($loginUrl, [
                "username" => "SBTECHNOWORLDSOLUTIONDCB2BRC",
                "password" => "A4aFT2zFdDkBA9@"
            ]);

            $body = $res->json();
            if ($res->successful() && isset($body['data']['jwt'])) {
                $jwt = $body['data']['jwt'];
                $this->info("✅ Got JWT");
            } else {
                $this->error("❌ Failed to login. Status: {$res->status()} Body: " . $res->body());
                return;
            }
        } catch (\Exception $e) {
            $this->error("💥 Error fetching JWT: " . $e->getMessage());
            return;
        }
       

        // 🔹 Step 2: Fetch recent bookings
        $bookings = booking::whereNotIn('status', ['Delivered', 'Cancelled'])
            ->where('created_at', '>=', Carbon::now()->subDays(15))
            ->get();

        $this->info("Processing {$bookings->count()} bookings...");

        foreach ($bookings as $booking) {
             if (!preg_match('/^[1-9][0-9]{8}$/', $booking->refrenceno)) {
        $this->warn("⏭️ Skipping booking #{$booking->id} — invalid LR number: {$booking->refrenceno}");
        continue;
    }
            try {
                $response = Http::withHeaders([
                    "Authorization" => "Bearer {$jwt}",   // ✅ use Bearer prefix
                    "Accept"        => "application/json"
                ])->get($trackUrl, [
                    "lrnum" => $booking->refrenceno
                ]);
                 
                if ($response->successful()) {
                    $data = $response->json()['data'] ?? null;
                    if (!$data || empty($data['wbns'])) {
                        $this->warn("No tracking data for booking #{$booking->id}, #{$booking->refrenceno}, #{$booking->forwordingno} ");
                        continue;
                    }

                    foreach (array_reverse($data['wbns']) as $scan) {
                        $status   = $scan['status'] ?? null;
                        $remark   = $scan['scan_remark'] ?? null;
                        $location = $scan['location'] ?? null;
                        $scanDate = isset($scan['scan_timestamp'])
                            ? Carbon::parse($scan['scan_timestamp'])->format('Y-m-d H:i:s')
                            : null;

                        $expected = isset($scan['estimated_date'])
                            ? Carbon::parse($scan['estimated_date'])->format('Y-m-d H:i:s')
                            : null;

                        // check duplicates
                        $exists = bookinglog::where('bookingno', $booking->id)
                            ->where('status', $status)
                            ->where('deliverydate', $scanDate)
                            ->exists();

                        if (!$exists) {
                            bookinglog::create([
                                'bookingno'             => $booking->id,
                                'status'                => $status,
                                'remark'                => $remark ?? $location,
                                'currentstatus'         => $location,
                                'deliverydate'          => $scanDate,
                                'expecteddeliverydate'  => $expected,
                                'createdbyy'            => 'system',
                            ]);
                        }
                    }
                    // Update booking status with latest scan status from database
                    $latestLog = bookinglog::where('bookingno', $booking->id)
                        ->orderBy('deliverydate', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->first();
                        
                     if ($latestLog && $latestLog->status) {
                        $booking->update(['status' => $latestLog->status]);
                        $this->info("✅ Tracking updated for booking #{$booking->id} ({$booking->refrenceno}) - Status: {$latestLog->status}");
                    } else {
                        $this->info("✅ Tracking updated for booking #{$booking->id} ({$booking->refrenceno})");
                    }
                } else {
                    $this->error("❌ API failed for booking #{$booking->id} ({$booking->refrenceno}) "
                        . "Status: {$response->status()} "
                        . "Body: " . substr($response->body(), 0, 500));
                }
            } catch (\Exception $e) {
                $this->error("💥 Error for booking #{$booking->id}: " . $e->getMessage());
            }
        }
    }
}
