<?php

use App\Models\Expense;
use App\Models\Flock;
use App\Models\Setting;
use App\Models\User;
use App\Models\WeekEntry;
use App\Support\FarmSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function userWith(array $permissions): User
{
    $user = User::factory()->create();
    foreach ($permissions as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

function seedFlockAndWeek(): array
{
    Flock::create(['name' => 'Placeholder', 'initial_bird_count' => 10, 'status' => 'active']); // takes id 1 (hardcoded overrides in dashboard)
    $flock = Flock::create(['name' => 'Flock B', 'breed' => 'Layers', 'initial_bird_count' => 1000, 'status' => 'active']);
    $week = WeekEntry::create(['flock_id' => $flock->id, 'week_name' => 'Week 1']);

    return [$flock, $week];
}

function postEntry($test, $week, array $over = [])
{
    return $test->postJson("/daily-entries/{$week->id}", array_merge([
        'day_number' => 1, 'daily_feeds' => 2, 'available_feeds' => 10, 'daily_mortality' => 0, 'sick_bay' => 0,
        'daily_egg_production_crates' => 11, 'daily_egg_production_pieces' => 12,
        'daily_sold_egg_crates' => 10, 'daily_sold_egg_pieces' => 0,   // 300 eggs sold
        'broken_egg_crates' => 0, 'broken_egg_pieces' => 0,
        'drugs' => 'Nil', 'reorder_feeds' => 1,
        'entry_date' => now()->subDay()->toDateString(),
    ], $over));
}

// ---------------------------------------------------------------- FarmSettings

it('falls back to the old hardcoded values when nothing is stored', function () {
    expect(FarmSettings::eggPrice())->toBe(250)
        ->and(FarmSettings::feedCostPerBag())->toBe(15000)
        ->and(FarmSettings::birdCost())->toBe(2000)
        ->and(FarmSettings::drugCostPerDay())->toBe(5000)
        ->and(FarmSettings::dailyLaborCost())->toBe(10000)
        ->and(FarmSettings::bagWeightKg())->toBe(50)
        ->and(FarmSettings::useRecordedExpenses())->toBeFalse();
});

it('stores values, ignores unknown keys and returns fresh values after a change', function () {
    FarmSettings::set(['egg_price' => 180.5, 'nonsense' => 1]);

    expect(FarmSettings::eggPrice())->toBe(180.5)
        ->and(Setting::where('key', 'nonsense')->exists())->toBeFalse();

    FarmSettings::set(['egg_price' => 200]);
    expect(FarmSettings::eggPrice())->toBe(200);
});

it('ignores blank or non-numeric stored values', function () {
    Setting::create(['key' => 'egg_price', 'value' => 'abc']);
    FarmSettings::flush();

    expect(FarmSettings::eggPrice())->toBe(250);
});

// ---------------------------------------------------------------- Settings page

it('blocks the settings page without permission', function () {
    $this->actingAs(User::factory()->create())->get('/settings')->assertForbidden();
    $this->actingAs(userWith(['View settings']))->put('/settings', [])->assertForbidden();
});

it('shows and saves settings for permitted users', function () {
    $this->actingAs(userWith(['View settings', 'Update settings']));

    $this->get('/settings')->assertOk()->assertSee('value="250"', false);

    $this->put('/settings', [
        'egg_price' => 120, 'feed_cost_per_bag' => 18000, 'bag_weight_kg' => 25,
        'bird_cost' => 2500, 'drug_cost_per_day' => 3000, 'daily_labor_cost' => 8000, 'feed_reorder_days' => 5,
        'use_recorded_expenses' => 1,
    ])->assertRedirect(route('settings.index'));

    expect(FarmSettings::eggPrice())->toBe(120)
        ->and(FarmSettings::feedCostPerBag())->toBe(18000)
        ->and(FarmSettings::bagWeightKg())->toBe(25)
        ->and(FarmSettings::get('feed_reorder_days'))->toBe(5)
        ->and(FarmSettings::useRecordedExpenses())->toBeTrue();
});

it('rejects invalid settings', function () {
    $this->actingAs(userWith(['Update settings']));

    $this->put('/settings', ['egg_price' => -5, 'feed_cost_per_bag' => 'x'])
        ->assertSessionHasErrors(['egg_price', 'feed_cost_per_bag', 'bag_weight_kg']);

    expect(FarmSettings::eggPrice())->toBe(250);
});

// ---------------------------------------------------------------- Dashboard uses the settings

it('computes dashboard revenue from the egg price setting', function () {
    [, $week] = seedFlockAndWeek();
    $this->actingAs(userWith(['dashboard', 'Create daily-entry']));
    postEntry($this, $week)->assertCreated();

    $this->get('/dashboard')->assertOk()->assertViewHas('dateRangeRevenue', 300 * 250);

    FarmSettings::set(['egg_price' => 100]);
    $this->get('/dashboard')->assertViewHas('dateRangeRevenue', 300 * 100);
});

it('estimates costs from the configured rates by default', function () {
    [, $week] = seedFlockAndWeek();
    $this->actingAs(userWith(['dashboard']));
    postEntry($this, $week, ['daily_feeds' => 4])->assertCreated();

    FarmSettings::set(['feed_cost_per_bag' => 20000]);

    $this->get('/dashboard')->assertViewHas('dateRangeFeedCost', 4 * 20000)
        ->assertViewHas('usingRecordedExpenses', false);
});

it('uses recorded expenses on the dashboard when switched on, excluding bird purchases', function () {
    [$flock, $week] = seedFlockAndWeek();
    $this->actingAs(userWith(['dashboard']));
    postEntry($this, $week)->assertCreated();

    $d = now()->subDay()->toDateString();
    foreach ([['feed', 50000], ['drugs', 10000], ['labor', 20000], ['utilities', 5000], ['birds', 999999]] as [$cat, $amt]) {
        Expense::create(['expense_date' => $d, 'category' => $cat, 'amount' => $amt, 'flock_id' => $flock->id]);
    }
    Expense::create(['expense_date' => $d, 'category' => 'feed', 'amount' => 7777, 'flock_id' => null]); // whole farm

    FarmSettings::set(['use_recorded_expenses' => 1]);

    // All flocks: everything except bird purchases.
    $this->get('/dashboard')
        ->assertViewHas('usingRecordedExpenses', true)
        ->assertViewHas('dateRangeFeedCost', 57777.0)
        ->assertViewHas('dateRangeDrugCost', 10000.0)
        ->assertViewHas('dateRangeLaborCost', 20000.0)
        ->assertViewHas('dateRangeOtherExpenses', 5000.0)
        ->assertViewHas('dateRangeTotalExpenses', 92777.0)
        ->assertViewHas('lifetimeOperationalExpenses', 92777.0);

    // One flock selected: only that flock's expenses.
    $this->get('/dashboard?flock_id=' . $flock->id)
        ->assertViewHas('dateRangeFeedCost', 50000.0)
        ->assertViewHas('dateRangeTotalExpenses', 85000.0);
});

it('only counts expenses inside the selected dates', function () {
    [$flock, $week] = seedFlockAndWeek();
    $this->actingAs(userWith(['dashboard']));
    postEntry($this, $week)->assertCreated();

    Expense::create(['expense_date' => now()->subDays(100)->toDateString(), 'category' => 'feed', 'amount' => 40000, 'flock_id' => $flock->id]);
    Expense::create(['expense_date' => now()->subDay()->toDateString(), 'category' => 'feed', 'amount' => 1000, 'flock_id' => $flock->id]);
    FarmSettings::set(['use_recorded_expenses' => 1]);

    $this->get('/dashboard')
        ->assertViewHas('dateRangeFeedCost', 1000.0)      // last 30 days only
        ->assertViewHas('lifetimeFeedCost', 41000.0);     // lifetime
});

// ---------------------------------------------------------------- Expenses

function expensePayload(array $over = []): array
{
    return array_merge([
        'expense_date' => now()->subDay()->toDateString(), 'category' => 'feed',
        'amount' => 125000.50, 'description' => '20 bags layers mash', 'vendor' => 'Agro Ltd',
    ], $over);
}

it('blocks expenses without the right permission', function () {
    $this->actingAs(User::factory()->create());
    $this->get('/expenses')->assertForbidden();
    $this->post('/expenses', expensePayload())->assertForbidden();

    $viewer = userWith(['View expense']);
    $this->actingAs($viewer)->get('/expenses')->assertOk();
    $this->post('/expenses', expensePayload())->assertForbidden();
});

it('records, edits and deletes an expense', function () {
    [$flock] = seedFlockAndWeek();
    $user = userWith(['View expense', 'Create expense', 'Update expense', 'Delete expense']);
    $this->actingAs($user);

    $this->post('/expenses', expensePayload(['flock_id' => $flock->id]))->assertRedirect()->assertSessionHas('success');

    $expense = Expense::firstOrFail();
    expect((float) $expense->amount)->toBe(125000.50)
        ->and($expense->created_by)->toBe($user->id)
        ->and($expense->flock_id)->toBe($flock->id)
        ->and($expense->expense_date->toDateString())->toBe(now()->subDay()->toDateString());

    $this->put("/expenses/{$expense->id}", expensePayload(['category' => 'drugs', 'amount' => 900, 'flock_id' => null]))->assertRedirect();
    expect($expense->fresh()->category)->toBe('drugs')
        ->and((float) $expense->fresh()->amount)->toBe(900.0)
        ->and($expense->fresh()->flock_id)->toBeNull();

    $this->delete("/expenses/{$expense->id}")->assertRedirect();
    expect(Expense::count())->toBe(0);
});

it('validates expenses', function (array $bad, string $field) {
    $this->actingAs(userWith(['Create expense']));

    $this->post('/expenses', expensePayload($bad))->assertSessionHasErrors($field);
    expect(Expense::count())->toBe(0);
})->with([
    'future date' => [['expense_date' => '2999-01-01'], 'expense_date'],
    'zero amount' => [['amount' => 0], 'amount'],
    'negative amount' => [['amount' => -10], 'amount'],
    'unknown category' => [['category' => 'lottery'], 'category'],
    'unknown flock' => [['flock_id' => 9999], 'flock_id'],
]);

it('summarises the filtered set by category, across all pages', function () {
    [$flock] = seedFlockAndWeek();
    $this->actingAs(userWith(['View expense']));

    foreach (range(1, 30) as $i) {   // 30 rows > one page of 25
        Expense::create(['expense_date' => now()->subDay()->toDateString(), 'category' => 'feed', 'amount' => 100, 'flock_id' => $flock->id]);
    }
    Expense::create(['expense_date' => now()->subDay()->toDateString(), 'category' => 'labor', 'amount' => 500, 'flock_id' => null]);
    Expense::create(['expense_date' => now()->subDays(90)->toDateString(), 'category' => 'feed', 'amount' => 99999, 'flock_id' => $flock->id]); // outside default 30 days

    $this->get('/expenses')->assertOk()
        ->assertViewHas('total', 3500.0)
        ->assertViewHas('byCategory', fn ($c) => $c['feed'] == 3000 && $c['labor'] == 500);

    $this->get('/expenses?category=labor')->assertViewHas('total', 500.0);
    $this->get('/expenses?flock_id=' . $flock->id)->assertViewHas('total', 3000.0);
    $this->get('/expenses?from=' . now()->subDays(120)->toDateString())->assertViewHas('total', 103499.0);
});

// ---------------------------------------------------------------- Permissions migration

it('grants the finance permissions to Super Admin and dashboard roles only', function () {
    $admin = Role::create(['name' => 'Super Admin']);
    $manager = Role::create(['name' => 'Manager']);
    $manager->givePermissionTo(Permission::create(['name' => 'dashboard']));
    $staff = Role::create(['name' => 'Staff']);
    $staff->givePermissionTo(Permission::create(['name' => 'View flock']));

    (require database_path('migrations/2026_11_05_120100_add_finance_permissions.php'))->up();

    expect($admin->fresh()->hasPermissionTo('View expense'))->toBeTrue()
        ->and($admin->fresh()->hasPermissionTo('Update settings'))->toBeTrue()
        ->and($manager->fresh()->hasPermissionTo('Create expense'))->toBeTrue()
        ->and($staff->fresh()->hasPermissionTo('View expense'))->toBeFalse();

    // running it again must not duplicate or fail
    (require database_path('migrations/2026_11_05_120100_add_finance_permissions.php'))->up();
    expect(Permission::where('name', 'View expense')->count())->toBe(1);
});
