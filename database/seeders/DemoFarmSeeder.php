<?php

namespace Database\Seeders;

use App\Http\Controllers\ReportController;
use App\Models\Customer;
use App\Models\DailyEntry;
use App\Models\Expense;
use App\Models\FeedPurchase;
use App\Models\Flock;
use App\Models\LivestockType;
use App\Models\ReportDefinition;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use App\Services\DailyEntryRecorder;
use App\Services\FlockLedger;
use App\Services\LivestockRecorder;
use App\Support\FarmSettings;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A believable demo farm, so every screen, chart and report has something to show:
 *
 *  - Layers A: 1,000 Isa Brown from 18 weeks, ~8 months of records, a normal laying curve (peak ~93% at
 *    27–30 weeks), low daily mortality, a couple of stolen birds.
 *  - Layers B: 1,500 Lohmann from 16 weeks, 4 months, still climbing towards peak.
 *  - Broilers batch: 2,000 day-old, 35 days, weekly sample weights, higher first-week losses.
 *  - Pullets: 1,200 day-old, 9 weeks, vaccination schedule.
 *  - Feed bought in when stores run low, customers and egg sales (paid, part-paid, unpaid), monthly costs.
 *  - A fish pond, a goat pen (if those types exist) with daily records, and the starter reports.
 *
 * Deterministic (fixed random seed) and only runs on a farm with no flocks yet, so it never touches real data.
 */
class DemoFarmSeeder extends Seeder
{
    public function run(): void
    {
        if (Flock::exists()) {
            $this->command?->warn('  The farm already has flocks; demo data was not added.');

            return;
        }
        mt_srand(20261118);
        $today = now()->startOfDay();
        $userId = User::orderBy('id')->value('id');
        if (in_array(FarmSettings::text('farm_name'), ['', 'Poultrilic Farm', (string) config('app.name')], true)) {
            FarmSettings::set(['farm_name' => 'Poultrilic Demo Farm']);
        }

        DB::transaction(function () use ($today, $userId) {
            FlockLedger::batch(function () use ($today) {
                $this->layers('Layers A (Isa Brown)', 'House 1', 1000, 18, $today->copy()->subDays(240), 0.00026, true);
                $this->layers('Layers B (Lohmann)', 'House 2', 1500, 16, $today->copy()->subDays(120), 0.00024, false);
                $this->broilers('Broilers batch 3', 'House 3', 2000, $today->copy()->subDays(34));
                $this->pullets('Pullets (growing)', 'Brooder', 1200, $today->copy()->subDays(62));
            });
            $this->feedPurchases($today, $userId);
            $this->sales($today, $userId);
            $this->expenses($today, $userId);
        });

        $this->livestock($today, $userId);
        $this->reports($userId);
    }

    /** Hen-day % for a layer of $ageWeeks (typical commercial curve, with day-to-day noise). */
    private function layCurve(float $ageWeeks): float
    {
        if ($ageWeeks < 18.5) {
            return 0;
        }
        if ($ageWeeks < 27) {
            $p = 0.93 / (1 + exp(-1.15 * ($ageWeeks - 22.3)));   // fast rise to peak
        } else {
            $p = 0.93 - max(0, $ageWeeks - 30) * 0.0045;          // slow decline after peak
        }

        return max(0, min(0.96, $p + (mt_rand(-20, 20) / 1000)));
    }

