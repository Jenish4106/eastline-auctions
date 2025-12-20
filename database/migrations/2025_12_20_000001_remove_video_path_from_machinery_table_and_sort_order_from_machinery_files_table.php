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
        // Remove video_path column from machinery table
        Schema::table('machinery', function (Blueprint $table) {
            $table->dropColumn('video_path');
        });
        
        // Remove sort_order column from machinery_files table
        Schema::table('machinery_files', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add video_path column back to machinery table
        Schema::table('machinery', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('offer');
        });
        
        // Add sort_order column back to machinery_files table
        Schema::table('machinery_files', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('type');
        });
    }
};