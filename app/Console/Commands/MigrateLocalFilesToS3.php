<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Services\S3StorageService;

class MigrateLocalFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-to-s3 {--dry-run : Run without actually uploading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate all local uploaded files, images, PDFs, signatures, and licenses to S3 storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("--- DRY RUN MODE (No files will be uploaded) ---");
        }

        $this->info("=== Starting Category & Settings Migration to S3 ===");

        $uploadedFiles = 0;
        $failedFiles = 0;

        // 1. Process Category Images from Database
        $categories = \App\Models\Category::all();
        $this->info("Found " . $categories->count() . " categories in database.");

        foreach ($categories as $category) {
            if (!$category->image) {
                continue;
            }

            $imageArray = json_decode($category->image, true);
            $filenames = is_array($imageArray) ? $imageArray : [$category->image];

            foreach ($filenames as $filename) {
                if (empty($filename)) continue;

                // Cleanup filename if full URL stored
                $cleanFilename = basename(parse_url($filename, PHP_URL_PATH));
                
                $localPath = public_path('uploads/category/images/' . $cleanFilename);
                $s3Path = 'category/images/' . $cleanFilename;

                if (!File::exists($localPath)) {
                    $this->warn("Local file missing for category [ID {$category->id}]: {$localPath}");
                    continue;
                }

                $this->line("Uploading Category Image: {$cleanFilename} -> S3 ({$s3Path})");

                if ($isDryRun) {
                    $uploadedFiles++;
                    continue;
                }

                try {
                    $content = File::get($localPath);
                    \Illuminate\Support\Facades\Storage::disk(S3StorageService::disk())->put($s3Path, $content);
                    $this->info("  ✓ Uploaded to S3!");
                    $uploadedFiles++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed: " . $e->getMessage());
                    $failedFiles++;
                }
            }
        }

        // 2. Process Settings Logos from Database
        $whiteLogo = \App\Models\Settings::get('white_logo');
        $darkLogo = \App\Models\Settings::get('dark_logo');

        $logos = array_filter([$whiteLogo, $darkLogo]);

        foreach ($logos as $logoPath) {
            $cleanPath = ltrim(str_replace('settings/', '', $logoPath), '/');
            $localPath = public_path('settings/' . $cleanPath);
            $s3Path = 'settings/' . $cleanPath;

            if (File::exists($localPath)) {
                $this->line("Uploading Setting Logo: {$cleanPath} -> S3 ({$s3Path})");

                if (!$isDryRun) {
                    try {
                        $content = File::get($localPath);
                        \Illuminate\Support\Facades\Storage::disk(S3StorageService::disk())->put($s3Path, $content);
                        $this->info("  ✓ Uploaded to S3!");
                        $uploadedFiles++;
                    } catch (\Exception $e) {
                        $this->error("  ✗ Failed: " . $e->getMessage());
                        $failedFiles++;
                    }
                } else {
                    $uploadedFiles++;
                }
            }
        }

        $this->newLine();
        $this->info("==========================================");
        $this->info(" Migration Completed Summary:");
        $this->info(" Successfully Uploaded to S3 : {$uploadedFiles}");
        if ($failedFiles > 0) {
            $this->error(" Failed Uploads              : {$failedFiles}");
        }
        $this->info("==========================================");

        return Command::SUCCESS;
    }
}
