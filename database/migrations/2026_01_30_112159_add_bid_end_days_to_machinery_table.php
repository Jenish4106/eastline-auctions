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
            $table->integer('bid_end_days')->default(7)->after('bid_start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machinery', function (Blueprint $table) {
            $table->dropColumn('bid_end_days');
        });
    }
};
