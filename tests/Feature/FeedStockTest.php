<?php

use App\Models\DailyEntry;
use App\Models\Expense;
use App\Models\FeedPurchase;
use App\Models\Flock;
use App\Models\User;
use App\Models\WeekEntry;
use App\Services\FeedStockService;
use App\Support\FarmSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function feedUser(array $permissions): User
{
    $user = User::factory()->create();
    foreach ($permissions as $p) {
        Permission::findOrCreate($p, 'web');
    }
    $user->givePermissionTo($permissions);

    return $user;
}

/** Feed given on a date (bags). Each call uses its own flock, so two calls on one date model two flocks. */
function fedOn(string $date, float $bags): DailyEntry
{
    $flock = Flock::create(['name' => 'F' . uniqid(), 'initial_bird_count' => 100, 'status' => 'active']);
    $week = WeekEntry::create(['flock_id' => $flock->id, 'week_name' => 'W1']);

    return DailyEntry::create([
        'week_entry_id' => $week->id, 'flock_id' => $flock->id, 'day_number' => 1,
        'entry_date' => $date, 'daily_feeds' => $bags, 'total_feeds_consumed' => (int) $bags, 'available_feeds' => 0,
        'daily_mortality' => 0, 'sick_bay' => 0, 'total_mortality' => 0, 'current_birds' => 100,
        'daily_egg_production' => '0 Cr 0PC', 'daily_sold_egg' => '0 Cr 0PC', 'total_sold_egg' => '0 Cr 0PC',
        'broken_egg' => 0, 'outstanding_egg' => '0 Cr 0PC', 'total_egg_in_farm' => '0 Cr 0PC',
    ]);
}

function purchase(string $date, float $bags, float $cost = 15000): FeedPurchase
{
    return FeedPurchase::create(['purchase_date' => $date, 'bags' => $bags, 'cost_per_bag' => $cost]);
}

beforeEach(function () {
    DailyEntry::query()->delete();
});

// ------------------------------------------------------------ stock maths

it('is not tracking until the first purchase', function () {
    $s = app(FeedStockService::class)->snapshot();

    expect($s['tracking'])->toBeFalse()->and($s['on_hand'])->toBe(0.0)->and($s['needs_reorder'])->toBeFalse();
});

it('subtracts feeding from the first purchase date and ignores earlier history', function () {
    fedOn(now()->subDays(30)->toDateString(), 99);     // before the ledger starts: ignored
    purchase(now()->subDays(5)->toDateString(), 40, 0); // opening stock
    purchase(now()->subDays(3)->toDateString(), 20);
    fedOn(now()->subDays(4)->toDateString(), 5);
    fedOn(now()->subDays(2)->toDateString(), 5);

    $s = app(FeedStockService::class)->snapshot();

    expect($s['tracking'])->toBeTrue()
        ->and($s['purchased'])->toBe(60.0)
        ->and($s['consumed'])->toBe(10.0)
        ->and($s['on_hand'])->toBe(50.0)
        ->and($s['avg_daily_use'])->toBe(5.0)
        ->and($s['days_remaining'])->toBe(10.0);
});

it('averages daily use over farm-wide totals per date, not per row', function () {
    purchase(now()->subDays(3)->toDateString(), 100);
    fedOn(now()->subDay()->toDateString(), 4);
    fedOn(now()->subDay()->toDateString(), 6);          // same date, second flock: 10 bags that day
    fedOn(now()->toDateString(), 10);

    expect(app(FeedStockService::class)->snapshot()['avg_daily_use'])->toBe(10.0);
});

it('flags a reorder when days remaining are within the warning window', function () {
    purchase(now()->subDays(2)->toDateString(), 30);
    fedOn(now()->subDay()->toDateString(), 10);          // 20 left at 10/day = 2 days

    $s = app(FeedStockService::class)->snapshot();
    expect($s['days_remaining'])->toBe(2.0)->and($s['needs_reorder'])->toBeTrue();

    FarmSettings::set(['feed_reorder_days' => 1]);
    expect(app(FeedStockService::class)->snapshot()['needs_reorder'])->toBeFalse();
});

it('flags a reorder when stock has run out and never shows negative days', function () {
    purchase(now()->subDays(2)->toDateString(), 5);
    fedOn(now()->subDay()->toDateString(), 8);

    $s = app(FeedStockService::class)->snapshot();
    expect($s['on_hand'])->toBe(-3.0)->and($s['days_remaining'])->toBe(0.0)->and($s['needs_reorder'])->toBeTrue();
});

it('has no days estimate when nothing has been fed recently', function () {
    purchase(now()->subDay()->toDateString(), 10);

    $s = app(FeedStockService::class)->snapshot();
    expect($s['days_remaining'])->toBeNull()->and($s['needs_reorder'])->toBeFalse();
});

// ------------------------------------------------------------ page + permissions

it('blocks the feed page without permission', function () {
    $this->actingAs(User::factory()->create())->get('/feed')->assertForbidden();
    $this->actingAs(feedUser(['View feed']))->post('/feed', ['purchase_date' => now()->toDateString(), 'bags' => 5])->assertForbidden();
});

