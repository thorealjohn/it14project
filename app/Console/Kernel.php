<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SendFollowUpReminders;
use App\Console\Commands\SendLateDeliveryAlerts;
use App\Console\Commands\SendPromoNotifications;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendLateDeliveryAlerts::class,
        SendFollowUpReminders::class,
        SendPromoNotifications::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('notifications:late-deliveries')->everyFifteenMinutes();
        $schedule->command('notifications:follow-ups')->dailyAt('09:00');
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
