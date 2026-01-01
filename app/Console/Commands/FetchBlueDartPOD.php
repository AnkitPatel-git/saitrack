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
            ->whereIn('status', ['Delivered', 'SHIPMENT DELIVERED ', 'delivered', 'DELIVERED'])
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
                            // Try different base URLs for the webhook server
                            // The path might be relative to the webhook server root or a storage path
                            $imagePath = ltrim($firstImage, '/');
                            
                            // Try multiple possible base URLs
                            $possibleUrls = [
                                'https://webhook.sbfasto.com/' . $imagePath,
                                'https://webhook.sbfasto.com/storage/' . $imagePath,
                                'https://webhook.sbfasto.com/api/storage/' . $imagePath,
                                'https://webhook.sbfasto.com/public/' . $imagePath,
                                'https://webhook.sbfasto.com/public/storage/' . $imagePath,
                            ];
                            
                            // Use the first one for now, we'll try alternatives if it fails
                            $podImageUrl = $possibleUrls[0];
                        }
                    }

                    if ($podImageUrl) {
                        try {
                            $this->line("   Downloading POD image from URL...");
                            $this->line("   Attempting URL: " . substr($podImageUrl, 0, 100) . (strlen($podImageUrl) > 100 ? '...' : ''));
                            
                            // Download the image with authentication headers (same as API call)
                            $imageResponse = Http::withHeaders([
                                    'license-key' => $this->licenseKey,
                                    'client-id' => $this->clientId,
                                ])
                                ->timeout(60)
                                ->get($podImageUrl);
                            
                            // If 404, try alternative URLs with and without auth headers
                            if ($imageResponse->status() == 404 && !empty($podImages) && strpos($podImages[0], 'http') !== 0) {
                                $this->line("   First URL failed (404), trying alternatives...");
                                $imagePath = ltrim($podImages[0], '/');
                                $alternativeUrls = [
                                    'https://webhook.sbfasto.com/storage/' . $imagePath,
                                    'https://webhook.sbfasto.com/api/storage/' . $imagePath,
                                    'https://webhook.sbfasto.com/api/bluedart/storage/' . $imagePath,
                                    'https://webhook.sbfasto.com/api/bluedart/pod-image?path=' . urlencode($imagePath),
                                    'https://webhook.sbfasto.com/api/bluedart/pod/' . $waybillNo . '/image',
                                    'https://webhook.sbfasto.com/public/' . $imagePath,
                                    'https://webhook.sbfasto.com/public/storage/' . $imagePath,
                                ];
                                
                                $imageResponse = null;
                                foreach ($alternativeUrls as $altUrl) {
                                    $this->line("   Trying: " . substr($altUrl, 0, 80) . '...');
                                    
                                    // Try with auth headers first
                                    $testResponse = Http::withHeaders([
                                            'license-key' => $this->licenseKey,
                                            'client-id' => $this->clientId,
                                        ])
                                        ->timeout(30)
                                        ->get($altUrl);
                                    
                                    if ($testResponse->successful()) {
                                        $imageResponse = $testResponse;
                                        $podImageUrl = $altUrl;
                                        $this->line("   ✅ Found working URL!");
                                        break;
                                    }
                                    
                                    // If still 404, try without auth headers
                                    if ($testResponse->status() == 404) {
                                        $testResponseNoAuth = Http::timeout(30)->get($altUrl);
                                        if ($testResponseNoAuth->successful()) {
                                            $imageResponse = $testResponseNoAuth;
                                            $podImageUrl = $altUrl;
                                            $this->line("   ✅ Found working URL (no auth)!");
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            if (!$imageResponse || !$imageResponse->successful()) {
                                $status = $imageResponse ? $imageResponse->status() : 'No response';
                                $this->warn("   ⚠️  All URL attempts failed. Last status: {$status}");
                                $this->warn("   POD image path from API: " . ($podImages[0] ?? 'N/A'));
                                throw new \Exception("Failed to download POD image. Status: {$status}");
                            }
                            
                            // Get image content
                            $imageContent = $imageResponse->body();
                            
                            if (empty($imageContent)) {
                                throw new \Exception("Downloaded image is empty");
                            }
                            
                            // Extract filename from URL or generate one
                            $urlParts = parse_url($podImageUrl);
                            $path = $urlParts['path'] ?? '';
                            $originalFilename = basename($path);
                            
                            // Extract file extension
                            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
                            if (empty($extension)) {
                                // Try to detect from content type
                                $contentType = $imageResponse->header('Content-Type');
                                if (strpos($contentType, 'jpeg') !== false || strpos($contentType, 'jpg') !== false) {
                                    $extension = 'jpg';
                                } elseif (strpos($contentType, 'png') !== false) {
                                    $extension = 'png';
                                } else {
                                    $extension = 'jpg'; // Default
                                }
                            }
                            
                            // Generate filename: timestamp_WB_{waybill}.{ext}
                            $timestamp = time();
                            $fileName = $timestamp . '_WB_' . $waybillNo . '.' . $extension;
                            
                            // Create directory structure: storage/image/pod/{YEAR}/{MONTH}
                            $year = date('Y');
                            $month = date('m');
                            $destinationPath = public_path('storage/image/pod/' . $year . '/' . $month);
                            
                            // Ensure directory exists
                            if (!file_exists($destinationPath)) {
                                mkdir($destinationPath, 0777, true);
                            }
                            
                            // Save the image file
                            $filePath = $destinationPath . '/' . $fileName;
                            file_put_contents($filePath, $imageContent);
                            
                            // Verify file was saved
                            if (!file_exists($filePath) || filesize($filePath) == 0) {
                                throw new \Exception("Failed to save POD image file");
                            }
                            
                            // Store relative path in database (matching manual upload pattern)
                            $relativePath = 'image/pod/' . $year . '/' . $month . '/' . $fileName;
                            
                            // Update booking with local POD path
                            $booking->update(['pod' => $relativePath]);
                            
                            $this->info("✅ POD downloaded and saved for booking #{$booking->id} ({$waybillNo})");
                            $this->line("   Local path: {$relativePath}");
                            $this->line("   File size: " . number_format(filesize($filePath) / 1024, 2) . " KB");
                            if ($podReceivedBy) {
                                $this->line("   Received By: {$podReceivedBy}");
                            }
                            $successCount++;
                            
                        } catch (\Exception $e) {
                            $this->error("💥 Error downloading/saving POD for waybill {$waybillNo}: " . $e->getMessage());
                            
                            // If we have POD data (scans or delivery details) but can't download image,
                            // at least store the image path from API for reference
                            if ($hasPODScan || $deliveryDetails) {
                                if (!empty($podImages)) {
                                    // Store the relative path from API as a reference
                                    $relativePathFromApi = $podImages[0];
                                    try {
                                        // Store it as-is (might be a path that can be accessed later)
                                        $booking->update(['pod' => $relativePathFromApi]);
                                        $this->warn("   ⚠️  Stored POD path from API (image download failed): {$relativePathFromApi}");
                                        $skippedCount++; // Count as skipped since image wasn't downloaded
                                    } catch (\Exception $dbError) {
                                        $this->error("   ❌ Also failed to store POD path in DB: " . $dbError->getMessage());
                                        $errorCount++;
                                    }
                                } else {
                                    $errorCount++;
                                }
                            } else {
                                $errorCount++;
                            }
                        }
                    } else {
                        // POD data exists but no image URL
                        // If we have POD scans or delivery details, we can still mark POD as received
                        if ($hasPODScan || $deliveryDetails) {
                            $this->warn("⚠️  POD data found but no image URL for waybill {$waybillNo}");
                            $this->line("   POD scan or delivery details exist, but no image available");
                            // Don't update booking since we don't have an image to store
                            $skippedCount++;
                        } else {
                            $this->warn("⚠️  POD data found but no image URL to save for waybill {$waybillNo}");
                            $skippedCount++;
                        }
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

