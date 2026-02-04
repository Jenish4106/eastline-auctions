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
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('vat_number')->nullable();
            
            // Billing
            $table->string('billing_company')->nullable();
            $table->string('billing_street')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_country')->nullable();
            
            // Shipping
            $table->boolean('shipping_same_as_billing')->default(true);
            $table->string('shipping_street')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_zip')->nullable();
            $table->string('shipping_country')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'phone_number', 'vat_number',
                'billing_company', 'billing_street', 'billing_city', 'billing_state', 'billing_zip', 'billing_country',
                'shipping_same_as_billing', 'shipping_street', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'
            ]);
        });
    }
};
