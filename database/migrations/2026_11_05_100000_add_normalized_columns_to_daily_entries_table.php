<?php

use App\Support\Eggs;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds integer egg columns, a real entry date and a direct flock_id to
 * daily_entries, then backfills them from the existing data.
 *
 * Additive and non-destructive: the legacy text columns (daily_egg_production,
 * daily_sold_egg, outstanding_egg, ...) are left in place so nothing that still
 * reads them breaks. Drop them in a later migration once nothing uses them.
 *
 * Backfill rules
 *  - eggs_produced / eggs_sold / eggs_outstanding: parsed from the legacy text
 *  - flock_id: via week_entries.flock_id
 *  - entry_date: DATE(created_at), i.e. exactly what the dashboard filtered on
 *    until now, so existing reports do not change. Edit individual rows later
 *    if an entry was keyed in on a different day than it describes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('flock_id')->nullable()->after('week_entry_id');
            $table->date('entry_date')->nullable()->after('day_number');
            $table->unsignedInteger('eggs_produced')->default(0)->after('daily_egg_production');
            $table->unsignedInteger('eggs_sold')->default(0)->after('eggs_produced');
            // Signed: outstanding can legitimately go negative if sales are keyed ahead of production.
            $table->integer('eggs_outstanding')->default(0)->after('eggs_sold');
        });

        $this->backfill();

        Schema::table('daily_entries', function (Blueprint $table) {
            $table->foreign('flock_id')->references('id')->on('flocks')->cascadeOnDelete();
            $table->index(['flock_id', 'entry_date'], 'daily_entries_flock_date_index');
            $table->index('entry_date', 'daily_entries_entry_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->dropForeign(['flock_id']);
            $table->dropIndex('daily_entries_flock_date_index');
            $table->dropIndex('daily_entries_entry_date_index');
        });

        Schema::table('daily_entries', function (Blueprint $table) {
            $table->dropColumn(['flock_id', 'entry_date', 'eggs_produced', 'eggs_sold', 'eggs_outstanding']);
        });
    }

    private function backfill(): void
    {
        DB::table('daily_entries')
            ->join('week_entries', 'week_entries.id', '=', 'daily_entries.week_entry_id')
            ->select([
                'daily_entries.id',
                'daily_entries.created_at',
                'daily_entries.daily_egg_production',
                'daily_entries.daily_sold_egg',
                'daily_entries.outstanding_egg',
                'week_entries.flock_id as week_flock_id',
            ])
            ->orderBy('daily_entries.id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('daily_entries')->where('id', $row->id)->update([
                        'flock_id' => $row->week_flock_id,
                        'entry_date' => $row->created_at ? substr((string) $row->created_at, 0, 10) : null,
                        'eggs_produced' => Eggs::parse($row->daily_egg_production),
                        'eggs_sold' => Eggs::parse($row->daily_sold_egg),
                        'eggs_outstanding' => Eggs::parse($row->outstanding_egg),
                    ]);
                }
            });
    }
};
