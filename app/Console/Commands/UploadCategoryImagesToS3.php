<?php

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class UploadCategoryImagesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'category:upload-to-s3 {--id= : Upload a specific category ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload category images from server storage to S3/Cloudflare R2 and store full URLs in DB.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting category image migration to S3/Cloudflare R2...');

        $query = Category::query();

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        }

        $categories = $query->get();

        if ($categories->isEmpty()) {
            $this->info('No categories found to process.');
            return 0;
        }

        $this->info("Found {$categories->count()} category/categories to process.");

        $successCount = 0;
        $failCount = 0;

        foreach ($categories as $category) {
            if (empty($category->image)) {
                continue;
            }

            $images = json_decode($category->image, true);
            if (!is_array($images)) {
                $images = [$category->image];
            }

            $updatedUrls = [];
            $categoryChanged = false;

            foreach ($images as $imgItem) {
                if (empty($imgItem)) {
                    continue;
                }

                // If already a full URL, keep it
                if (str_starts_with($imgItem, 'http://') || str_starts_with($imgItem, 'https://')) {
                    $updatedUrls[] = $imgItem;
                    continue;
                }

                $filename = basename(ltrim($imgItem, '/'));
                $localFilePath = public_path("uploads/category/images/{$filename}");

                if (!File::exists($localFilePath)) {
                    $this->warn("[Category ID {$category->id}] Server file not found at: {$localFilePath}");
                    $updatedUrls[] = $imgItem;
                    $failCount++;
                    continue;
                }

                try {
                    $s3Directory = 'uploads/category/images';
                    $s3Path = "{$s3Directory}/{$filename}";

                    // Upload to S3/R2 with public visibility
                    Storage::disk('s3')->putFileAs($s3Directory, new \Illuminate\Http\File($localFilePath), $filename, 'public');

                    $fullUrl = Storage::disk('s3')->url($s3Path);
                    $updatedUrls[] = $fullUrl;
                    $categoryChanged = true;
                    $this->info("[Category ID {$category->id}] Uploaded image -> {$fullUrl}");
                } catch (\Throwable $e) {
                    $this->error("[Category ID {$category->id}] Error uploading {$filename}: " . $e->getMessage());
                    $updatedUrls[] = $imgItem;
                    $failCount++;
                }
            }

            if ($categoryChanged || json_encode($updatedUrls) !== $category->image) {
                $category->image = json_encode(array_values(array_unique($updatedUrls)));
                $category->save();
                $successCount++;
                $this->info("[Category ID {$category->id}] Category updated with full S3 URLs.");
            }
        }

        $this->info("Processing complete! {$successCount} categories updated, {$failCount} file errors.");
        return 0;
    }
}