    private function layers(string $name, string $house, int $birds, int $ageWeeks, Carbon $arrival, float $dailyMort, bool $theft): void
    {
        $flock = Flock::create([
            'name' => $name, 'breed' => str_contains($name, 'Isa') ? 'Isa Brown' : 'Lohmann Brown', 'flock_type' => 'layer', 'house' => $house,
            'initial_bird_count' => $birds, 'date_of_arrival' => $arrival->toDateString(), 'age_in_weeks' => $ageWeeks, 'status' => 'active',
            'opening_feed_stock' => 40,
        ]);
        $rec = app(DailyEntryRecorder::class);
        $alive = $birds;
        $store = 40.0;
        $stock = 0;
        $days = (int) $arrival->diffInDays(now()->startOfDay());
        $vaccines = [7 => 'Newcastle (Lasota) booster', 35 => 'Fowl pox', 70 => 'Newcastle (Komarov)', 120 => 'Deworming', 160 => 'Newcastle booster'];

        for ($d = 0; $d < $days; $d++) {
            $date = $arrival->copy()->addDays($d);
            $age = $ageWeeks + $d / 7;
            $deaths = $this->poisson($alive * $dailyMort * ($d < 10 ? 2 : 1));
            $stolen = ($theft && in_array($d, [96, 97, 151], true)) ? mt_rand(2, 5) : 0;
            $alive -= $deaths + $stolen;
            $laid = (int) round($alive * $this->layCurve($age));
            $broken = (int) round($laid * mt_rand(6, 14) / 1000);
            $bad = (int) round($laid * mt_rand(1, 5) / 1000);
            $stock += $laid - $broken - $bad;
            // Eggs leave the farm every second or third day, in whole crates.
            $sold = ($d % 3 !== 1) ? intdiv(max(0, $stock - 60), 30) * 30 : 0;
            $stock -= $sold;
            $feed = round($alive * ($age < 20 ? 0.105 : 0.118) / FarmSettings::bagWeightKg() * 2) / 2;   // to the half bag
            $store -= $feed;
            if ($store < 8) {
                $store += 40;
            }
            $late = mt_rand(1, 10) === 1 ? mt_rand(2, 5) : 0;

            $rec->record($flock, [
                'entry_date' => $date->toDateString(),
                'daily_mortality' => $deaths, 'birds_stolen' => $stolen, 'birds_culled' => 0, 'sick_bay' => mt_rand(0, 3),
                'daily_feeds' => $feed, 'available_feeds' => max(0, $store),
                'eggs_produced' => $laid, 'eggs_sold' => $sold, 'broken_egg' => $broken, 'bad_eggs' => $bad,
                'drugs' => $vaccines[$d] ?? ($d % 30 === 14 ? 'Multivitamin in water' : null),
                'notes' => $stolen ? 'Birds missing at morning count; reported to security.' : null,
                'reported_birds_alive' => $d % 7 === 6 ? $alive : null,
                'reported_at' => $date->copy()->addDays($late)->setTime(18, 30)->toDateTimeString(),
                'source' => 'web',
            ]);
        }
    }

    private function broilers(string $name, string $house, int $birds, Carbon $arrival): void
    {
        $flock = Flock::create([
            'name' => $name, 'breed' => 'Arbor Acres', 'flock_type' => 'broiler', 'house' => $house,
            'initial_bird_count' => $birds, 'date_of_arrival' => $arrival->toDateString(), 'age_in_weeks' => 0, 'status' => 'active',
        ]);
        $rec = app(DailyEntryRecorder::class);
        $alive = $birds;
        $store = 30.0;
        $days = (int) $arrival->diffInDays(now()->startOfDay());
        $weight = [0 => 42, 7 => 185, 14 => 480, 21 => 930, 28 => 1500, 35 => 2150];
        $drugs = [0 => 'Glucose + vitamins', 1 => 'Glucose + vitamins', 7 => 'Gumboro (IBD) vaccine', 14 => 'Newcastle (Lasota)', 21 => 'Gumboro booster', 22 => 'Coccidiostat'];
        for ($d = 0; $d < $days; $d++) {
            $date = $arrival->copy()->addDays($d);
            $deaths = $this->poisson($alive * ($d < 7 ? 0.0022 : 0.0006));
            $alive -= $deaths;
            $gPerBird = min(190, 15 + $d * 5.2);
            $feed = round($alive * $gPerBird / 1000 / FarmSettings::bagWeightKg() * 2) / 2;
            $store -= $feed;
            if ($store < 10) {
                $store += 40;
            }
            $rec->record($flock, [
                'entry_date' => $date->toDateString(), 'daily_mortality' => $deaths, 'daily_feeds' => $feed, 'available_feeds' => max(0, $store),
                'drugs' => $drugs[$d] ?? null, 'avg_weight_g' => $weight[$d] ?? null, 'sick_bay' => mt_rand(0, 6),
                'reported_at' => $date->copy()->setTime(19, 0)->toDateTimeString(),
            ]);
        }
    }

    private function pullets(string $name, string $house, int $birds, Carbon $arrival): void
    {
        $flock = Flock::create([
            'name' => $name, 'breed' => 'Isa Brown', 'flock_type' => 'pullet', 'house' => $house,
            'initial_bird_count' => $birds, 'date_of_arrival' => $arrival->toDateString(), 'age_in_weeks' => 0, 'status' => 'active',
        ]);
        $rec = app(DailyEntryRecorder::class);
        $alive = $birds;
        $store = 25.0;
        $days = (int) $arrival->diffInDays(now()->startOfDay());
        $drugs = [0 => 'Glucose + vitamins', 1 => 'Marek\'s (hatchery) / vitamins', 7 => 'Gumboro (IBD)', 14 => 'Newcastle (Lasota)', 21 => 'Gumboro booster', 28 => 'Newcastle booster', 42 => 'Fowl pox'];
        for ($d = 0; $d < $days; $d++) {
            $date = $arrival->copy()->addDays($d);
            $deaths = $this->poisson($alive * ($d < 10 ? 0.003 : 0.0006));
            $alive -= $deaths;
            $feed = round($alive * min(80, 12 + $d * 1.1) / 1000 / FarmSettings::bagWeightKg() * 2) / 2;
            $store -= $feed;
            if ($store < 6) {
                $store += 25;
            }
            $rec->record($flock, [
                'entry_date' => $date->toDateString(), 'daily_mortality' => $deaths, 'daily_feeds' => $feed, 'available_feeds' => max(0, $store),
                'drugs' => $drugs[$d] ?? null, 'avg_weight_g' => $d % 7 === 0 ? round(40 + $d * 11.5) : null,
                'reported_at' => $date->copy()->setTime(18, 0)->toDateTimeString(),
            ]);
        }
    }

