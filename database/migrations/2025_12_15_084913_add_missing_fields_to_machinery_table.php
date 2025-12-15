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
        Schema::table('machinery', function (Blueprint $table) {
            $table->string('make')->after('category_id');
            $table->string('model')->after('make');
            $table->string('serial_number')->nullable()->after('fuel');
            $table->string('video_path')->nullable()->after('offer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machinery', function (Blueprint $table) {
            $table->dropColumn(['make', 'model', 'serial_number', 'video_path']);
        });
    }
};