<?php

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Models\ImportBatch;
use App\Models\LivestockRecord;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Models\ReportDefinition;
use App\Models\User;
use App\Services\DailyEntryRecorder;
use App\Services\FlockLedger;
use App\Services\FlockMerger;
use App\Services\Import\SheetTools;
use App\Services\PoultryMetrics;
use App\Support\LivestockTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

const PL_PERMS = [
    'View flock', 'Create flock', 'Update flock', 'Delete flock', 'Merge flock', 'dashboard',
    'View weekly-entry', 'Create weekly-entry', 'Create daily-entry', 'Update daily-entry', 'Delete daily-entry',
    'Import records', 'View reports', 'Manage reports',
    'View livestock', 'Create livestock record', 'Update livestock record', 'Delete livestock record',
    'Manage livestock units', 'Manage livestock types',
];

function plUser(array $perms = PL_PERMS): User
{
    $user = User::factory()->create();
    foreach ($perms as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $user->givePermissionTo($perms);

    return $user;
}

/** Record consecutive days for a flock: each item [deaths, eggs, feed, stolen?]. */
function plDays(Flock $flock, string $start, array $days): void
{
    $rec = app(DailyEntryRecorder::class);
    foreach ($days as $i => $d) {
        $rec->record($flock, [
            'entry_date' => \Carbon\Carbon::parse($start)->addDays($i)->toDateString(),
            'daily_mortality' => $d[0], 'eggs_produced' => $d[1], 'daily_feeds' => $d[2], 'birds_stolen' => $d[3] ?? 0,
            'eggs_sold' => $d[4] ?? 0, 'available_feeds' => 20,
        ]);
    }
}

function plXlsx(array $headers, array $rows): string
{
    $book = SheetTools::workbook('Daily records', array_map(fn ($h) => [$h, null], $headers), $rows);
    $path = tempnam(sys_get_temp_dir(), 'pl') . '.xlsx';
    (new Xlsx($book))->save($path);

    return $path;
}

// ---- Ledger --------------------------------------------------------------------------------------

it('keeps birds alive correct across weeks (the old code reset it each week)', function () {
    $flock = Flock::create(['name' => 'A', 'initial_bird_count' => 1000, 'status' => 'active', 'date_of_arrival' => '2026-01-01', 'age_in_weeks' => 18]);
    // 10 days: two weeks; 2 deaths a day, 1 bird stolen on day 9.
    plDays($flock, '2026-01-01', array_map(fn ($i) => [2, 800, 4.5, $i === 8 ? 1 : 0], range(0, 9)));

    $last = DailyEntry::forFlock($flock->id)->latestFirst()->first();
    expect($last->total_mortality)->toBe(20)
        ->and($last->current_birds)->toBe(1000 - 20 - 1)
        ->and($flock->fresh()->current_bird_count)->toBe(979)
        ->and((float) $last->total_feeds_consumed)->toBe(45.0)
        ->and($last->weekEntry->week_name)->toBe('Week 20');   // arrived at 18 weeks: days 8-10 are in week 20
});

it('recalculates later days when an earlier day is edited or deleted', function () {
    $flock = Flock::create(['name' => 'A', 'initial_bird_count' => 100, 'status' => 'active']);
    plDays($flock, '2026-02-01', [[1, 80, 1], [1, 80, 1], [1, 80, 1]]);
    $first = DailyEntry::forFlock($flock->id)->oldestFirst()->first();

    app(DailyEntryRecorder::class)->record($flock, ['entry_date' => '2026-02-01', 'daily_mortality' => 5, 'eggs_produced' => 80, 'daily_feeds' => 1], $first);
    expect($flock->fresh()->current_bird_count)->toBe(100 - 5 - 1 - 1);

    $first->fresh()->delete();
    expect($flock->fresh()->current_bird_count)->toBe(98)
        ->and(DailyEntry::forFlock($flock->id)->latestFirst()->first()->total_mortality)->toBe(2);
});

it('refuses losses greater than the birds alive', function () {
    $flock = Flock::create(['name' => 'A', 'initial_bird_count' => 5, 'status' => 'active']);
    app(DailyEntryRecorder::class)->record($flock, ['entry_date' => '2026-02-01', 'daily_mortality' => 6, 'daily_feeds' => 0]);
})->throws(\Illuminate\Validation\ValidationException::class);

it('tracks egg stock across days and flags a typed bird count that disagrees once', function () {
    $flock = Flock::create(['name' => 'A', 'initial_bird_count' => 100, 'status' => 'active', 'opening_egg_stock' => 50]);
    $rec = app(DailyEntryRecorder::class);
    $rec->record($flock, ['entry_date' => '2026-03-01', 'daily_mortality' => 0, 'eggs_produced' => 90, 'eggs_sold' => 60, 'broken_egg' => 2, 'bad_eggs' => 1, 'daily_feeds' => 2, 'reported_birds_alive' => 100]);
    $rec->record($flock, ['entry_date' => '2026-03-02', 'daily_mortality' => 1, 'eggs_produced' => 90, 'eggs_sold' => 120, 'daily_feeds' => 2, 'reported_birds_alive' => 97]);
    $rec->record($flock, ['entry_date' => '2026-03-03', 'daily_mortality' => 0, 'eggs_produced' => 90, 'eggs_sold' => 0, 'daily_feeds' => 2, 'reported_birds_alive' => 97]);

    $days = DailyEntry::forFlock($flock->id)->oldestFirst()->get();
    expect($days->pluck('egg_stock')->all())->toBe([50 + 27, 77 - 30, 47 + 90]);

    $issues = collect(FlockLedger::issues($flock));
    // 97 reported vs 99 computed on day 2 is flagged once, not again on day 3.
    expect($issues->filter(fn ($i) => str_contains($i['message'], 'reported 97'))->count())->toBe(1);
});

// ---- Metrics ------------------------------------------------------------------------------------

it('works out hen-day, mortality and eggs per bag from daily inputs', function () {
    $flock = Flock::create(['name' => 'A', 'flock_type' => 'layer', 'initial_bird_count' => 1000, 'status' => 'active']);
    plDays($flock, '2026-04-01', [[0, 800, 4], [10, 790, 4]]);

    $s = app(PoultryMetrics::class)->summary([$flock->id], '2026-04-01', '2026-04-02');
    // bird-days: 1000 + 990 = 1990; eggs 1590 -> 79.9 %
    expect($s['eggs_laid'])->toBe(1590)
        ->and($s['hen_day_pct'])->toBe(79.9)
        ->and($s['birds_start'])->toBe(1000)
        ->and($s['birds_end'])->toBe(990)
        ->and($s['mortality_pct'])->toBe(1.0)
        ->and($s['eggs_per_bag'])->toBe(198.8)
        ->and($s['feed_g_bird_day'])->toBe(round(8 * 50 * 1000 / 1990, 1));
});

it('counts merged birds once in farm totals', function () {
    $b = Flock::create(['name' => 'B', 'initial_bird_count' => 650, 'status' => 'active']);
    $c = Flock::create(['name' => 'C', 'initial_bird_count' => 770, 'status' => 'active']);
    plDays($b, '2026-05-01', [[2, 500, 2]]);
    plDays($c, '2026-05-01', [[3, 600, 3]]);

    $merged = app(FlockMerger::class)->merge([$b->id, $c->id], '2026-05-01', null, 'B+C');
    expect($merged->initial_bird_count)->toBe(648 + 767)
        ->and($b->fresh()->status)->toBe('ended')
        ->and($b->fresh()->merged_into_id)->toBe($merged->id);

    plDays($merged, '2026-05-02', [[5, 1100, 5]]);
    $s = app(PoultryMetrics::class)->summary(null, '2026-05-01', '2026-05-02');
    expect($s['birds_start'])->toBe(1420)
        ->and($s['birds_end'])->toBe(1415 - 5)
        ->and($s['deaths'])->toBe(10);
});

// ---- Import -------------------------------------------------------------------------------------

it('previews and imports a multi-flock sheet, creating missing flocks and deriving daily stolen birds', function () {
    $this->actingAs(plUser());
    $path = plXlsx(
        ['Flock', 'Report date (est.)', 'Week', 'Day', 'Deaths today', 'Total deaths (corrected)', 'Stolen (total)', 'Birds alive', 'Feed today (bags)', 'Feed in store (bags)', 'Eggs laid', 'Eggs sold', 'Broken eggs', 'Bad eggs', 'Eggs in farm', 'Medication'],
        [
            ['Layers A', '2026-06-01', 53, 1, 1, 36, 0, 963, 4.5, 30, 700, 600, 2, 1, 197, 'Nil'],
            ['Layers A', '2026-06-02', 53, 2, 0, 36, 2, 961, 4.5, 25.5, 710, 690, 3, 0, 214, 'Vitamins'],
            ['Chicks D', '2026-06-01', 1, 1, 20, 20, 0, 4430, 3, 18, 0, 0, 0, 0, 0, 'Glucose'],
        ],
    );

    $this->post(route('imports.preview'), ['kind' => 'poultry', 'create_missing' => 1, 'file' => new UploadedFile($path, 'farm.xlsx', null, null, true)])
        ->assertRedirect();
    $batch = ImportBatch::firstOrFail();
    $this->get(route('imports.show', $batch))->assertOk()->assertSee('Layers A')->assertSee('Create flock');

    $this->post(route('imports.commit', $batch))->assertRedirect(route('imports.index'));
    expect($batch->fresh()->status)->toBe('imported');

    $a = Flock::where('name', 'Layers A')->firstOrFail();
    $days = DailyEntry::forFlock($a->id)->oldestFirst()->get();
    expect($a->initial_bird_count)->toBe(964)
        ->and($a->age_in_weeks)->toBe(52)
        ->and($days[1]->birds_stolen)->toBe(2)
        ->and($a->fresh()->current_bird_count)->toBe(961)
        ->and($a->opening_egg_stock)->toBe(197 - (700 - 600 - 2 - 1))
        ->and($days[0]->drugs)->toBeNull()
        ->and($days[1]->drugs)->toBe('Vitamins')
        ->and($days[1]->source)->toBe('import');

    // Importing the same file again updates; it does not duplicate.
    $this->post(route('imports.preview'), ['kind' => 'poultry', 'create_missing' => 1, 'file' => new UploadedFile($path, 'farm.xlsx', null, null, true)]);
    $this->post(route('imports.commit', ImportBatch::latest('id')->first()));
    expect(DailyEntry::count())->toBe(3)->and(Flock::count())->toBe(2);
});

it('imports a single-flock log into a chosen flock and downloads templates', function () {
    $this->actingAs(plUser());
    $flock = Flock::create(['name' => 'Layers', 'initial_bird_count' => 500, 'status' => 'active']);
    $path = plXlsx(['Date', 'Deaths today', 'Feed used today (bags)', 'Feed remaining (bags)', 'Medication', 'Farm: birds alive', 'Notes'], [
        ['03/11/2026', 2, 3, 40, null, 498, 'ok'],
        ['04/11/2026', 1, 3, 37, 'Amprolium', 497, null],
    ]);
    $this->post(route('imports.preview'), ['kind' => 'poultry', 'flock_id' => $flock->id, 'file' => new UploadedFile($path, 'log.xlsx', null, null, true)]);
    $this->post(route('imports.commit', ImportBatch::firstOrFail()));

    expect(DailyEntry::forFlock($flock->id)->pluck('entry_date')->map->toDateString()->all())->toBe(['2026-11-03', '2026-11-04'])
        ->and($flock->fresh()->current_bird_count)->toBe(497);

    $this->get(route('imports.template', 'poultry'))->assertOk()->assertDownload('poultrilic-poultry-template.xlsx');
});

// ---- Livestock ----------------------------------------------------------------------------------

it('installs a template, records against a unit and keeps the head count', function () {
    $this->actingAs(plUser());
    $this->post(route('livestock.types.store'), ['template' => 'fish'])->assertRedirect();
    $type = LivestockType::where('name', 'Fish ponds')->firstOrFail();
    expect($type->fields()->count())->toBeGreaterThan(5);

    $this->post(route('livestock.units.store', $type), ['name' => 'Pond 1', 'opening_count' => 1000])->assertRedirect();
    $unit = LivestockUnit::firstOrFail();

    $this->post(route('livestock.records.store', [$type, $unit]), ['record_date' => '2026-07-01', 'v' => ['deaths' => 12, 'feed_kg' => 5.5, 'ph' => 7.1, 'water_changed' => '1']])->assertRedirect();
    $this->post(route('livestock.records.store', [$type, $unit]), ['record_date' => '2026-07-02', 'v' => ['deaths' => 8, 'harvested' => 100, 'harvest_kg' => 95, 'ph' => 6.9]])->assertRedirect();
    expect($unit->fresh()->current_count)->toBe(1000 - 12 - 8 - 100);

    // Too many losses is refused.
    $this->post(route('livestock.records.store', [$type, $unit]), ['record_date' => '2026-07-03', 'v' => ['harvested' => 5000]])->assertSessionHasErrors('values');

    $m = app(\App\Services\LivestockMetrics::class)->series($type, null, '2026-07-01', '2026-07-31', 'all')[0];
    expect($m['field:deaths'])->toEqual(20)->and($m['field:ph'])->toEqual(7.0)->and($m['head_start'])->toBe(1000)->and($m['head_end'])->toBe(880);

    // Pages render.
    $this->get(route('livestock.index', $type))->assertOk()->assertSee('Pond 1');
    $this->get(route('livestock.units.show', [$type, $unit]))->assertOk()->assertSee('Water pH');
    $this->get(route('livestock.types.edit', $type))->assertOk();
    $this->get(route('livestock.export', $type))->assertOk();

    // Admin adds a custom field.
    $this->post(route('livestock.fields.store', $type), ['label' => 'Ammonia', 'kind' => 'decimal', 'unit' => 'ppm', 'aggregate' => 'avg', 'headcount' => 'none'])->assertRedirect();
    expect($type->fields()->where('key', 'ammonia')->exists())->toBeTrue();
});

it('imports livestock records from the type template', function () {
    $this->actingAs(plUser());
    $type = LivestockTemplates::install('goats');
    $unit = $type->units()->create(['name' => 'Pen A', 'opening_count' => 20]);
    $path = plXlsx(['Pen', 'Date', 'Kids / lambs born', 'Deaths', 'Feed / supplement (kg)', 'Notes'], [
        ['Pen A', '2026-08-01', 3, 0, 4, 'twins'],
        ['Pen A', '2026-08-02', 0, 1, 4, null],
    ]);
    $this->post(route('imports.preview'), ['kind' => $type->slug, 'file' => new UploadedFile($path, 'goats.xlsx', null, null, true)]);
    $this->post(route('imports.commit', ImportBatch::firstOrFail()));

    expect(LivestockRecord::count())->toBe(2)->and($unit->fresh()->current_count)->toBe(22);
});

// ---- Reports, pages and API ---------------------------------------------------------------------

it('runs saved reports and exports them', function () {
    $this->actingAs(plUser());
    $flock = Flock::create(['name' => 'A', 'initial_bird_count' => 1000, 'status' => 'active']);
    plDays($flock, now()->subDays(5)->toDateString(), [[1, 800, 4], [0, 810, 4], [2, 805, 4]]);

    $this->get(route('reports.index', ['starters' => 1]))->assertRedirect();
    expect(ReportDefinition::count())->toBeGreaterThanOrEqual(6);

    $this->post(route('reports.store'), [
        'name' => 'Laying', 'source' => 'poultry', 'metrics' => ['eggs_laid', 'hen_day_pct'], 'group_by' => 'day', 'chart' => 'line', 'period' => 'last_30', 'per_entity' => 1,
    ])->assertRedirect();
    $rep = ReportDefinition::where('name', 'Laying')->firstOrFail();

    $this->get(route('reports.show', $rep))->assertOk()->assertSee('2,415');
    $this->get(route('reports.export', [$rep, 'xlsx']))->assertOk();
    $this->get(route('reports.export', [$rep, 'csv']))->assertOk();
    $this->get(route('reports.export', [$rep, 'pdf']))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('reports.performance'))->assertOk()->assertSee('At a glance');
    $this->get(route('reports.performance', ['format' => 'pdf']))->assertOk()->assertHeader('content-type', 'application/pdf');
    foreach (ReportDefinition::all() as $r) {
        $this->get(route('reports.show', $r))->assertOk();
    }
});

