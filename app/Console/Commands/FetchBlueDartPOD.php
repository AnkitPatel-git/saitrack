<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\booking;
use Carbon\Carbon;

class FetchBlueDartPOD extends Command
{
    protected $signature = 'pod:fetch-bluedart';
    protected $description = 'Fetch POD details from BlueDart webhook API and save to bookings';

    private $webhookBaseUrl = 'https://webhook.sbfasto.com/api/bluedart/shipments';
    private $licenseKey = '9ef1c4a2c87b51e0d3fa72c19b6dd0447c3bb25a1f98c4daee3f0a91c8a7e5d2';
    private $clientId = 'BD1CB263A3F101';

    public function handle()
    {
        $this->info("Starting BlueDart POD fetch...");

        // Fetch bookings with BlueDart waybills (11 character waybills)
        // Check waybills, refrenceno, or forwordingno fields
        // Focus on delivered bookings or those that might have POD available
        $bookings = booking::where(function($query) {
                $query->where(function($q) {
                    $q->whereRaw('CHAR_LENGTH(waybills) = 11')
                      ->whereNotNull('waybills')
                      ->where('waybills', '!=', '');
                })
                ->orWhere(function($q) {
                    $q->whereRaw('CHAR_LENGTH(refrenceno) = 11')
                      ->whereNotNull('refrenceno')
                      ->where('refrenceno', '!=', '');
                })
                ->orWhere(function($q) {
                    $q->whereRaw('CHAR_LENGTH(forwordingno) = 11')
                      ->whereNotNull('forwordingno')
                      ->where('forwordingno', '!=', '');
                });
            })
            ->whereIn('status', ['Delivered', 'SHIPMENT DELIVERED'])
            ->where('created_at', '>=', Carbon::now()->subDays(30)) // Last 30 days
            ->get();

        $this->info("Found {$bookings->count()} bookings to check for POD...");

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($bookings as $booking) {
            // Get waybill number from any of the fields (priority: waybills > refrenceno > forwordingno)
            $waybillNo = $booking->refrenceno ?: $booking->forwordingno ?: $booking->waybills;

            if (empty($waybillNo) || strlen($waybillNo) != 11) {
                $this->warn("Skipping booking #{$booking->id} - Invalid waybill number");
                $skippedCount++;
                continue;
            }

            // Skip if POD already exists
            if (!empty($booking->pod)) {
                $this->warn("Skipping booking #{$booking->id} ({$waybillNo}) - POD already exists");
                $skippedCount++;
                continue;
            }

            try {
                $this->info("Fetching POD for waybill: {$waybillNo} (Booking #{$booking->id})");

                $response = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'license-key' => $this->licenseKey,
                        'client-id' => $this->clientId,
                    ])
                    ->timeout(30)
                    ->get("{$this->webhookBaseUrl}/{$waybillNo}");

                if (!$response->successful()) {
                    $this->error("❌ API failed for waybill {$waybillNo} - Status: {$response->status()}");
                    $errorCount++;
                    continue;
                }

                $data = $response->json();

                if (!isset($data['success']) || !$data['success'] || !isset($data['data'])) {
                    $this->warn("⚠️  No POD data available for waybill {$waybillNo}");
                    $errorCount++;
                    continue;
                }

                $shipmentData = $data['data'];

                // Extract POD information
                $podImages = [];
                $deliveryDetails = null;
                $podReceivedBy = null;
                $podSignature = null;

                // Get POD images from epod
                if (isset($shipmentData['epod']['pod_images']) && is_array($shipmentData['epod']['pod_images'])) {
                    $podImages = $shipmentData['epod']['pod_images'];
                }

                // Get delivery details
                if (isset($shipmentData['delivery_details'])) {
                    $deliveryDetails = $shipmentData['delivery_details'];
                    $podReceivedBy = $deliveryDetails['received_by'] ?? null;
                    $podSignature = $deliveryDetails['signature'] ?? null;
                }

                // Check if there are POD scans
                $hasPODScan = false;
                if (isset($shipmentData['scans']) && is_array($shipmentData['scans'])) {
                    foreach ($shipmentData['scans'] as $scan) {
                        if (isset($scan['scan_code']) && $scan['scan_code'] === 'POD') {
                            $hasPODScan = true;
                            break;
                        }
                    }
                }

                // If we have POD data, save it
                if (!empty($podImages) || $hasPODScan || $deliveryDetails) {
                    // Store POD image URL (use first image if available)
                    $podImageUrl = null;
                    if (!empty($podImages)) {
                        // If the image path is relative, construct full URL
                        $firstImage = $podImages[0];
                        if (strpos($firstImage, 'http') === 0) {
                            $podImageUrl = $firstImage;
                        } else {
                            // Assume it's a relative path from the webhook server
                            $podImageUrl = 'https://webhook.sbfasto.com/' . ltrim($firstImage, '/');
                        }
                    }

                    // Update booking with POD information
                    $updateData = [];

                    if ($podImageUrl) {
                        $updateData['pod'] = $podImageUrl;
                    }

                    // Store additional POD data as JSON in a custom field if needed
                    // For now, we'll store the image URL in the pod field
                    if (!empty($updateData)) {
                        $booking->update($updateData);
                        $this->info("✅ POD saved for booking #{$booking->id} ({$waybillNo})");
                        if ($podImageUrl) {
                            $this->line("   POD Image: {$podImageUrl}");
                        }
                        if ($podReceivedBy) {
                            $this->line("   Received By: {$podReceivedBy}");
                        }
                        $successCount++;
                    } else {
                        $this->warn("⚠️  POD data found but no image URL to save for waybill {$waybillNo}");
                        $skippedCount++;
                    }
                } else {
                    $this->warn("⚠️  No POD data found for waybill {$waybillNo}");
                    $skippedCount++;
                }

            } catch (\Exception $e) {
                $this->error("💥 Error fetching POD for waybill {$waybillNo}: " . $e->getMessage());
                $errorCount++;
            }

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        $this->info("\n=== POD Fetch Summary ===");
        $this->info("✅ Success: {$successCount}");
        $this->info("⚠️  Skipped: {$skippedCount}");
        $this->info("❌ Errors: {$errorCount}");
        $this->info("Total processed: " . ($successCount + $skippedCount + $errorCount));
    }
}

