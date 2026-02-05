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
        Schema::table('orders', function (Blueprint $blueprint) {
            $blueprint->dropColumn([
                'first_name',
                'last_name',
                'phone_number',
                'vat_number',
                'invoice_path',
            ]);
        });

        Schema::table('machinery', function (Blueprint $blueprint) {
            $blueprint->dropColumn('contract_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $blueprint) {
            $blueprint->string('first_name')->nullable();
            $blueprint->string('last_name')->nullable();
            $blueprint->string('phone_number')->nullable();
            $blueprint->string('vat_number')->nullable();
            $blueprint->string('invoice_path')->nullable();
        });

        Schema::table('machinery', function (Blueprint $blueprint) {
            $blueprint->string('contract_path')->nullable();
        });
    }
};
