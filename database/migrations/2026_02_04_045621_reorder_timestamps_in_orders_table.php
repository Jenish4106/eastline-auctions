<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Using raw SQL because Schema builder doesn't support 'MODIFY COLUMN ... AFTER ...' easily across all drivers without doctrine/dbal,
        // and we want to be explicit about moving them to the end.
        // Assuming MySQL/MariaDB as it's XAMPP.
        
        DB::statement("ALTER TABLE orders MODIFY COLUMN created_at TIMESTAMP NULL DEFAULT NULL AFTER shipping_country");
        DB::statement("ALTER TABLE orders MODIFY COLUMN updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We can't easily know where they were before without querying schema metadata or hardcoding.
        // Reverting this is just moving them back to *somewhere*, but 'down' is rarely strictly about column order.
        // We will leave them at the end or move them back to after 'cancelled_date' if we want to be precise, 
        // but 'cancelled_date' was where invoice_path was added after.
        // Let's just move them after 'cancelled_date' (which is where they effectively were relative to the original schema + billing fields depending on insert order).
        // Actually, before this migration, they were likely in the middle.
        // Let's just create a no-op down or move them to a 'standard' location like after id.
        // Staying safe: no-op for down regarding order, as column order doesn't affect functionality usually.
    }
};
