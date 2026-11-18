<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LivestockRecord;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Services\LivestockMetrics;
use App\Services\LivestockRecorder;
use Illuminate\Http\Request;

/** Livestock types with their field definitions (the app builds its forms from them), units and records. */
class LivestockApiController extends ApiController
{
    public function types()
    {
        $this->authorizeTo('View livestock');

        return $this->ok(LivestockType::where('active', true)->with(['fields', 'units'])->orderBy('sort')->get()->map(fn ($t) => [
            'id' => $t->id, 'slug' => $t->slug, 'name' => $t->name, 'unit_label' => $t->unit_label, 'count_label' => $t->count_label,
            'icon' => $t->icon, 'color' => $t->color, 'description' => $t->description,
            'fields' => $t->fields->map(fn ($f) => $f->only(['key', 'label', 'kind', 'unit', 'options', 'aggregate', 'headcount', 'required', 'help']))->values(),
            'units' => $t->units->map(fn ($u) => $this->unit($u))->values(),
        ])->all());
    }

    public function unit(LivestockUnit $unit, ?Request $request = null): array
    {
        return [
            'id' => $unit->id, 'type_id' => $unit->livestock_type_id, 'name' => $unit->name, 'breed' => $unit->breed,
            'status' => $unit->status, 'opening_count' => $unit->opening_count, 'current_count' => $unit->current_count,
            'start_date' => $unit->start_date?->toDateString(),
            'last_record' => $unit->records()->max('record_date'),
        ];
    }

    public function showUnit(Request $request, LivestockUnit $unit, LivestockMetrics $metrics)
    {
        $this->authorizeTo('View livestock');
        $type = $unit->type()->with('fields')->first();

        return $this->ok($this->unit($unit) + [
            'summary' => $metrics->series($type, [$unit->id], $request->input('from'), $request->input('to'), 'all')[0],
            'monthly' => $metrics->series($type, [$unit->id], $request->input('from'), $request->input('to'), 'month'),
        ], null, ['metrics' => $metrics->catalogue($type)]);
    }

    public function records(Request $request, LivestockUnit $unit)
    {
        $this->authorizeTo('View livestock');
        $page = LivestockRecord::where('livestock_unit_id', $unit->id)->orderByDesc('record_date')->paginate(min(100, (int) $request->input('per_page', 31)));

        return $this->ok(collect($page->items())->map(fn ($r) => [
            'id' => $r->id, 'record_date' => $r->record_date->toDateString(), 'values' => (object) ($r->values ?? []),
            'count_after' => $r->count_after, 'notes' => $r->notes, 'source' => $r->source,
        ])->all(), null, ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()]);
    }

    public function saveRecord(Request $request, LivestockUnit $unit, LivestockRecorder $recorder)
    {
        $request->validate(['record_date' => 'required|date|before_or_equal:today', 'values' => 'nullable|array', 'notes' => 'nullable|string|max:2000']);
        $existing = LivestockRecord::where('livestock_unit_id', $unit->id)->where('record_date', $request->input('record_date'))->first();
        $this->authorizeTo($existing ? 'Update livestock record' : 'Create livestock record');

        $r = $recorder->record($unit, $request->input('record_date'), (array) $request->input('values', []), $request->input('notes'), 'mobile', $existing, $request->user()->id);

        return $this->ok(['id' => $r->id, 'record_date' => $r->record_date->toDateString(), 'values' => (object) $r->values, 'count_after' => $r->count_after],
            $existing ? 'Record updated.' : 'Record saved.', [], $existing ? 200 : 201);
    }

    public function deleteRecord(LivestockRecord $record)
    {
        $this->authorizeTo('Delete livestock record');
        $record->delete();

        return $this->ok(null, 'Record deleted.');
    }
}
