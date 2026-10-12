<?php

use Illuminate\Support\Facades\Schedule;

// Dijalankan oleh "php artisan schedule:work" (lokal) atau cron "schedule:run" (server).
// Perintah memeriksa sendiri interval di menu Integrasi SIMRS.
Schedule::command('simrs:sync')->everyMinute()->withoutOverlapping();
