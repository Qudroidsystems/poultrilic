<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns the flock ledger needs.
 *
 * flocks
 *  - flock_type: layer | broiler | pullet | cockerel | other (drives which KPIs matter)
 *  - house: pen / house name
 *  - opening_egg_stock / opening_feed_stock: stock on hand when records for the flock began
 *  - merged_into_id / closed_on: when flocks are combined (e.g. 650 + 770 -> 1,420) the source flocks are
 *    closed on the merge date and point at the flock they went into
 *  - notes
 *
 * daily_entries (raw inputs, as reported)
 *  - birds_stolen, birds_culled (culled, sold live, moved out), bad_eggs
 *  - reported_birds_alive / reported_total_deaths: what the farm wrote down, kept to flag typing mistakes
 *  - egg_stock: eggs in the farm at the end of the day (computed by the ledger)
 *  - avg_weight_g: optional sample body weight (broilers / pullets)
 *  - notes, source (web | import | mobile | api), reported_at (when the farm actually sent it)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flocks', function (Blueprint $table) {
            if (! Schema::hasColumn('flocks', 'flock_type')) {
                $table->string('flock_type', 20)->default('layer')->after('breed');
            }
            if (! Schema::hasColumn('flocks', 'house')) {
                $table->string('house')->nullable()->after('flock_type');
            }
            if (! Schema::hasColumn('flocks', 'opening_egg_stock')) {
                $table->unsignedInteger('opening_egg_stock')->default(0)->after('current_bird_count');
            }
            if (! Schema::hasColumn('flocks', 'opening_feed_stock')) {
                $table->decimal('opening_feed_stock', 10, 2)->default(0)->after('opening_egg_stock');
            }
            if (! Schema::hasColumn('flocks', 'merged_into_id')) {
                $table->unsignedBigInteger('merged_into_id')->nullable()->after('status');
            }
            if (! Schema::hasColumn('flocks', 'closed_on')) {
                $table->date('closed_on')->nullable()->after('merged_into_id');
            }
            if (! Schema::hasColumn('flocks', 'notes')) {
                $table->text('notes')->nullable()->after('closed_on');
            }
        });

        Schema::table('daily_entries', function (Blueprint $table) {
            $table->unsignedInteger('birds_stolen')->default(0)->after('daily_mortality');
            $table->unsignedInteger('birds_culled')->default(0)->after('birds_stolen');
            $table->unsignedInteger('bad_eggs')->default(0)->after('broken_egg');
            $table->integer('egg_stock')->default(0)->after('eggs_outstanding');
            $table->unsignedInteger('reported_birds_alive')->nullable()->after('current_birds');
            $table->unsignedInteger('reported_total_deaths')->nullable()->after('reported_birds_alive');
            $table->decimal('avg_weight_g', 8, 1)->nullable()->after('reported_total_deaths');
            $table->text('notes')->nullable()->after('drugs');
            $table->string('source', 10)->default('web')->after('notes');
            $table->timestamp('reported_at')->nullable()->after('source');
        });

        // Running feed total in bags; was an integer, so half bags were lost.
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->decimal('total_feeds_consumed', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('daily_entries', function (Blueprint $table) {
            $table->dropColumn([
                'birds_stolen', 'birds_culled', 'bad_eggs', 'egg_stock', 'reported_birds_alive',
                'reported_total_deaths', 'avg_weight_g', 'notes', 'source', 'reported_at',
            ]);
        });

        Schema::table('flocks', function (Blueprint $table) {
            $table->dropColumn(['flock_type', 'house', 'opening_egg_stock', 'opening_feed_stock', 'merged_into_id', 'closed_on', 'notes']);
        });
    }
};
