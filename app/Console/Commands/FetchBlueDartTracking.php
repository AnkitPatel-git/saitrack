<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\booking;
use App\Models\bookinglog;
use Carbon\Carbon;

class FetchBlueDartTracking extends Command
{
    protected $signature = 'tracking:fetch';
    protected $description = 'Fetch shipment tracking data from BlueDart API and save scans into bookinglog';

    public function handle()
    {
        $apiUrl   = "https://apigateway.bluedart.com/in/transportation/tracking/v1/shipment";
        $tokenUrl = "https://apigateway.bluedart.com/in/transportation/token/v1/login";

        // 🔹 Fetch new JWT token
        $this->info("Fetching new JWT token...");
        try {
            $res = Http::withHeaders([
                    "ClientID"     => "D7FGUzOG0AvjIGT6uGLRXs6AH8GbzhbA",
                    "ClientSecret" => "bIkWdoWf3vXyjFVW",
                    "Content-Type" => "application/json",
                ])
                ->withBody(json_encode([
                    "email"    => "admin@gmail.com",
                    "password" => "123456"
                ]), 'application/json')
                ->get($tokenUrl);

            $body = $res->json();
            if ($res->successful() && isset($body['JWTToken'])) {
                $jwt = $body['JWTToken'];
                $this->info("✅ Got JWT token: " . $jwt);
            } else {
                $this->error("❌ Failed to get JWT token. Status: {$res->status()} Body: " . $res->body());
                return;
            }
        } catch (\Exception $e) {
            $this->error("💥 Error fetching JWT: " . $e->getMessage());
            return;
        }

        // 🔹 Fetch only valid bookings in last 15 days
        $bookings = booking::whereRaw('CHAR_LENGTH(refrenceno) = 11')
            ->whereNotIn('status', ['Delivered', 'Cancelled'])
            ->where('created_at', '>=', Carbon::now()->subDays(15))
            ->get();

        $this->info("Processing {$bookings->count()} bookings from last 15 days...");

        foreach ($bookings as $booking) {
            $params = [
                "handler" => "tnt",
                "loginid" => "BOM77977",
                "numbers" => $booking->refrenceno,
                "format"  => "json",
                "lickey"  => "qrisjiiqul0ztmhsvgemgqlpopqjhonk",
                "scan"    => 1,
                "action"  => "custawbquery",
                "verno"   => 1,
                "awb"     => "awb",
            ];

            try {
                $response = Http::withHeaders([
                        'Accept'   => 'application/json',
                        'JWTToken' => $jwt,
                    ])
                    ->timeout(30)
                    ->get($apiUrl, $params);

                if ($response->successful()) {
                    $data = $response->json();
                    $shipments = $data['ShipmentData']['Shipment'] ?? [];

                    if (empty($shipments)) {
                        $this->warn("No shipment data for booking #{$booking->id}");
                        continue;
                    }

                    foreach ($shipments as $shipment) {
                        if (!isset($shipment['Scans'])) continue;

                        // Parse ExpectedDelivery date
                        $expectedDeliveryRaw = $shipment['ExpectedDelivery'] ?? null;
                        $expectedDelivery = null;
                        if ($expectedDeliveryRaw) {
                            $dt = \DateTime::createFromFormat('d F Y', $expectedDeliveryRaw);
                            if ($dt) {
                                $expectedDelivery = $dt->format('Y-m-d') . ' 00:00:00';
                            }
                        }

                        foreach (array_reverse($shipment['Scans']) as $scan) {
                            $scanDetail = $scan['ScanDetail'] ?? null;
                            if (!$scanDetail) continue;

                            // Parse ScanDate + ScanTime
                            $scanDateTime = null;
                            if (!empty($scanDetail['ScanDate']) && !empty($scanDetail['ScanTime'])) {
                                $dt = \DateTime::createFromFormat('d-M-Y H:i', $scanDetail['ScanDate'].' '.$scanDetail['ScanTime']);
                                if ($dt) {
                                    $scanDateTime = $dt->format('Y-m-d H:i:s');
                                }
                            }

                            // Extract last part of ScannedLocation for currentstatus
                            $scannedLocation = $scanDetail['ScannedLocation'] ?? null;
                            $currentStatus = null;
                            if ($scannedLocation) {
                                $parts = explode(',', $scannedLocation);
                                $currentStatus = trim(end($parts)); // last part
                            }

                            // check duplicates
                            $exists = bookinglog::where('bookingno', $booking->id)
                                ->where('status', $scanDetail['Scan'])
                                ->where('deliverydate', $scanDateTime)
                                ->exists();

                            if (!$exists) {
                                bookinglog::create([
                                    'bookingno'             => $booking->id,
                                    'status'                => $scanDetail['Scan'],
                                    'remark'                => $scanDetail['ScannedLocation'] ?? null,
                                    'currentstatus'         => $currentStatus,
                                    'deliverydate'          => $scanDateTime,
                                    'expecteddeliverydate'  => $expectedDelivery,
                                    'createdbyy'            => 'system',
                                ]);
                            }
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
