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
        Schema::create('machinery', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->string('make');
            $table->string('model');
            $table->string('year');
            $table->string('weight');
            $table->string('working_hours');
            $table->string('condition');
            $table->string('fuel');
            $table->string('serial_number')->nullable();
            $table->decimal('buy_now_price', 10, 2);
            $table->decimal('bid_start_price', 10, 2);
            $table->string('bid_end_time');
            $table->text('description');
            $table->json('specification');
            $table->string('offer')->nullable();
            $table->string('video_path')->nullable();
            $table->boolean('status')->default(1)
                ->comment('1: Active, 2: Sold, 3: Closed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machinery');
    }
};