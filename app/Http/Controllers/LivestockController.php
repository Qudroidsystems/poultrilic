<?php

namespace App\Http\Controllers;

use App\Models\LivestockRecord;
use App\Models\LivestockType;
use App\Models\LivestockUnit;
use App\Models\ReportDefinition;
use App\Services\Import\LivestockSheetImporter;
use App\Services\Import\SheetTools;
use App\Services\LivestockMetrics;
use App\Services\LivestockRecorder;
use Illuminate\Http\Request;

/** Recording for any livestock type: units (ponds, herds, pens), daily records, totals and export. */
class LivestockController extends Controller
{
    public function __construct(private LivestockMetrics $metrics)
    {
        $this->middleware('permission:View livestock', ['only' => ['index', 'show', 'export']]);
        $this->middleware('permission:Manage livestock units', ['only' => ['storeUnit', 'updateUnit', 'destroyUnit']]);
        $this->middleware('permission:Create livestock record', ['only' => ['storeRecord']]);
        $this->middleware('permission:Delete livestock record', ['only' => ['destroyRecord']]);
    }

    private function range(Request $request, LivestockType $type, string $default = 'last_90'): array
    {
        $period = $request->input('period', $default);
        $period = array_key_exists($period, ReportDefinition::PERIODS) ? $period : $default;
        $latest = LivestockRecord::whereHas('unit', fn ($q) => $q->where('livestock_type_id', $type->id))->max('record_date');
        $anchor = $latest ? min(\Carbon\Carbon::parse($latest), now())->toDateString() : null;
        [$from, $to] = ReportDefinition::periodRange($period, $request->input('from'), $request->input('to'), in_array($period, ['last_7', 'last_30', 'last_90', 'last_12m']) ? $anchor : null);

        return [$period, $from, $to];
    }

    public function index(Request $request, LivestockType $type)
    {
        [$period, $from, $to] = $this->range($request, $type);
        $type->load('fields');

        return view('livestock.index', [
            'pagetitle' => $type->name,
            'type' => $type,
            'units' => $type->units()->withCount('records')->get()->map(fn ($u) => $u->setAttribute('last_record', $u->records()->max('record_date'))),
            'period' => $period, 'from' => $from, 'to' => $to,
            'catalogue' => $this->metrics->catalogue($type),
            'summary' => $this->metrics->series($type, null, $from, $to, 'all')[0],
            'byUnit' => $this->metrics->series($type, null, $from, $to, 'unit'),
            'trend' => $this->metrics->series($type, null, $from, $to, 'month'),
        ]);
    }

    public function show(Request $request, LivestockType $type, LivestockUnit $unit)
    {
        abort_unless($unit->livestock_type_id === $type->id, 404);
        [$period, $from, $to] = $this->range($request, $type, 'all');
        $type->load('fields');
        $edit = $request->filled('edit') ? LivestockRecord::where('livestock_unit_id', $unit->id)->find($request->integer('edit')) : null;

        return view('livestock.unit', [
            'pagetitle' => $unit->name,
            'type' => $type, 'unit' => $unit,
            'period' => $period, 'from' => $from, 'to' => $to,
            'catalogue' => $this->metrics->catalogue($type),
            'summary' => $this->metrics->series($type, [$unit->id], $from, $to, 'all')[0],
            'monthly' => $this->metrics->series($type, [$unit->id], $from, $to, 'month'),
            'records' => LivestockRecord::where('livestock_unit_id', $unit->id)
                ->when($from, fn ($q) => $q->where('record_date', '>=', $from))
                ->when($to, fn ($q) => $q->where('record_date', '<=', $to))
                ->orderByDesc('record_date')->paginate(31)->withQueryString(),
            'edit' => $edit,
        ]);
    }

    public function storeUnit(Request $request, LivestockType $type)
    {
        $data = $this->unitRules($request);
        $unit = $type->units()->create($data + ['status' => 'active']);

        return redirect()->route('livestock.units.show', [$type, $unit])->with('success', "{$unit->name} added.");
    }

    public function updateUnit(Request $request, LivestockType $type, LivestockUnit $unit)
    {
        abort_unless($unit->livestock_type_id === $type->id, 404);
        $unit->update($this->unitRules($request) + ['status' => $request->input('status') === 'closed' ? 'closed' : 'active']);

        return back()->with('success', 'Saved.');
    }

    public function destroyUnit(LivestockType $type, LivestockUnit $unit)
    {
        abort_unless($unit->livestock_type_id === $type->id, 404);
        $unit->delete();

        return redirect()->route('livestock.index', $type)->with('success', "{$unit->name} and its records were deleted.");
    }

    public function storeRecord(Request $request, LivestockType $type, LivestockUnit $unit, LivestockRecorder $recorder)
    {
        abort_unless($unit->livestock_type_id === $type->id, 404);
        $request->validate(['record_date' => 'required|date|before_or_equal:today', 'notes' => 'nullable|string|max:2000']);
        $record = $request->filled('record_id') ? LivestockRecord::where('livestock_unit_id', $unit->id)->findOrFail($request->integer('record_id')) : null;
        if ($record && ! $request->user()->can('Update livestock record')) {
            abort(403);
        }

        $recorder->record($unit, $request->input('record_date'), (array) $request->input('v', []), $request->input('notes'), 'web', $record, $request->user()?->id);

        return redirect()->route('livestock.units.show', [$type, $unit])->with('success', 'Record saved for ' . \Carbon\Carbon::parse($request->input('record_date'))->format('j M Y') . '.');
    }

    public function destroyRecord(LivestockType $type, LivestockUnit $unit, LivestockRecord $record)
    {
        abort_unless($record->livestock_unit_id === $unit->id && $unit->livestock_type_id === $type->id, 404);
        $record->delete();

        return back()->with('success', 'Record deleted.');
    }

    /** All records for the type in the import template layout. */
    public function export(Request $request, LivestockType $type)
    {
        $type->load('fields');
        $headers = app(LivestockSheetImporter::class)->headers($type);
        array_splice($headers, 2, 0, [[$type->count_label . ' after', 'Worked out by Poultrilic (ignored on import)']]);
        $rows = LivestockRecord::with('unit')->whereHas('unit', fn ($q) => $q->where('livestock_type_id', $type->id))
            ->orderBy('record_date')->get()
            ->map(function ($r) use ($type) {
                $row = [$r->unit->name, $r->record_date, $r->count_after];
                foreach ($type->fields as $f) {
                    $v = $r->values[$f->key] ?? null;
                    $row[] = $f->kind === 'yes_no' && $v !== null ? ($v ? 'Yes' : 'No') : $v;
                }
                $row[] = $r->notes;

                return $row;
            })->all();

        return SheetTools::download(SheetTools::workbook(\Illuminate\Support\Str::limit($type->name, 28, ''), $headers, $rows), $type->slug . '-records-' . now()->format('Y-m-d') . '.xlsx');
    }

    private function unitRules(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:40',
            'breed' => 'nullable|string|max:120',
            'start_date' => 'nullable|date',
            'opening_count' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);
    }
}
