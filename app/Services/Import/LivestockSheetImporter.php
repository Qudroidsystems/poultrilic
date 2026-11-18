<?php

namespace App\Services\Import;

use App\Models\LivestockRecord;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Services\LivestockRecorder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports records for a livestock type. Columns: the unit (Pond / Herd / Pen... or "Unit"), Date, Notes, and
 * one column per field, matched by its label, its label with unit, or its key.
 */
class LivestockSheetImporter
{
    public function __construct(private LivestockRecorder $recorder)
    {
    }

    public function headers(LivestockType $type): array
    {
        $h = [[$type->unit_label, 'Name of the ' . strtolower($type->unit_label) . ' exactly as in Poultrilic'], ['Date', 'The day the record describes']];
        foreach ($type->fields as $f) {
            $note = trim(($f->help ?? '') . ' ' . match ($f->kind) {
                'select' => 'One of: ' . implode(', ', $f->options ?? []),
                'yes_no' => 'Yes or No',
                'number' => 'Whole number',
                'decimal', 'money' => 'Number',
                default => '',
            });
            $h[] = [$f->displayLabel(), $note];
        }
        $h[] = ['Notes', null];

        return $h;
    }

    public function parse(LivestockType $type, string $path, ?int $unitId = null, bool $createMissing = false): array
    {
        $book = IOFactory::load($path);
        $type->load('fields');
        $want = [
            'unit' => [SheetTools::norm($type->unit_label), 'unit', 'name', SheetTools::norm($type->name)],
            'date' => ['date', 'recorddate', 'entrydate'],
            'notes' => ['notes', 'remarks', 'comment', 'comments'],
        ];
        foreach ($type->fields as $f) {
            $want['f:' . $f->key] = array_unique([SheetTools::norm($f->displayLabel()), SheetTools::norm($f->label), SheetTools::norm($f->key)]);
        }

        $best = [null, 0, [], 0];
        foreach ($book->getAllSheets() as $sheet) {
            $maxCol = min(80, Coordinate::columnIndexFromString($sheet->getHighestDataColumn()));
            for ($r = 1; $r <= min(10, $sheet->getHighestDataRow()); $r++) {
                $headers = [];
                for ($c = 1; $c <= $maxCol; $c++) {
                    $v = $sheet->getCell([$c, $r])->getValue();
                    if (is_string($v) && trim($v) !== '') {
                        $headers[$c] = SheetTools::norm($v);
                    }
                }
                $map = [];
                foreach ($want as $field => $aliases) {
                    foreach ($aliases as $a) {
                        $col = array_search($a, $headers, true);
                        if ($col !== false && ! in_array($col, $map, true)) {
                            $map[$field] = $col;
                            break;
                        }
                    }
                }
                if (isset($map['date']) && count($map) > $best[3]) {
                    $best = [$sheet, $r, $map, count($map)];
                }
            }
        }
        [$sheet, $hr, $map] = $best;
        if (! $sheet) {
            return ['rows' => [], 'errors' => [['row' => null, 'message' => 'No sheet with a Date column and this type\'s field names. Download the template to see the expected columns.']], 'units' => [], 'mapped' => []];
        }
        if (! isset($map['unit']) && ! $unitId) {
            return ['rows' => [], 'errors' => [['row' => null, 'message' => "No {$type->unit_label} column. Choose the " . strtolower($type->unit_label) . ' these records belong to.']], 'units' => [], 'mapped' => []];
        }

        $units = $type->units()->get()->keyBy(fn ($u) => Str::lower(trim($u->name)));
        $fixed = $unitId ? LivestockUnit::find($unitId) : null;
        $rows = [];
        $errors = [];
        for ($r = $hr + 1; $r <= $sheet->getHighestDataRow(); $r++) {
            $date = isset($map['date']) ? SheetTools::date(SheetTools::value($sheet, $map['date'], $r)) : null;
            if (! $date) {
                continue;
            }
            $unitName = $fixed?->name ?? trim((string) SheetTools::value($sheet, $map['unit'], $r));
            $values = [];
            foreach ($type->fields as $f) {
                if (isset($map['f:' . $f->key])) {
                    $values[$f->key] = SheetTools::value($sheet, $map['f:' . $f->key], $r);
                }
            }
            $rows[] = [
                'row' => $r, 'unit' => $unitName, 'unit_id' => $fixed?->id ?? $units->get(Str::lower($unitName))?->id,
                'date' => $date->toDateString(), 'values' => $values,
                'notes' => isset($map['notes']) ? SheetTools::value($sheet, $map['notes'], $r) : null,
            ];
        }

        $summary = collect($rows)->groupBy('unit')->map(fn ($g, $name) => [
            'name' => $name, 'unit_id' => $g->first()['unit_id'], 'rows' => $g->count(),
            'from' => $g->min('date'), 'to' => $g->max('date'),
        ])->values()->all();
        foreach ($summary as $s) {
            if (! $s['unit_id'] && ! $createMissing) {
                $errors[] = ['row' => null, 'message' => "No {$type->unit_label} called \"{$s['name']}\". Tick \"create missing\" or fix the name."];
            }
        }
        $mapped = [];
        foreach ($map as $k => $col) {
            $mapped[$k] = (string) $sheet->getCell([$col, $hr])->getValue();
        }

        return ['rows' => $rows, 'errors' => $errors, 'units' => $summary, 'mapped' => $mapped];
    }

    public function commit(LivestockType $type, array $parsed, bool $createMissing, ?int $userId = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'units_created' => 0, 'errors' => []];
        $made = [];
        foreach (collect($parsed['rows'])->sortBy('date') as $row) {
            $unit = $row['unit_id'] ? LivestockUnit::find($row['unit_id']) : null;
            if (! $unit) {
                if (! $createMissing || $row['unit'] === '') {
                    $stats['skipped']++;
                    continue;
                }
                $unit = $made[$row['unit']] ??= tap(LivestockUnit::create([
                    'livestock_type_id' => $type->id, 'name' => $row['unit'], 'start_date' => $row['date'], 'opening_count' => 0, 'status' => 'active',
                ]), function () use (&$stats) {
                    $stats['units_created']++;
                });
            }
            $exists = LivestockRecord::where('livestock_unit_id', $unit->id)->where('record_date', $row['date'])->exists();
            try {
                $this->recorder->record($unit, $row['date'], $row['values'], $row['notes'] ? (string) $row['notes'] : null, 'import', null, $userId);
                $exists ? $stats['updated']++ : $stats['created']++;
            } catch (ValidationException $e) {
                $stats['skipped']++;
                $stats['errors'][] = ['row' => $row['row'], 'message' => collect($e->errors())->flatten()->first()];
            }
        }

        return $stats;
    }
}
