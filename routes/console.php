<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('a5:heartbeat', function (): void {
    $this->info('A5 Rifas OK - '.now()->toIso8601String());
})->purpose('Confirma que a aplicação e o scheduler conseguem inicializar.');

Schedule::command('a5:heartbeat')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('raffles:expire-reservations')->everyMinute()->withoutOverlapping();
Schedule::command('payments:reconcile --limit=100')->everyMinute()->withoutOverlapping();
