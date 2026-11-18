<?php

namespace App\Services;

use App\Models\LivestockRecord;
use App\Models\LivestockUnit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Validates and saves one day's record for a livestock unit (web form, API and import share it). */
class LivestockRecorder
{
    /**
     * @param  array  $values  field key => raw value
     * @throws ValidationException
     */
    public function record(LivestockUnit $unit, string $date, array $values, ?string $notes = null, string $source = 'web', ?LivestockRecord $record = null, ?int $userId = null): LivestockRecord
    {
        $type = $unit->type()->with('fields')->first();
        $rules = ['record_date' => ['required', 'date', 'before_or_equal:today']];
        $data = ['record_date' => $date];
        $names = [];
        foreach ($type->fields as $f) {
            $rules['v.' . $f->key] = $f->rule();
            $data['v'][$f->key] = $values[$f->key] ?? null;
            $names['v.' . $f->key] = $f->label;
        }
        Validator::make($data, $rules, [], $names)->validate();

        $clean = [];
        foreach ($type->fields as $f) {
            $v = $f->cast($values[$f->key] ?? null);
            if ($v !== null) {
                $clean[$f->key] = $v;
            }
        }

        $date = Carbon::parse($date)->toDateString();
        $record ??= LivestockRecord::where('livestock_unit_id', $unit->id)->where('record_date', $date)->first();

        // Losses cannot exceed the animals there before the day.
        $before = LivestockRecord::where('livestock_unit_id', $unit->id)->where('record_date', '<', $date)
            ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
            ->orderByDesc('record_date')->value('count_after') ?? $unit->opening_count;
        $after = LivestockLedger::apply($type->fields, $clean, (int) $before);
        if ($after < 0) {
            throw ValidationException::withMessages(['values' => [sprintf(
                'This takes the %s below zero: %d before %s, %d after.', strtolower($type->count_label), $before, Carbon::parse($date)->format('j M Y'), $after
            )]]);
        }

        $attrs = ['livestock_unit_id' => $unit->id, 'record_date' => $date, 'values' => $clean, 'notes' => $notes, 'source' => $source];
        if ($record) {
            $record->fill($attrs)->save();
        } else {
            $record = LivestockRecord::create($attrs + ['created_by' => $userId]);
        }

        return $record->refresh();
    }
}