it('shows the stock summary', function () {
    purchase(now()->subDays(2)->toDateString(), 30);
    fedOn(now()->subDay()->toDateString(), 10);

    $this->actingAs(feedUser(['View feed']))->get('/feed')->assertOk()
        ->assertViewHas('stock', fn ($s) => $s['on_hand'] == 20 && $s['needs_reorder'] === true)
        ->assertSee('Time to reorder feed');
});

// ------------------------------------------------------------ CRUD + expense sync

it('records a purchase with a matching feed expense, and keeps them in step', function () {
    $user = feedUser(['Create feed', 'Update feed', 'Delete feed']);
    $this->actingAs($user);

    $this->post('/feed', [
        'purchase_date' => now()->subDay()->toDateString(), 'bags' => 20, 'cost_per_bag' => 16000, 'vendor' => 'Agro Ltd',
    ])->assertRedirect()->assertSessionHas('success');

    $p = FeedPurchase::firstOrFail();
    $e = Expense::firstOrFail();
    expect($p->expense_id)->toBe($e->id)
        ->and($e->category)->toBe('feed')
        ->and((float) $e->amount)->toBe(320000.0)
        ->and($e->vendor)->toBe('Agro Ltd')
        ->and($e->expense_date->toDateString())->toBe(now()->subDay()->toDateString());

    $this->put("/feed/{$p->id}", [
        'purchase_date' => now()->toDateString(), 'bags' => 10, 'cost_per_bag' => 17000,
    ])->assertRedirect();
    expect(Expense::count())->toBe(1)
        ->and((float) $e->fresh()->amount)->toBe(170000.0)
        ->and($e->fresh()->expense_date->toDateString())->toBe(now()->toDateString());

    // Changing to opening stock (no cost) drops the expense.
    $this->put("/feed/{$p->id}", ['purchase_date' => now()->toDateString(), 'bags' => 10, 'cost_per_bag' => 0])->assertRedirect();
    expect(Expense::count())->toBe(0)->and($p->fresh()->expense_id)->toBeNull();

    // And back to a cost creates a new one.
    $this->put("/feed/{$p->id}", ['purchase_date' => now()->toDateString(), 'bags' => 10, 'cost_per_bag' => 1000])->assertRedirect();
    expect(Expense::count())->toBe(1);

    $this->delete("/feed/{$p->id}")->assertRedirect();
    expect(FeedPurchase::count())->toBe(0)->and(Expense::count())->toBe(0);
});

it('records opening stock without an expense', function () {
    $this->actingAs(feedUser(['Create feed']));

    $this->post('/feed', ['purchase_date' => now()->toDateString(), 'bags' => 35])->assertRedirect();

    expect(FeedPurchase::first()->is_opening_stock)->toBeTrue()
        ->and((float) FeedPurchase::first()->cost_per_bag)->toBe(0.0)
        ->and(Expense::count())->toBe(0);
});

it('validates purchases', function (array $bad, string $field) {
    $this->actingAs(feedUser(['Create feed']));

    $this->post('/feed', array_merge(['purchase_date' => now()->toDateString(), 'bags' => 10, 'cost_per_bag' => 100], $bad))
        ->assertSessionHasErrors($field);
    expect(FeedPurchase::count())->toBe(0);
})->with([
    'future date' => [['purchase_date' => '2999-01-01'], 'purchase_date'],
    'zero bags' => [['bags' => 0], 'bags'],
    'negative bags' => [['bags' => -2], 'bags'],
    'negative cost' => [['cost_per_bag' => -1], 'cost_per_bag'],
    'missing date' => [['purchase_date' => ''], 'purchase_date'],
]);

// ------------------------------------------------------------ dashboard + migration

it('shows the low-feed alert on the dashboard only when needed', function () {
    Flock::create(['name' => 'Placeholder', 'initial_bird_count' => 10, 'status' => 'active']);
    $this->actingAs(feedUser(['dashboard']));

    $this->get('/dashboard')->assertOk()->assertDontSee('Feed running low');

    purchase(now()->subDays(2)->toDateString(), 30);
    fedOn(now()->subDay()->toDateString(), 10);
    $this->get('/dashboard')->assertOk()->assertSee('Feed running low');

    purchase(now()->toDateString(), 500);
    $this->get('/dashboard')->assertDontSee('Feed running low');
});

it('grants the feed permissions to Super Admin and dashboard roles only', function () {
    $admin = Role::create(['name' => 'Super Admin']);
    $staff = Role::create(['name' => 'Staff']);
    $staff->givePermissionTo(Permission::create(['name' => 'View flock']));

    (require database_path('migrations/2026_11_05_130100_add_feed_permissions.php'))->up();
    (require database_path('migrations/2026_11_05_130100_add_feed_permissions.php'))->up();

    expect($admin->fresh()->hasPermissionTo('Create feed'))->toBeTrue()
        ->and($staff->fresh()->hasPermissionTo('View feed'))->toBeFalse()
        ->and(Permission::where('name', 'View feed')->count())->toBe(1);
});
