<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename the table from machinery_images to machinery_files
        Schema::rename('machinery_images', 'machinery_files');
        
        // Add type column to distinguish between images and videos
        Schema::table('machinery_files', function (Blueprint $table) {
            $table->string('type')->default('image')->after('image_path');
            
            // Create index for better query performance
            $table->index(['machinery_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the type column
        Schema::table('machinery_files', function (Blueprint $table) {
            $table->dropIndex(['machinery_id', 'type']);
            $table->dropColumn('type');
        });
        
        // Rename the table back from machinery_files to machinery_images
        Schema::rename('machinery_files', 'machinery_images');
    }
};