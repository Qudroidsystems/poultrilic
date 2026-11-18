<?php

namespace App\Services\Import;

use App\Models\DailyEntry;
use App\Models\Flock;
use App\Services\DailyEntryRecorder;
use App\Services\FlockLedger;
use App\Support\Eggs;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reads flock daily records from Excel / CSV and saves them as flock-days.
 *
 * Columns are matched by name, so the farm's own sheets import as they are: the cleaned "Daily Data" sheet
 * (one row per flock-day, with a Flock column), a single-flock "Daily Log", or the Poultrilic template.
 * Running totals in the file (total deaths, birds alive, stolen to date) are not trusted: daily figures are
 * derived from them only when the daily column is missing, and the typed totals are kept so the ledger can
 * flag rows where they disagree.
 */
class FlockSheetImporter
{
    /**
     * field => accepted header names, normalised (lowercase letters and digits only). Earlier names win, so a
     * value column ("Farm: birds alive") is preferred over a formula column ("Birds alive").
     */
    public const COLUMNS = [
        'flock' => ['flock', 'flockname', 'batch', 'pen', 'house'],
        'entry_date' => ['date', 'entrydate', 'reportdate', 'reportdateest', 'recorddate', 'day date'],
        'week' => ['week', 'weekno', 'weeknumber', 'ageweeks'],
        'day' => ['day', 'dayinweek', 'dayno', 'daynumber'],
        'daily_mortality' => ['deathstoday', 'deaths', 'dailymortality', 'mortality', 'dead', 'mortalitytoday'],
        'total_deaths' => ['farmtotaldeaths', 'totaldeaths', 'totalmortality', 'cumulativedeaths', 'totaldeathscorrected'],
        'birds_stolen' => ['stolentoday', 'stolen', 'birdsstolen', 'stolenmissing', 'missing'],
        'stolen_total' => ['stolentotal', 'totalstolen'],
        'birds_culled' => ['culled', 'culls', 'birdsculled', 'culledsoldlive', 'birdssold', 'birdssoldlive'],
        'birds_alive' => ['farmbirdsalive', 'birdsalivecounted', 'birdsalive', 'currentbirds', 'birdsremaining', 'birds'],
        'daily_feeds' => ['feedfedtodaybags', 'feedtodaybags', 'feedusedtodaybags', 'feedtoday', 'dailyfeeds', 'feedbags', 'feedused', 'feed'],
        'available_feeds' => ['feedinstorebags', 'farmfeedremaining', 'feedremainingbags', 'feedinstore', 'availablefeeds', 'feedremaining', 'feedbalance'],
        'eggs_produced' => ['eggslaid', 'eggsproduced', 'eggscollected', 'eggproduction', 'dailyeggproduction', 'eggs'],
        'eggs_sold' => ['eggssold', 'soldeggs', 'dailysoldegg'],
        'broken_egg' => ['brokeneggs', 'broken', 'brokenegg', 'cracked'],
        'bad_eggs' => ['badeggs', 'bad', 'rotten'],
        'eggs_in_farm' => ['eggsinfarm', 'totaleggsinfarm', 'eggstock', 'eggsinstore'],
        'sick_bay' => ['sickbay', 'sick', 'sickbirds'],
        'drugs' => ['medication', 'drugs', 'drugsadministered', 'medicine', 'vaccine', 'treatment', 'vaccinationmedication'],
        'avg_weight_g' => ['avgweightg', 'averageweightg', 'avgweight', 'bodyweightg', 'weightg'],
        'notes' => ['notes', 'remarks', 'comment', 'comments', 'issues'],
        'reported_at' => ['senton', 'reportedat', 'sent', 'datesent'],
    ];

    public function __construct(private DailyEntryRecorder $recorder)
    {
    }

