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
        Schema::table('users', function (Blueprint $table) {
            // Kolom untuk menyimpan kode verifikasi (OTP)
            $table->string('verification_code')->nullable()->after('password');
            // Kolom untuk waktu kedaluwarsa kode
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_code', 'verification_code_expires_at']);
        });
    }
};
