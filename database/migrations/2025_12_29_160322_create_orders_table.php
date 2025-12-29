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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->foreignId('machinery_id')->constrained('machinery')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->date('purchase_date');
            
            $table->tinyInteger('delivery_status')->default(0)->comment('0 = Process, 1 = Shipped, 2 = In Transit, 3 = Delivered, 4 = Cancelled');
            
            $table->timestamp('process_date')->nullable();
            $table->timestamp('shipped_date')->nullable();
            $table->timestamp('in_transit_date')->nullable();
            $table->timestamp('delivered_date')->nullable();
            $table->timestamp('cancelled_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
