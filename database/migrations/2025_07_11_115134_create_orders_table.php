<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // Mereferensikan alamat yang dipilih saat checkout
            $table->foreignId('user_address_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('total_amount');
            $table->unsignedBigInteger('shipping_cost')->default(0);
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->string('tracking_number')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
