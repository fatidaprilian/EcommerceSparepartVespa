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
        Schema::table('orders', function (Blueprint $table) {
            // Existing columns (keep these)
            $table->string('payment_method')->nullable()->after('payment_status')
                ->comment('xendit, manual_transfer, etc');
            $table->string('payment_gateway')->nullable()->after('payment_method')
                ->comment('For gateway name if using third-party');
            $table->string('payment_invoice_id')->nullable()->after('payment_gateway')
                ->comment('Invoice ID from payment gateway');
            $table->text('payment_url')->nullable()->after('payment_invoice_id')
                ->comment('Payment URL for redirect');

            // New additional columns (only add if not exists)
            if (!Schema::hasColumn('orders', 'payment_metadata')) {
                $table->json('payment_metadata')->nullable()->after('payment_url')
                    ->comment('Raw payment response data');
            }
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_metadata')
                    ->comment('When payment was completed');
            }
            if (!Schema::hasColumn('orders', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('paid_at')
                    ->comment('For manual transfer proof image path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Only remove the columns we added in this migration
            $columnsToDrop = [];

            if (Schema::hasColumn('orders', 'payment_metadata')) {
                $columnsToDrop[] = 'payment_metadata';
            }
            if (Schema::hasColumn('orders', 'paid_at')) {
                $columnsToDrop[] = 'paid_at';
            }
            if (Schema::hasColumn('orders', 'payment_proof')) {
                $columnsToDrop[] = 'payment_proof';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Note: We don't drop the original columns (payment_method, etc)
            // to maintain backward compatibility
        });
    }
};
