<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\booking;
use Carbon\Carbon;

class FetchDelhiveryPOD extends Command
{
    protected $signature = 'pod:fetch-delhivery';
    protected $description = 'Fetch POD details from Delhivery API and save to bookings';

    private $baseUrl = 'https://ltl-clients-api.delhivery.com';
    private $loginUrl;
    private $documentUrl;
    
    // Production credentials
    private $username = 'SBTECHNOWORLDSOLUTIONDCB2BRC';
    private $password = 'A4aFT2zFdDkBA9@';
    
    private $jwtToken = null;

    public function __construct()
    {
        parent::__construct();
        $this->loginUrl = "{$this->baseUrl}/ums/login";
        $this->documentUrl = "{$this->baseUrl}/document/download";
    }

    /**
     * Authenticate and get JWT token
     */
    private function authenticate(): string
    {
        if ($this->jwtToken) {
            return $this->jwtToken;
        }

        $this->info("Fetching Delhivery JWT token...");
        try {
            $response = Http::post($this->loginUrl, [
                "username" => $this->username,
                "password" => $this->password
            ]);

            $body = $response->json();
            if ($response->successful() && isset($body['data']['jwt'])) {
                $this->jwtToken = $body['data']['jwt'];
                $this->info("✅ Got JWT token");
                return $this->jwtToken;
            } else {
                throw new \Exception("Failed to login. Status: {$response->status()} Body: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->error("❌ Error fetching JWT: " . $e->getMessage());
            throw $e;
        }
    }

    public function handle()
    {
        $this->info("Starting Delhivery POD fetch...");

        // Authenticate first
        try {
            $jwt = $this->authenticate();
        } catch (\Exception $e) {
            $this->error("Authentication failed. Exiting.");
            return;
        }

        // Fetch bookings with Delhivery LRN numbers (9 digits)
        // Check lr_number and refrenceno fields
        // Focus on delivered bookings
        $bookings = booking::where(function($query) {
                $query->where(function($q) {
                    $q->whereNotNull('lr_number')
                      ->where('lr_number', '!=', '')
                      ->whereRaw('CHAR_LENGTH(lr_number) = 9')
                      ->whereRaw('lr_number REGEXP "^[1-9][0-9]{8}$"');
                })
                ->orWhere(function($q) {
                    $q->whereNotNull('refrenceno')
                      ->where('refrenceno', '!=', '')
                      ->whereRaw('CHAR_LENGTH(refrenceno) = 9')
                      ->whereRaw('refrenceno REGEXP "^[1-9][0-9]{8}$"');
                });
            })
            ->whereIn('status', ['Delivered', 'delivered', 'DELIVERED'])
            ->where('created_at', '>=', Carbon::now()->subDays(30)) // Last 30 days
            ->get();

        $this->info("Found {$bookings->count()} bookings to check for POD...");

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($bookings as $booking) {
            // Get LRN number from lr_number or refrenceno field
            $lrn = $booking->lr_number ?: $booking->refrenceno;

            if (empty($lrn) || !preg_match('/^[1-9][0-9]{8}$/', $lrn)) {
                $this->warn("Skipping booking #{$booking->id} - Invalid LRN number: {$lrn}");
                $skippedCount++;
                continue;
            }

            // Skip if POD already exists
            if (!empty($booking->pod)) {
                $this->warn("Skipping booking #{$booking->id} ({$lrn}) - POD already exists");
                $skippedCount++;
                continue;
            }

            try {
                $this->info("Fetching POD for LRN: {$lrn} (Booking #{$booking->id})");

                $response = Http::withHeaders([
                        'Authorization' => "Bearer {$jwt}",
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(30)
                    ->get($this->documentUrl, [
                        'lrn' => $lrn,
                        'doc_type' => 'LM_POD',
                        'auto_download' => 'false',
                        'version' => 'latest'
                    ]);

                if (!$response->successful()) {
                    $this->error("❌ API failed for LRN {$lrn} - Status: {$response->status()}");
                    if ($response->status() == 404) {
                        $this->line("   POD not available for this LRN");
                    }
                    $errorCount++;
                    continue;
                }

                $data = $response->json();

                // Check if we got a download URL or document data
                $podUrl = null;
                
                // The API returns files array in data.files
                // Check for the actual response structure: data.files[0].url
                if (isset($data['data']['files']) && is_array($data['data']['files']) && !empty($data['data']['files'])) {
                    $podUrl = $data['data']['files'][0]['url'] ?? null;
                } elseif (isset($data['data']['download_url'])) {
                    $podUrl = $data['data']['download_url'];
                } elseif (isset($data['data']['url'])) {
                    $podUrl = $data['data']['url'];
                } elseif (isset($data['download_url'])) {
                    $podUrl = $data['download_url'];
                } elseif (isset($data['url'])) {
                    $podUrl = $data['url'];
                } elseif (isset($data['data']) && is_string($data['data'])) {
                    // Sometimes the URL is directly in data
                    $podUrl = $data['data'];
                }

                if ($podUrl) {
                    try {
                        $this->line("   Downloading POD image from URL...");
                        
                        // Download the image
                        $imageResponse = Http::timeout(60)->get($podUrl);
                        
                        if (!$imageResponse->successful()) {
                            throw new \Exception("Failed to download POD image. Status: {$imageResponse->status()}");
                        }
                        
                        // Get image content
                        $imageContent = $imageResponse->body();
                        
                        if (empty($imageContent)) {
                            throw new \Exception("Downloaded image is empty");
                        }
                        
                        // Extract filename from URL or generate one
                        $urlParts = parse_url($podUrl);
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
                        
                        // Generate filename: timestamp_LRN_{lrn}.{ext}
                        $timestamp = time();
                        $fileName = $timestamp . '_LRN_' . $lrn . '.' . $extension;
                        
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
                        
                        $this->info("✅ POD downloaded and saved for booking #{$booking->id} ({$lrn})");
                        $this->line("   Local path: {$relativePath}");
                        $this->line("   File size: " . number_format(filesize($filePath) / 1024, 2) . " KB");
                        $successCount++;
                        
                    } catch (\Exception $e) {
                        $this->error("💥 Error downloading/saving POD for LRN {$lrn}: " . $e->getMessage());
                        $errorCount++;
                    }
                } else {
                    $this->warn("⚠️  POD data found but no download URL for LRN {$lrn}");
                    $this->line("   Response: " . json_encode($data));
                    $skippedCount++;
                }

            } catch (\Exception $e) {
                $this->error("💥 Error fetching POD for LRN {$lrn}: " . $e->getMessage());
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

