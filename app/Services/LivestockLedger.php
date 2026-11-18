<?php

namespace App\Services;

use App\Models\LivestockRecord;
use App\Models\LivestockUnit;

/**
 * Head count for a livestock unit, walked from its opening count through every record in date order:
 * fields marked "adds" (births, purchases, stocking) add, "takes" (deaths, sales, harvest) subtract, and a
 * "set" field (a physical count) replaces the running figure when it is filled in.
 */
class LivestockLedger
{
    public static function rebuild(int|LivestockUnit|null $unit): void
    {
        $unit = $unit instanceof LivestockUnit ? $unit : LivestockUnit::with('type.fields')->find($unit);
        if (! $unit) {
            return;
        }
        $fields = $unit->type?->fields ?? collect();
        $count = (int) $unit->opening_count;

        foreach (LivestockRecord::where('livestock_unit_id', $unit->id)->orderBy('record_date')->orderBy('id')->get() as $rec) {
            $count = self::apply($fields, $rec->values ?? [], $count);
            if ((int) $rec->count_after !== $count) {
                $rec->count_after = $count;
                $rec->saveQuietly();
            }
        }

        if ((int) $unit->current_count !== $count) {
            $unit->current_count = $count;
            $unit->saveQuietly();
        }
    }

    public static function apply($fields, array $values, int $count): int
    {
        $set = null;
        foreach ($fields as $f) {
            $v = $values[$f->key] ?? null;
            if ($v === null || $v === '' || ! is_numeric($v)) {
                continue;
            }
            match ($f->headcount) {
                'add' => $count += (int) $v,
                'subtract' => $count -= (int) $v,
                'set' => $set = (int) $v,
                default => null,
            };
        }

        return $set ?? $count;
    }
}
