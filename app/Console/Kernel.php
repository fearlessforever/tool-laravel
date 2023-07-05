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
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        // $this->load( base_path('fearlessforever/Commands') );
        
        require base_path('routes/console.php');
    }

    protected $commands = [
        \Fearless\Commands\DB_Import::class,
        \Fearless\Commands\DB_Export::class,
        // \Fearless\Commands\DB_Import::class,
    ];
}
