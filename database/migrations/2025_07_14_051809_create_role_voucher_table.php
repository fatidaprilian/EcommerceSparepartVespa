<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_voucher', function (Blueprint $table) {
            // Tidak perlu id(), cukup foreign key
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained()->onDelete('cascade');

            // Pastikan kombinasi role dan voucher unik
            $table->primary(['role_id', 'voucher_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_voucher');
    }
};
