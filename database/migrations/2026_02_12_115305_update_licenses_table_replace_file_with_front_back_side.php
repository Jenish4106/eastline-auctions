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
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn('file');
            $table->string('front_side')->after('user_id')->nullable();
            $table->string('back_side')->after('front_side')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('file')->after('user_id');
            $table->dropColumn(['front_side', 'back_side']);
        });
    }
};
