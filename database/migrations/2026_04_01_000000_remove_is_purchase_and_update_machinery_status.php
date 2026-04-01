<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE machinery MODIFY status TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Draft, 1: Publish, 2: Sold'");

        if (Schema::hasColumn('machinery', 'is_purchase')) {
            Schema::table('machinery', function (Blueprint $table) {
                $table->dropColumn('is_purchase');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('machinery', 'is_purchase')) {
            Schema::table('machinery', function (Blueprint $table) {
                $table->boolean('is_purchase')->default(false)->after('bid_won_date');
            });
        }

        DB::statement("ALTER TABLE machinery MODIFY status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Active, 2: Sold, 3: Closed'");
    }
};
