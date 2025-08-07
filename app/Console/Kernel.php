<?php

namespace App\Console;

// Pastikan command di-import di sini
use App\Console\Commands\CancelUnpaidOrders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daftarkan command untuk berjalan setiap menit untuk kemudahan testing di lokal.
        // Di production, Anda bisa mengubahnya menjadi ->hourly() atau ->daily().
        $schedule->command(CancelUnpaidOrders::class)->everyMinute();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
