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
            ->emailOutputTo(config('app.odoowoo_admin_email'));
        }

        if (config('app.odoowoo_sync_variable')) {
            $schedule->command('woo:sync-woo-product-variables')
            ->hourlyAt(30)
            ->withoutOverlapping(60)
            ->runInBackground()
            ->emailOutputTo(config('app.odoowoo_admin_email'));
        }

        if (config('app.odoowoo_pos_sms')) {
            $schedule->command('odoo:pos-daily-report --recipients='.config('app.odoowoo_pos_sms_recipients').' --date='.date("Y-m-d"))
            ->dailyAt(config('app.odoowoo_pos_sms_time'))
            ->runInBackground()
            ->emailOutputOnFailure(config('app.odoowoo_admin_email'));
        }

        if (config('app.odoowoo_customer_sms')) {
            $schedule->command('odoo:customer-sms-daily --date='.date('Y-m-d', strtotime('-1 day')))
            ->dailyAt(config('app.odoowoo_customer_sms_time'))
            ->runInBackground()
            ->emailOutputOnFailure(config('app.odoowoo_admin_email'));
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