    /**
     * Parse a file without saving anything.
     *
     * @return array{sheet: string, mapped: array<string,string>, rows: array, errors: array, flocks: array}
     */
    public function parse(string $path, ?int $flockId = null, bool $createMissing = false): array
    {
        $book = IOFactory::load($path);
        [$sheet, $headerRow, $map] = $this->findSheet($book->getAllSheets());

        if (! $sheet) {
            return ['sheet' => null, 'mapped' => [], 'rows' => [], 'errors' => [[
                'row' => null, 'message' => 'No sheet with recognisable columns. The first rows need headers such as Date, Deaths today, Feed today (bags), Eggs laid.',
            ]], 'flocks' => []];
        }

        if (! isset($map['flock']) && ! $flockId) {
            return ['sheet' => $sheet->getTitle(), 'mapped' => $this->mappedLabels($sheet, $headerRow, $map), 'rows' => [], 'errors' => [[
                'row' => null, 'message' => 'This sheet has no Flock column. Choose the flock these records belong to.',
            ]], 'flocks' => []];
        }

        $rows = [];
        $errors = [];
        $maxRow = $sheet->getHighestDataRow();
        for ($r = $headerRow + 1; $r <= $maxRow; $r++) {
            $get = fn (string $field) => isset($map[$field]) ? $this->cell($sheet, $map[$field], $r) : null;

            $date = $this->date($get('entry_date'));
            $flockName = trim((string) $get('flock'));
            if (! $date) {
                $empty = $flockName === '' && $get('eggs_produced') === null && $get('daily_mortality') === null;
                if (! $empty && ($get('entry_date') !== null)) {
                    $errors[] = ['row' => $r, 'message' => 'Date not understood: "' . $get('entry_date') . '".'];
                }
                continue;   // blank lines, notes under the table
            }
            if ($date->isFuture()) {
                $errors[] = ['row' => $r, 'message' => 'Date ' . $date->toDateString() . ' is in the future.'];
                continue;
            }

            $rows[] = [
                'row' => $r,
                'flock_name' => $flockName,
                'entry_date' => $date->toDateString(),
                'week' => $this->int($get('week')),
                'day' => $this->int($get('day')),
                'daily_mortality' => $this->int($get('daily_mortality')),
                'total_deaths' => $this->int($get('total_deaths')),
                'birds_stolen' => $this->int($get('birds_stolen')),
                'stolen_total' => $this->int($get('stolen_total')),
                'birds_culled' => $this->int($get('birds_culled')) ?? 0,
                'birds_alive' => $this->int($get('birds_alive')),
                'daily_feeds' => $this->num($get('daily_feeds')) ?? 0,
                'available_feeds' => $this->num($get('available_feeds')),
                'eggs_produced' => $this->eggs($get('eggs_produced')),
                'eggs_sold' => $this->eggs($get('eggs_sold')),
                'broken_egg' => $this->eggs($get('broken_egg')),
                'bad_eggs' => $this->eggs($get('bad_eggs')),
                'eggs_in_farm' => $this->int($get('eggs_in_farm')),
                'sick_bay' => $this->int($get('sick_bay')) ?? 0,
                'drugs' => $this->text($get('drugs')),
                'avg_weight_g' => $this->num($get('avg_weight_g')),
                'notes' => $this->text($get('notes')),
                'reported_at' => $this->date($get('reported_at'), true)?->toDateTimeString(),
            ];
        }

        // Resolve flocks.
        $existing = Flock::all();
        $byName = $existing->keyBy(fn ($f) => Str::lower(trim((string) $f->name)));
        $fixed = $flockId ? $existing->firstWhere('id', $flockId) : null;
        $groups = collect($rows)->groupBy(fn ($row) => $fixed ? '#' . $fixed->id : Str::lower($row['flock_name']));

        $flocks = [];
        foreach ($groups as $key => $items) {
            $items = $items->sortBy('entry_date')->values();
            $flock = $fixed ?? $byName->get($key);
            $name = $fixed?->name ?? $items->first()['flock_name'];

            if (! $flock && $name === '') {
                foreach ($items as $it) {
                    $errors[] = ['row' => $it['row'], 'message' => 'No flock name on this row.'];
                }
                continue;
            }

            // Daily figures from running totals when the daily column is missing.
            $prevStolen = null;
            $prevDeaths = null;
            $items = $items->map(function ($it) use (&$prevStolen, &$prevDeaths) {
                if ($it['birds_stolen'] === null) {
                    $it['birds_stolen'] = ($it['stolen_total'] !== null && $prevStolen !== null) ? max(0, $it['stolen_total'] - $prevStolen) : 0;
                }
                if ($it['stolen_total'] !== null) {
                    $prevStolen = max($prevStolen ?? 0, $it['stolen_total']);
                }
                if ($it['daily_mortality'] === null) {
                    $it['daily_mortality'] = ($it['total_deaths'] !== null && $prevDeaths !== null) ? max(0, $it['total_deaths'] - $prevDeaths) : 0;
                }
                if ($it['total_deaths'] !== null) {
                    $prevDeaths = $it['total_deaths'];
                }

                return $it;
            });

            $dupes = $items->groupBy('entry_date')->filter(fn ($g) => $g->count() > 1);
            foreach ($dupes as $date => $g) {
                $errors[] = ['row' => $g->last()['row'], 'message' => "Flock \"{$name}\" has " . $g->count() . " rows for {$date}; the last one is used."];
            }
            $items = $items->keyBy('entry_date')->values();

            $first = $items->first();
            $existingDates = $flock ? DailyEntry::forFlock($flock->id)->whereIn('entry_date', $items->pluck('entry_date'))->pluck('entry_date')->map(fn ($d) => Carbon::parse($d)->toDateString())->all() : [];

            $flocks[] = [
                'key' => $key,
                'flock_id' => $flock?->id,
                'name' => $name,
                'create' => ! $flock,
                'can_create' => ! $flock && $createMissing,
                // A new flock starts with the birds alive before its first imported day.
                'initial_birds' => $flock?->initial_bird_count
                    ?? (($first['birds_alive'] ?? 0) + (int) $first['daily_mortality'] + (int) $first['birds_stolen'] + (int) $first['birds_culled']),
                // Eggs already in the farm before the first day: the farm's count less that day's net eggs.
                'opening_eggs' => $first['eggs_in_farm'] !== null
                    ? max(0, (int) $first['eggs_in_farm'] - ((int) $first['eggs_produced'] - (int) $first['eggs_sold'] - (int) $first['broken_egg'] - (int) $first['bad_eggs']))
                    : 0,
                'opening_feed' => $first['available_feeds'] !== null ? (float) $first['available_feeds'] + (float) $first['daily_feeds'] : 0,
                'from' => $first['entry_date'],
                'to' => $items->last()['entry_date'],
                'rows' => $items->count(),
                'updates' => count($existingDates),
                'eggs' => $items->sum('eggs_produced'),
                'deaths' => $items->sum('daily_mortality'),
                'layer' => $items->sum('eggs_produced') > 0,
                'items' => $items->all(),
            ];
            if (! $flock && ! $createMissing) {
                $errors[] = ['row' => null, 'message' => "No flock called \"{$name}\". Tick \"create missing flocks\" or rename it in the file to match an existing flock."];
            }
        }

        return [
            'sheet' => $sheet->getTitle(),
            'mapped' => $this->mappedLabels($sheet, $headerRow, $map),
            'rows' => $rows,
            'errors' => $errors,
            'flocks' => $flocks,
        ];
    }

