<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('woo:sync')->hourly()->withoutOverlapping(30)->runInBackground()->emailOutputTo(env('MAIL_NOTIFICATIONS',''));
        $schedule->command('woo:sync-woo-product-variables')->hourlyAt(20)->withoutOverlapping(30)->runInBackground()->emailOutputTo(env('MAIL_NOTIFICATIONS',''));;
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
