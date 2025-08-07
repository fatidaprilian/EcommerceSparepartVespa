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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->comment('Stock Keeping Unit from ERP');
            $table->longText('description')->nullable();
            $table->unsignedBigInteger('base_price')->comment('Harga dalam satuan mata uang terkecil (misal: sen/rupiah)');
            $table->integer('stock')->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);

            // Kolom untuk Integrasi ERP
            $table->string('erp_id')->unique()->nullable()->comment('ID unik dari sistem ERP');
            $table->timestamp('last_synced_at')->nullable()->comment('Waktu sinkronisasi terakhir dengan ERP');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
