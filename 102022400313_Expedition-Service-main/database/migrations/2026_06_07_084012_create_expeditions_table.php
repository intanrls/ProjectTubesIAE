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
    Schema::create('expeditions', function (Blueprint $table) {
        $table->id();
        $table->string('order_id');
        $table->string('customer_name');
        $table->text('customer_address');
        $table->string('courier_name');
        $table->string('tracking_number')->unique();
        $table->string('shipping_status')->default('processing');
        $table->timestamp('shipped_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->string('receipt_number')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expeditions');
    }
};
