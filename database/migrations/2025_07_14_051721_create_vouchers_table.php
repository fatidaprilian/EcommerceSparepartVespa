<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->text('description')->nullable();

            // Tipe diskon: 'fixed' (potongan harga tetap) atau 'percentage' (potongan persen)
            $table->enum('type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('value', 15, 2); // Nilai diskon (rupiah atau persen)

            $table->unsignedBigInteger('min_purchase')->default(0)->comment('Minimum pembelian untuk bisa menggunakan voucher');

            $table->unsignedInteger('max_uses')->nullable()->comment('Berapa kali voucher bisa digunakan secara total');
            $table->unsignedInteger('uses')->default(0)->comment('Sudah berapa kali digunakan');

            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
