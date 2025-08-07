<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_product_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_product_discounts');
    }
};
