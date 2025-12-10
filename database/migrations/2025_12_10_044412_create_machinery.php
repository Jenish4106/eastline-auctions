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
            $table->string('name');
            $table->integer('category_id');
            $table->string('year');
            $table->string('weight');
            $table->string('fuel_type');
            $table->string('buy_now_price');
            $table->string('bid_start_price');
            $table->string('bid_end_time');
            $table->text('description');
            $table->string('images');
            $table->boolean('status')->default(1)->comment('1: active, 2: Sold, 3: Closed');
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
