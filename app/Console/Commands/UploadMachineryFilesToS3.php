<?php

namespace App\Console\Commands;

use App\Models\MachineryFileManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadMachineryFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'machinery:upload-to-s3 {--id= : Upload a specific file ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload local machinery images and videos to S3/Cloudflare R2, store full URLs in DB, and update status to 1.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting machinery file migration to S3/Cloudflare R2...');

        $query = MachineryFileManager::where('status', 0);

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        $pendingFiles = $query->get();

        if ($pendingFiles->isEmpty()) {
            $this->info('No pending machinery files found with status = 0.');
            return 0;
        }

        $this->info("Found {$pendingFiles->count()} file(s) to process.");

        $successCount = 0;
        $failCount = 0;

        foreach ($pendingFiles as $fileRecord) {
            $path = $fileRecord->image_path;

            // If path is already a full URL, mark status as 1
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $fileRecord->status = 1;
                $fileRecord->save();
                $this->info("[ID {$fileRecord->id}] Already full URL. Status updated to 1.");
                $successCount++;
                continue;
            }

            $filename = basename(ltrim($path, '/'));

            if ($fileRecord->type === 'video') {
                $customPath = 'E:\Wamp64\www\machinery\videos\\' . $filename;
                $defaultPath = public_path('uploads/machinery/videos/' . $filename);
                $localFilePath = File::exists($customPath) ? $customPath : $defaultPath;
                $folder = 'videos';
            } else {
                $customPath = 'E:\Wamp64\www\machinery\images\\' . $filename;
                $defaultPath = public_path('uploads/machinery/images/' . $filename);
                $localFilePath = File::exists($customPath) ? $customPath : $defaultPath;
                $folder = 'images';
            }

            if (!File::exists($localFilePath)) {
                $this->warn("[ID {$fileRecord->id}] Local file not found at: {$localFilePath}");
                $failCount++;
                continue;
            }

            try {
                $s3Directory = "uploads/machinery/{$folder}";
                $s3Path = "{$s3Directory}/{$filename}";

                // Upload to S3/R2 with public visibility
                Storage::disk('s3')->putFileAs($s3Directory, new \Illuminate\Http\File($localFilePath), $filename, 'public');

                $fullUrl = Storage::disk('s3')->url($s3Path);

                // Update database record
                $fileRecord->image_path = $fullUrl;
                $fileRecord->status = 1;
                $fileRecord->save();

                // Move uploaded local file to E:\Wamp64\www\machinery\s3_uploaded\{folder}\
                $s3UploadedDir = "E:\\Wamp64\\www\\machinery\\s3_uploaded\\{$folder}";
                if (!File::exists($s3UploadedDir)) {
                    File::makeDirectory($s3UploadedDir, 0755, true);
                }

                $destinationPath = $s3UploadedDir . DIRECTORY_SEPARATOR . $filename;
                if (File::exists($localFilePath)) {
                    File::move($localFilePath, $destinationPath);
                    $this->info("[ID {$fileRecord->id}] Moved local file to -> {$destinationPath}");
                }

                $this->info("[ID {$fileRecord->id}] Successfully uploaded -> {$fullUrl}");
                $successCount++;
            } catch (\Throwable $e) {
                $this->error("[ID {$fileRecord->id}] Error uploading: " . $e->getMessage());
                $failCount++;
            }
        }

        $this->info("Processing complete! {$successCount} succeeded, {$failCount} failed.");
        return 0;
    }
}
