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
            $table->string('bid_start_time')->after('bid_start_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machinery', function (Blueprint $table) {
            $table->dropColumn('bid_start_time');
        });
    }
};
