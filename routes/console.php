<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('poultry:rebuild {flock? : Flock id (all flocks when omitted)}', function (?string $flock = null) {
    if ($flock) {
        \App\Services\FlockLedger::rebuild((int) $flock);
        $this->info("Flock {$flock} recalculated.");

        return;
    }
    $n = \App\Services\FlockLedger::rebuildAll();
    $this->info("{$n} flock(s) recalculated.");
})->purpose('Recalculate birds alive, deaths, feed and egg stock to date from the daily records');
