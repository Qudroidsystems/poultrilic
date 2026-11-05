<?php

use App\Exports\PoultryAnalyticsExport;
use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\User;
use App\Models\WeekEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function makeFlockWithWeek(array $flock = []): array
{
    $flock = Flock::create(array_merge([
        'name' => 'Flock A', 'breed' => 'Layers', 'initial_bird_count' => 1000, 'status' => 'active',
    ], $flock));
    $week = WeekEntry::create(['flock_id' => $flock->id, 'week_name' => 'Week 1']);

    return [$flock, $week];
}

function entryPayload(array $overrides = []): array
{
    return array_merge([
        'day_number' => 1,
        'daily_feeds' => 2.5,
        'available_feeds' => 10,
        'daily_mortality' => 2,
        'sick_bay' => 0,
        'daily_egg_production_crates' => 11,
        'daily_egg_production_pieces' => 12,
        'daily_sold_egg_crates' => 10,
        'daily_sold_egg_pieces' => 0,
        'broken_egg_crates' => 0,
        'broken_egg_pieces' => 5,
        'drugs' => 'Nil',
        'reorder_feeds' => 1,
    ], $overrides);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('stores integer egg counts, the entry date and flock_id (and still writes legacy text)', function () {
    [$flock, $week] = makeFlockWithWeek();

    $this->postJson("/daily-entries/{$week->id}", entryPayload(['entry_date' => '2026-10-01']))
        ->assertCreated();

    $entry = DailyEntry::firstOrFail();
    expect($entry->flock_id)->toBe($flock->id)
        ->and($entry->entry_date->toDateString())->toBe('2026-10-01')
        ->and($entry->eggs_produced)->toBe(342)
        ->and($entry->eggs_sold)->toBe(300)
        ->and($entry->broken_egg)->toBe(5)
        ->and($entry->eggs_outstanding)->toBe(37)
        ->and($entry->daily_egg_production)->toBe('11 Cr 12PC')
        ->and($entry->outstanding_egg)->toBe('1 Cr 7PC');

    expect($flock->fresh()->current_bird_count)->toBe(998);
});

it('defaults the entry date to today when none is sent', function () {
    [, $week] = makeFlockWithWeek();

    $this->postJson("/daily-entries/{$week->id}", entryPayload())->assertCreated();

    expect(DailyEntry::first()->entry_date->toDateString())->toBe(now()->toDateString());
});

it('keeps weekly running totals as sums of the integer columns', function () {
    [, $week] = makeFlockWithWeek();

    $this->postJson("/daily-entries/{$week->id}", entryPayload(['day_number' => 1, 'entry_date' => '2026-10-01']))->assertCreated();
    $this->postJson("/daily-entries/{$week->id}", entryPayload(['day_number' => 2, 'entry_date' => '2026-10-02']))->assertCreated();

    $second = DailyEntry::orderByDesc('id')->first();
    expect($second->total_sold_egg)->toBe('20 Cr 0PC')       // 300 + 300 pieces
        ->and($second->total_egg_in_farm)->toBe('2 Cr 14PC'); // 37 + 37 pieces
});

it('rejects a second entry for the same flock and date', function () {
    [, $week] = makeFlockWithWeek();

    $this->postJson("/daily-entries/{$week->id}", entryPayload(['day_number' => 1, 'entry_date' => '2026-10-01']))->assertCreated();
    $this->postJson("/daily-entries/{$week->id}", entryPayload(['day_number' => 2, 'entry_date' => '2026-10-01']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('entry_date');
});

it('rejects future dates', function () {
    [, $week] = makeFlockWithWeek();

    $this->postJson("/daily-entries/{$week->id}", entryPayload(['entry_date' => now()->addDay()->toDateString()]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('entry_date');
});

it('lets legacy rows that share a backfilled date be edited without changing the date', function () {
    [$flock, $week] = makeFlockWithWeek();

    $a = DailyEntry::create(['week_entry_id' => $week->id, 'day_number' => 1, 'entry_date' => '2026-10-01', 'daily_feeds' => 1, 'available_feeds' => 1,
        'total_feeds_consumed' => 1, 'daily_mortality' => 0, 'sick_bay' => 0, 'total_mortality' => 0, 'current_birds' => 1000,
        'daily_egg_production' => '1 Cr 0PC', 'daily_sold_egg' => '0 Cr 0PC', 'total_sold_egg' => '0 Cr 0PC', 'broken_egg' => 0,
        'outstanding_egg' => '1 Cr 0PC', 'total_egg_in_farm' => '1 Cr 0PC']);
    DailyEntry::create(array_merge($a->only(['week_entry_id', 'entry_date', 'daily_feeds', 'available_feeds', 'total_feeds_consumed', 'daily_mortality', 'sick_bay',
        'total_mortality', 'current_birds', 'daily_egg_production', 'daily_sold_egg', 'total_sold_egg', 'broken_egg', 'outstanding_egg', 'total_egg_in_farm']), ['day_number' => 2]));

    $this->putJson("/daily-entries/{$week->id}/{$a->id}", entryPayload(['day_number' => 1, 'entry_date' => '2026-10-01']))
        ->assertOk();

    // ...but moving it onto a date another entry already has is still refused.
    $other = DailyEntry::where('day_number', 2)->first();
    $other->update(['entry_date' => '2026-10-02']);
    $this->putJson("/daily-entries/{$week->id}/{$a->id}", entryPayload(['day_number' => 1, 'entry_date' => '2026-10-02']))
        ->assertStatus(422);
});

it('derives integer columns when only the legacy text is written', function () {
    [, $week] = makeFlockWithWeek();

    $entry = DailyEntry::create(['week_entry_id' => $week->id, 'day_number' => 1, 'daily_feeds' => 1, 'available_feeds' => 1,
        'total_feeds_consumed' => 1, 'daily_mortality' => 0, 'sick_bay' => 0, 'total_mortality' => 0, 'current_birds' => 1000,
        'daily_egg_production' => '5741 Cr 2PC', 'daily_sold_egg' => '1 Cr 0PC', 'total_sold_egg' => '1 Cr 0PC', 'broken_egg' => 0,
        'outstanding_egg' => '2 Cr 3PC', 'total_egg_in_farm' => '2 Cr 3PC']);

    expect($entry->fresh()->eggs_produced)->toBe(172232)
        ->and($entry->fresh()->eggs_sold)->toBe(30)
        ->and($entry->fresh()->eggs_outstanding)->toBe(63)
        ->and($entry->fresh()->flock_id)->not->toBeNull();
});

it('filters reports by the entry date, not by when the row was keyed in', function () {
    [$flock, $week] = makeFlockWithWeek();

    // Keyed in today, but describing a day in September.
    $this->postJson("/daily-entries/{$week->id}", entryPayload(['entry_date' => '2026-09-10']))->assertCreated();

    expect(DailyEntry::betweenDates('2026-09-01', '2026-09-30')->count())->toBe(1)
        ->and(DailyEntry::betweenDates(now()->startOfDay(), now()->endOfDay())->count())->toBe(0);

    $rows = (new PoultryAnalyticsExport(
        \Carbon\Carbon::parse('2026-09-01'), \Carbon\Carbon::parse('2026-09-30'), $flock->id
    ))->collection();

    expect($rows)->toHaveCount(1);
});

it('renders the dashboard for a user with the dashboard permission', function () {
    Permission::create(['name' => 'dashboard']);
    $user = User::factory()->create();
    $user->givePermissionTo('dashboard');
    $this->actingAs($user);

    [, $week] = makeFlockWithWeek();
    $this->postJson("/daily-entries/{$week->id}", entryPayload(['entry_date' => now()->subDay()->toDateString()]))->assertCreated();

    $this->get('/dashboard')->assertOk();
});
