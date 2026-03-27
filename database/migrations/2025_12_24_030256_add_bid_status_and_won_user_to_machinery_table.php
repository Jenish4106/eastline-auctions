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
            $table->tinyInteger('bid_status')->default(0)->after('bid_end_time')->comment('0: pending, 1: active, 2: completed, 3: cancelled');
            $table->unsignedBigInteger('won_user')->nullable()->after('bid_status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machinery', function (Blueprint $table) {
            $table->dropColumn(['bid_status', 'won_user']);
        });
    }
};
