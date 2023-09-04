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
        if (config('app.odoowoo_sync_simple')) {
            $schedule->command('woo:sync')
            ->hourly()
            ->withoutOverlapping(60)
            ->runInBackground()
            ->emailOutputTo(env('MAIL_NOTIFICATIONS',''));
        }

        if (config('app.odoowoo_sync_variable')) {
            $schedule->command('woo:sync-woo-product-variables')
            ->hourlyAt(30)
            ->withoutOverlapping(60)
            ->runInBackground()
            ->emailOutputTo(env('MAIL_NOTIFICATIONS',''));
        }

        if (config('app.odoowoo_pos_sms')) {
            $schedule->command('odoo:pos-daily-report --recipients='.config('app.odoowoo_pos_sms_recipients').' --date='.date("Y-m-d"))
            ->dailyAt(config('app.odoowoo_pos_sms_time'))
            ->runInBackground()
            ->emailOutputTo(env('MAIL_NOTIFICATIONS',''));
        }
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
