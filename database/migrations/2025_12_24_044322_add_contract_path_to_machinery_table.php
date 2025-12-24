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
            $table->string('contract_path')->nullable()->after('won_user');
            $table->tinyInteger('contract_status')->nullable()->after('contract_path')->comment('0: Pending, 1: Approved, 3: Signed, 4: Rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machinery', function (Blueprint $table) {
            if (Schema::hasColumn('machinery', 'contract_path')) {
                $table->dropColumn('contract_path');
            }
            
            if (Schema::hasColumn('machinery', 'contract_status')) {
                $table->dropColumn('contract_status');
            }
        });
    }
};
