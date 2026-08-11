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

        $directoriesToMigrate = [
            public_path('uploads/category') => 'category',
            public_path('settings') => 'settings',
        ];

        $totalFiles = 0;
        $uploadedFiles = 0;
        $skippedFiles = 0;
        $failedFiles = 0;

        foreach ($directoriesToMigrate as $localBasePath => $s3Prefix) {
            if (!File::exists($localBasePath)) {
                $this->info("Directory not found, skipping: {$localBasePath}");
                continue;
            }

            $files = File::allFiles($localBasePath);

            foreach ($files as $file) {
                $totalFiles++;
                $relativePath = $file->getRelativePathname();
                
                // Construct S3 relative path
                $s3Path = $s3Prefix ? trim($s3Prefix, '/') . '/' . str_replace('\\', '/', $relativePath) : str_replace('\\', '/', $relativePath);

                $this->line("Processing [{$totalFiles}]: {$s3Path}");

                if (S3StorageService::exists($s3Path)) {
                    $this->info("  ➜ Already exists on S3. Skipping.");
                    $skippedFiles++;
                    continue;
                }

                if ($isDryRun) {
                    $this->question("  ➜ [DRY RUN] Would upload to S3: {$s3Path}");
                    $uploadedFiles++;
                    continue;
                }

                try {
                    $fileContent = File::get($file->getRealPath());
                    
                    // Direct S3 Upload via S3StorageService
                    S3StorageService::disk()->put($s3Path, $fileContent);

                    $this->info("  ✓ Successfully uploaded to S3!");
                    $uploadedFiles++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to upload: " . $e->getMessage());
                    $failedFiles++;
                }
            }
        }

        $this->newLine();
        $this->info("==========================================");
        $this->info(" Migration Completed Summary:");
        $this->info(" Total Files Processed : {$totalFiles}");
        $this->info(" Successfully Uploaded : {$uploadedFiles}");
        $this->info(" Skipped (Already S3)  : {$skippedFiles}");
        if ($failedFiles > 0) {
            $this->error(" Failed Uploads        : {$failedFiles}");
        }
        $this->info("==========================================");

        return Command::SUCCESS;
    }
}
