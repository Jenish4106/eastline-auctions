<?php

namespace App\Console\Commands;

use App\Models\MachineryFileManager;
use App\Services\S3StorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MigrateMachineryFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-machinery-to-s3 {--dry-run : Run without actually uploading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate machinery images and videos to S3 with default image fallback for missing local files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('--- DRY RUN MODE (No files will be uploaded) ---');
        }

        $this->info('=== Starting Machinery Files (Images & Videos) Migration to S3 ===');

        $uploadedFiles = 0;
        $missingCount = 0;
        $failedFiles = 0;

        $machineryFiles = MachineryFileManager::whereIn('type', ['image', 'video'])->get();
        $this->info('Found ' . $machineryFiles->count() . ' machinery files (images/videos) in database.');

        foreach ($machineryFiles as $fileRecord) {
            if (!$fileRecord->image_path)
                continue;

            $rawFilename = ltrim($fileRecord->image_path, '/');
            $cleanFilename = basename(parse_url($rawFilename, PHP_URL_PATH));

            if ($fileRecord->type === 'video') {
                $localPath = public_path('uploads/machinery/videos/' . $cleanFilename);
                $s3Path = 'uploads/machinery/videos/' . $cleanFilename;
            } else {
                $localPath = public_path('uploads/machinery/images/' . $cleanFilename);
                $s3Path = 'uploads/machinery/images/' . $cleanFilename;
            }

            if (File::exists($localPath)) {
                $this->line("Uploading Machinery {$fileRecord->type}: {$cleanFilename} -> S3 ({$s3Path})");

                if (!$isDryRun) {
                    try {
                        $content = File::get($localPath);
                        Storage::disk(S3StorageService::disk())->put($s3Path, $content);
                        $this->info('  ✓ Uploaded to S3!');
                        $uploadedFiles++;
                    } catch (\Exception $e) {
                        $this->error('  ✗ Failed: ' . $e->getMessage());
                        $failedFiles++;
                    }
                } else {
                    $uploadedFiles++;
                }
            } else {
                $this->warn("Local file missing for Machinery File [ID {$fileRecord->id}]: {$cleanFilename}. Uploading default fallback image/video.");
                $missingCount++;

                if (!$isDryRun) {
                    try {
                        if ($fileRecord->type === 'video') {
                            $defaultLocal = public_path('uploads/defaults/default-machine.mp4');
                            if (File::exists($defaultLocal)) {
                                Storage::disk(S3StorageService::disk())->put($s3Path, File::get($defaultLocal));
                                $this->info("  ✓ Default video uploaded to S3 path: {$s3Path}");
                            }
                        } else {
                            $defaultLocal = public_path('uploads/defaults/default.png');
                            if (File::exists($defaultLocal)) {
                                Storage::disk(S3StorageService::disk())->put($s3Path, File::get($defaultLocal));
                                $this->info("  ✓ Default image uploaded to S3 path: {$s3Path}");
                            }
                        }
                        $uploadedFiles++;
                    } catch (\Exception $e) {
                        $this->error('  ✗ Failed default upload: ' . $e->getMessage());
                        $failedFiles++;
                    }
                }
            }
        }

        $this->newLine();
        $this->info('==========================================');
        $this->info(' Migration Completed Summary:');
        $this->info(" Successfully Uploaded to S3 : {$uploadedFiles}");
        if ($missingCount > 0) {
            $this->warn(" Missing Local Files Handled : {$missingCount} (Set with Default Fallback)");
        }
        if ($failedFiles > 0) {
            $this->error(" Failed Uploads              : {$failedFiles}");
        }
        $this->info('==========================================');

        return Command::SUCCESS;
    }
}