    private function feedPurchases(Carbon $today, ?int $userId): void
    {
        $cost = (float) FarmSettings::feedCostPerBag();
        $first = Carbon::parse(DailyEntry::min('entry_date'));
        $vendors = ['Top Feeds Ltd', 'Vital Feed Depot', 'Chikun Feeds'];
        for ($d = $first->copy(); $d->lte($today); $d->addDays(7)) {
            $used = (float) DailyEntry::whereBetween('entry_date', [$d->toDateString(), $d->copy()->addDays(6)->toDateString()])->sum('daily_feeds');
            if ($used <= 0) {
                continue;
            }
            $bags = ceil($used / 5) * 5;
            $price = round($cost * (1 + ($d->diffInDays($first) / 365) * 0.12));   // feed prices creep up
            $purchase = FeedPurchase::create([
                'purchase_date' => $d->toDateString(), 'bags' => $bags, 'cost_per_bag' => $price,
                'vendor' => $vendors[mt_rand(0, 2)], 'created_by' => $userId,
            ]);
            $expense = Expense::create([
                'expense_date' => $d->toDateString(), 'category' => 'feed', 'amount' => $bags * $price, 'vendor' => $purchase->vendor,
                'description' => "Feed purchase: {$bags} bags", 'created_by' => $userId,
            ]);
            $purchase->update(['expense_id' => $expense->id]);
        }
    }

    private function sales(Carbon $today, ?int $userId): void
    {
        $customers = collect([
            ['Mama Nkechi Provisions', '08031234567', 'Oja Oba market'],
            ['Adewale Bakery', '08052223344', 'Alagbaka'],
            ['Grace Supermarket', '08167778899', 'Oke-Ijebu road'],
            ['Hotel Kings Court', '09011122233', 'Ijapo estate'],
            ['Alhaji Musa (wholesale)', '08099887766', 'Isikan'],
            ['Walk-in customers', null, null],
        ])->map(fn ($c) => Customer::create(['name' => $c[0], 'phone' => $c[1], 'address' => $c[2]]));

        $price = (float) FarmSettings::eggPrice();
        // Invoice the eggs that left the farm, week by week, split across customers.
        $weeks = DailyEntry::where('eggs_sold', '>', 0)->selectRaw('entry_date, eggs_sold')->orderBy('entry_date')->get()
            ->groupBy(fn ($e) => Carbon::parse($e->entry_date)->startOfWeek()->toDateString());
        foreach ($weeks as $week => $rows) {
            $eggs = (int) $rows->sum('eggs_sold');
            $shares = [0.3, 0.25, 0.2, 0.15, 0.1];
            foreach ($shares as $i => $share) {
                $n = intdiv((int) round($eggs * $share), 30) * 30;
                if ($n <= 0) {
                    continue;
                }
                $date = Carbon::parse($week)->addDays(mt_rand(0, 5));
                if ($date->gt($today)) {
                    $date = $today->copy();
                }
                $unit = $price + ($i === 4 ? -10 : 0);   // the wholesaler gets a little off
                $sale = Sale::create([
                    'invoice_number' => Sale::nextInvoiceNumber((int) $date->format('Y')),
                    'customer_id' => $customers[$i]->id, 'sale_date' => $date->toDateString(), 'eggs' => $n,
                    'price_per_egg' => $unit, 'total' => $n * $unit, 'created_by' => $userId,
                ]);
                $recent = $date->diffInDays($today) < 21;
                $roll = mt_rand(1, 10);
                $paid = (! $recent || $roll <= 6) ? $sale->total : ($roll <= 8 ? round($sale->total * 0.5, -2) : 0);
                if ($paid > 0) {
                    SalePayment::create([
                        'sale_id' => $sale->id, 'paid_on' => $date->copy()->addDays(mt_rand(0, 6))->min($today)->toDateString(),
                        'amount' => $paid, 'method' => ['transfer', 'cash', 'pos'][mt_rand(0, 2)], 'created_by' => $userId,
                    ]);
                }
            }
        }
    }