it('renders the overview, flock, merge, import and flock list pages', function () {
    $this->actingAs(plUser());
    $flock = Flock::create(['name' => 'Layers A', 'initial_bird_count' => 1000, 'status' => 'active']);
    plDays($flock, now()->subDays(3)->toDateString(), [[1, 800, 4], [0, 810, 4]]);

    $this->get(route('poultry.overview'))->assertOk()->assertSee('Farm overview')->assertSee('Layers A');
    $this->get(route('poultry.flocks.show', $flock))->assertOk()->assertSee('Month by month');
    $this->get(route('poultry.flocks.export', $flock))->assertOk();
    $this->get(route('poultry.merge'))->assertOk();
    $this->get(route('imports.index'))->assertOk();
    $this->get(route('flocks.index'))->assertOk()->assertSee('Layers A');
    $this->get(route('livestock.types.index'))->assertOk();
    $this->post(route('poultry.flocks.recalculate', $flock))->assertRedirect();

    $this->post(route('flocks.store'), ['name' => 'Broilers 1', 'flock_type' => 'broiler', 'initial_bird_count' => 500, 'status' => 'active'])
        ->assertRedirect();
    expect(Flock::where('name', 'Broilers 1')->value('flock_type'))->toBe('broiler');
});

it('serves the mobile API with tokens', function () {
    $user = plUser();
    $user->forceFill(['password' => bcrypt('secret-pass')])->save();
    $flock = Flock::create(['name' => 'A', 'initial_bird_count' => 100, 'status' => 'active']);

    $this->getJson('/api/v1/app/config')->assertOk()->assertJsonPath('data.api_version', 1);
    $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'wrong'])->assertStatus(422);
    $token = $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'secret-pass'])
        ->assertOk()->assertJsonPath('success', true)->json('data.token');
    $h = ['Authorization' => "Bearer {$token}"];

    $this->getJson('/api/v1/auth/me', $h)->assertOk()->assertJsonPath('data.modules.0', 'poultry');
    $this->postJson("/api/v1/flocks/{$flock->id}/entries", ['entry_date' => now()->toDateString(), 'daily_mortality' => 2, 'daily_feeds' => 1.5, 'eggs_produced' => 70], $h)
        ->assertCreated()->assertJsonPath('data.birds_alive', 98);
    $this->postJson("/api/v1/flocks/{$flock->id}/entries", ['entry_date' => now()->toDateString(), 'daily_mortality' => 3, 'daily_feeds' => 1.5, 'eggs_produced' => 70], $h)
        ->assertOk()->assertJsonPath('data.birds_alive', 97);
    expect(DailyEntry::count())->toBe(1);

    $this->getJson('/api/v1/dashboard', $h)->assertOk()->assertJsonPath('data.birds_now', 97);
    $this->getJson("/api/v1/flocks/{$flock->id}", $h)->assertOk()->assertJsonPath('data.lifetime.deaths', 3);
    $this->getJson("/api/v1/flocks/{$flock->id}/entries", $h)->assertOk()->assertJsonPath('meta.total', 1);
    $this->getJson('/api/v1/livestock/types', $h)->assertOk();
    $this->getJson('/api/v1/reports', $h)->assertOk();
    $this->app['auth']->forgetGuards();   // the test client keeps the signed-in user between calls
    $this->getJson('/api/v1/dashboard')->assertUnauthorized();
});
