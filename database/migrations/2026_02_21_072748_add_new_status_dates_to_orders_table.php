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
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('sales_agreement_date')->nullable()->after('purchase_date');
            $table->timestamp('awaiting_invoice_date')->nullable()->after('sales_agreement_date');
            $table->timestamp('settle_payment_date')->nullable()->after('awaiting_invoice_date');
            $table->timestamp('confirmation_date')->nullable()->after('settle_payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'sales_agreement_date',
                'awaiting_invoice_date',
                'settle_payment_date',
                'confirmation_date'
            ]);
        });
    }
};
