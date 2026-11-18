<?php

use App\Services\FlockLedger;
use Illuminate\Database\Migrations\Migration;

/**
 * Recalculate every flock's running totals with the corrected flock-wide ledger. Before this, birds alive,
 * total deaths, feed to date and eggs in farm were summed per week only. Daily inputs are not touched.
 * Run again any time with: php artisan poultry:rebuild
 */
return new class extends Migration
{
    public function up(): void
    {
        FlockLedger::rebuildAll();
    }

    public function down(): void
    {
        // Nothing to undo: only derived columns were recalculated.
    }
};