    private function expenses(Carbon $today, ?int $userId): void
    {
        $first = Carbon::parse(DailyEntry::min('entry_date'))->startOfMonth();
        $flockA = Flock::where('name', 'like', 'Layers A%')->value('id');
        for ($m = $first->copy(); $m->lte($today); $m->addMonth()) {
            $items = [
                ['labor', 'Farm hands (3) – monthly pay', 165000, null],
                ['utilities', 'Electricity and diesel', mt_rand(38, 62) * 1000, null],
                ['drugs', 'Vaccines, vitamins and disinfectant', mt_rand(25, 55) * 1000, null],
                ['packaging', 'Egg crates', mt_rand(15, 30) * 1000, $flockA],
                ['transport', 'Deliveries to customers', mt_rand(12, 25) * 1000, null],
            ];
            foreach ($items as $i => [$cat, $desc, $amount, $flock]) {
                $date = $m->copy()->addDays(min(27, 2 + $i * 5));
                if ($date->gt($today)) {
                    continue;
                }
                Expense::create(['expense_date' => $date->toDateString(), 'category' => $cat, 'description' => $desc, 'amount' => $amount, 'flock_id' => $flock, 'created_by' => $userId]);
            }
        }
    }

    private function livestock(Carbon $today, ?int $userId): void
    {
        $rec = app(LivestockRecorder::class);

        if ($fish = LivestockType::where('name', 'Fish ponds')->first()) {
            foreach ([['Pond 1 (catfish)', 2500, 110], ['Pond 2 (catfish)', 2000, 45]] as [$name, $n, $daysAgo]) {
                $start = $today->copy()->subDays($daysAgo);
                $unit = $fish->units()->create(['name' => $name, 'breed' => 'Clarias gariepinus', 'start_date' => $start->toDateString(), 'opening_count' => 0]);
                for ($d = 0; $d < $daysAgo; $d++) {
                    $w = round(4 * pow(1.045, $d), 0);    // grams, roughly exponential early growth
                    $harvest = $d === 100 ? 600 : 0;
                    $rec->record($unit, $start->copy()->addDays($d)->toDateString(), array_filter([
                        'stocked' => $d === 0 ? $n : null,
                        'deaths' => $d === 0 ? null : $this->poisson($d < 14 ? 6 : 1.5),
                        'feed_kg' => $d === 0 ? null : round(min(60, $n * $w * 0.03 / 1000), 1),
                        'feed_size' => $w < 30 ? '1.5' : ($w < 120 ? '3.0' : '4.5'),
                        'avg_weight_g' => $d % 14 === 0 ? min(1200, $w) : null,
                        'water_changed' => $d % 5 === 0 ? 1 : null,
                        'ph' => round(6.8 + mt_rand(0, 8) / 10, 1),
                        'temp_c' => round(26 + mt_rand(0, 30) / 10, 1),
                        'harvested' => $harvest ?: null,
                        'harvest_kg' => $harvest ? 610 : null,
                        'sales_amount' => $harvest ? 610 * 2300 : null,
                    ], fn ($v) => $v !== null), $harvest ? 'Partial harvest, sold at the farm gate.' : null, 'web', null, $userId);
                }
            }
        }

        if ($goats = LivestockType::where('name', 'Goats & sheep')->first()) {
            $start = $today->copy()->subDays(90);
            $unit = $goats->units()->create(['name' => 'Pen A (WAD goats)', 'breed' => 'West African Dwarf', 'start_date' => $start->toDateString(), 'opening_count' => 24]);
            for ($d = 0; $d < 90; $d += 3) {
                $rec->record($unit, $start->copy()->addDays($d)->toDateString(), array_filter([
                    'births' => in_array($d, [21, 24, 45, 66], true) ? mt_rand(1, 2) : null,
                    'deaths' => $d === 33 ? 1 : null,
                    'sold' => $d === 75 ? 4 : null,
                    'sales_amount' => $d === 75 ? 4 * 55000 : null,
                    'feed_kg' => 9 + mt_rand(0, 4),
                    'avg_weight_kg' => $d % 30 === 0 ? 18 + $d / 30 : null,
                    'treatment' => $d === 30 ? 'Deworming (albendazole)' : null,
                ], fn ($v) => $v !== null), null, 'web', null, $userId);
            }
        }
    }

    private function reports(?int $userId): void
    {
        foreach (ReportController::STARTERS as [$name, $source, $metrics, $group, $chart, $period, $perEntity, $desc]) {
            ReportDefinition::firstOrCreate(['name' => $name], [
                'source' => $source, 'metrics' => $metrics, 'group_by' => $group, 'chart' => $chart, 'period' => $period,
                'per_entity' => $perEntity, 'description' => $desc, 'created_by' => $userId,
                'pinned' => in_array($name, ['Monthly laying performance', 'Mortality by month'], true),
            ]);
        }
    }

    /** Small Poisson draw for daily deaths. */
    private function poisson(float $lambda): int
    {
        if ($lambda <= 0) {
            return 0;
        }
        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $l);

        return $k - 1;
    }
}