    /**
     * Save a parsed file. Rows are upserted by flock and date, so importing the same file twice changes nothing.
     *
     * @return array{created: int, updated: int, skipped: int, flocks_created: int, errors: array}
     */
    public function commit(array $parsed, bool $createMissing = false): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'flocks_created' => 0, 'errors' => []];

        FlockLedger::batch(function () use ($parsed, $createMissing, &$stats) {
            foreach ($parsed['flocks'] as $g) {
                $flock = $g['flock_id'] ? Flock::find($g['flock_id']) : null;
                if (! $flock) {
                    if (! $createMissing) {
                        $stats['skipped'] += $g['rows'];
                        continue;
                    }
                    $flock = Flock::create([
                        'name' => $g['name'],
                        'breed' => $g['layer'] ? 'Layers' : null,
                        'flock_type' => $g['layer'] ? 'layer' : 'pullet',
                        'initial_bird_count' => max(0, (int) $g['initial_birds']),
                        'current_bird_count' => max(0, (int) $g['initial_birds']),
                        'opening_feed_stock' => $g['opening_feed'],
                        'opening_egg_stock' => $g['opening_eggs'] ?? 0,
                        'date_of_arrival' => $g['from'],
                        'age_in_weeks' => $this->ageAtStart($g['items'][0] ?? []),
                        'status' => 'active',
                    ]);
                    $stats['flocks_created']++;
                }

                foreach ($g['items'] as $it) {
                    $existing = DailyEntry::forFlock($flock->id)->where('entry_date', $it['entry_date'])->first();
                    try {
                        $this->recorder->record($flock, [
                            'entry_date' => $it['entry_date'],
                            'week_number' => $it['week'] ?: null,
                            'day_number' => ($it['day'] && $it['day'] >= 1 && $it['day'] <= 7) ? $it['day'] : null,
                            'daily_mortality' => (int) $it['daily_mortality'],
                            'birds_stolen' => (int) $it['birds_stolen'],
                            'birds_culled' => (int) $it['birds_culled'],
                            'sick_bay' => (int) $it['sick_bay'],
                            'daily_feeds' => (float) $it['daily_feeds'],
                            'available_feeds' => (float) ($it['available_feeds'] ?? 0),
                            'eggs_produced' => (int) $it['eggs_produced'],
                            'eggs_sold' => (int) $it['eggs_sold'],
                            'broken_egg' => (int) $it['broken_egg'],
                            'bad_eggs' => (int) $it['bad_eggs'],
                            'drugs' => $it['drugs'],
                            'notes' => $it['notes'],
                            'avg_weight_g' => $it['avg_weight_g'],
                            'reported_birds_alive' => $it['birds_alive'],
                            'reported_total_deaths' => $it['total_deaths'],
                            'reported_at' => $it['reported_at'],
                            'source' => 'import',
                        ], $existing, false);
                        $existing ? $stats['updated']++ : $stats['created']++;
                    } catch (ValidationException $e) {
                        $stats['skipped']++;
                        $stats['errors'][] = ['row' => $it['row'], 'message' => collect($e->errors())->flatten()->first()];
                    }
                }
            }
        });

        return $stats;
    }

    /** Birds' age in weeks at the first imported day, from the farm's Week/Day columns. */
    private function ageAtStart(array $first): int
    {
        if (empty($first['week'])) {
            return 0;
        }
        $day = ($first['day'] ?? 1) ?: 1;

        return max(0, intdiv(((int) $first['week'] - 1) * 7 + $day - 1, 7));
    }

    /** @param Worksheet[] $sheets */
    private function findSheet(array $sheets): array
    {
        $best = [null, 0, [], 0];
        foreach ($sheets as $sheet) {
            $maxCol = min(60, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
            for ($r = 1; $r <= min(15, $sheet->getHighestDataRow()); $r++) {
                $headers = [];
                for ($c = 1; $c <= $maxCol; $c++) {
                    $v = $sheet->getCell([$c, $r])->getValue();
                    if (is_string($v) && trim($v) !== '') {
                        $headers[$c] = self::norm($v);
                    }
                }
                $map = $this->match($headers);
                $score = count($map) + (isset($map['entry_date']) ? 5 : 0);
                if ($score > $best[3] && count($map) >= 3 && isset($map['entry_date'])) {
                    $best = [$sheet, $r, $map, $score];
                }
            }
        }

        return [$best[0], $best[1], $best[2]];
    }

    /** headers: column index => normalised header. Returns field => column index. */
    private function match(array $headers): array
    {
        $map = [];
        $used = [];
        foreach (self::COLUMNS as $field => $aliases) {
            foreach ($aliases as $alias) {
                $alias = self::norm($alias);
                $col = array_search($alias, $headers, true);
                if ($col !== false && ! isset($used[$col])) {
                    $map[$field] = $col;
                    $used[$col] = true;
                    break;
                }
            }
        }

        return $map;
    }

    private function mappedLabels(Worksheet $sheet, int $headerRow, array $map): array
    {
        $out = [];
        foreach ($map as $field => $col) {
            $out[$field] = (string) $sheet->getCell([$col, $headerRow])->getValue();
        }

        return $out;
    }

    public static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($s)));
    }

    private function cell(Worksheet $sheet, int $col, int $row): mixed
    {
        $cell = $sheet->getCell([$col, $row]);
        $v = $cell->getValue();
        if (is_string($v) && str_starts_with($v, '=')) {
            try {
                $v = $cell->getCalculatedValue();
            } catch (\Throwable) {
                $v = $cell->getOldCalculatedValue();
            }
        }
        if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $v = $v->getPlainText();
        }
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '' || $v === '-' || $v === '–') {
                return null;
            }
        }

        return $v;
    }

    private function date(mixed $v, bool $withTime = false): ?Carbon
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            if ($v instanceof \DateTimeInterface) {
                $c = Carbon::instance($v);
            } elseif (is_numeric($v) && (float) $v > 20000 && (float) $v < 80000) {
                $c = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v));
            } elseif (is_string($v)) {
                // Day-first, as written in Nigeria (03/11/2026 = 3 Nov), unless it is ISO.
                $c = preg_match('#^\d{1,2}[/.-]\d{1,2}[/.-]\d{2,4}#', $v)
                    ? Carbon::createFromFormat(strlen(explode(' ', str_replace(['.', '-'], '/', $v))[0]) > 8 ? 'd/m/Y' : 'd/m/y', explode(' ', str_replace(['.', '-'], '/', $v))[0])
                    : Carbon::parse($v);
            } else {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return $withTime ? $c : $c->startOfDay();
    }

    private function int(mixed $v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_string($v)) {
            $v = str_replace(',', '', $v);
            if (! is_numeric($v)) {
                return null;
            }
        }

        return is_numeric($v) ? (int) round((float) $v) : null;
    }

    private function num(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_string($v)) {
            $v = str_replace(',', '', $v);
        }

        return is_numeric($v) ? round((float) $v, 2) : null;
    }

    /** Eggs as pieces: a number, or "11 Cr 12PC". */
    private function eggs(mixed $v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_numeric($v)) {
            return max(0, (int) round((float) $v));
        }

        return Eggs::parse(str_replace(',', '', (string) $v));
    }

    private function text(mixed $v): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        if ($v === '' || in_array(Str::lower($v), ['0', 'none', 'nil', 'n/a'], true)) {
            return null;
        }

        return Str::limit($v, 250, '');
    }
}
