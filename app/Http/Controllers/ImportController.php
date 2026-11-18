<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use App\Models\ImportBatch;
use App\Models\LivestockType;
use App\Services\Import\FlockSheetImporter;
use App\Services\Import\LivestockSheetImporter;
use App\Services\Import\SheetTools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Excel / CSV import in two steps: upload -> preview (nothing saved, every problem listed) -> import.
 * Each upload is kept as an import batch, so the history page shows who imported what and when.
 */
class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Import records');
    }

    /** Template columns for poultry daily records: [header, note]. */
    public static function poultryHeaders(): array
    {
        return [
            ['Flock', 'Flock name exactly as in Poultrilic (pick from the list)'],
            ['Date', 'The day the record describes, e.g. 14 Nov 2026'],
            ['Week', 'Optional: age in weeks (the farm\'s week number)'],
            ['Day', 'Optional: day 1-7 in that week'],
            ['Deaths today', 'Birds that died today'],
            ['Stolen / missing', 'Birds stolen or missing today'],
            ['Culled / sold live', 'Birds culled, sold live or moved out today'],
            ['Birds alive (as counted)', 'Optional: a physical count, used to flag miscounts'],
            ['Feed fed today (bags)', 'Bags fed today (half bags as 0.5)'],
            ['Feed in store (bags)', 'Bags left in store at the end of the day'],
            ['Eggs laid', 'Pieces (or "11 Cr 12PC")'],
            ['Eggs sold', 'Pieces sold today'],
            ['Broken eggs', 'Pieces'],
            ['Bad eggs', 'Pieces'],
            ['Medication', 'Drug or vaccine given; leave blank for none'],
            ['Avg weight (g)', 'Optional sample body weight'],
            ['Notes', 'Anything unusual'],
        ];
    }

    public function index()
    {
        return view('imports.index', [
            'pagetitle' => 'Import from Excel',
            'flocks' => Flock::orderByRaw("CASE WHEN status='active' THEN 0 ELSE 1 END")->orderBy('name')->get(),
            'types' => LivestockType::where('active', true)->with('units')->orderBy('sort')->get(),
            'batches' => ImportBatch::with('user')->latest()->limit(30)->get(),
        ]);
    }

    public function template(string $kind)
    {
        if ($kind === 'poultry') {
            $flocks = Flock::where('status', 'active')->orderBy('name')->pluck('name')->filter()->values()->all();
            $book = SheetTools::workbook('Daily records', self::poultryHeaders(), [
                [$flocks[0] ?? 'Layers A', now()->subDay()->startOfDay(), null, null, 1, 0, 0, null, 4.5, 26, 712, 600, 4, 1, null, null, 'Example row - delete it'],
            ], [
                'How to fill the Poultrilic daily records sheet',
                '',
                'One row per flock per day. Several flocks can share the sheet: put the flock name in the first column.',
                'Only Flock, Date and the figures for the day are needed. Running totals (birds alive, total deaths, eggs in farm) are worked out by Poultrilic.',
                'Eggs are pieces. "11 Cr 12PC" is also accepted (30 eggs per crate).',
                'Feed is in bags. Use 0.5 for half a bag.',
                'Importing the same day again updates that day; it does not add a second record.',
                'Your own sheets also import: columns are matched by name (e.g. "Deaths today", "Feed today (bags)", "Eggs laid", "Feed in store (bags)").',
                'A "Birds alive" count or a "Total deaths" column is kept and compared with the daily figures, so miscounts are flagged on the flock page.',
            ], ['Flock' => $flocks]);

            return SheetTools::download($book, 'poultrilic-poultry-template.xlsx');
        }

        $type = LivestockType::where('slug', $kind)->with('fields', 'units')->firstOrFail();
        $headers = app(LivestockSheetImporter::class)->headers($type);
        $book = SheetTools::workbook(Str::limit($type->name, 28, ''), $headers, [], [
            "How to fill the {$type->name} records sheet",
            '',
            "One row per {$type->unit_label} per day. Put the {$type->unit_label} name in the first column.",
            'Leave a cell blank when there is nothing to record. Importing the same day again updates that day.',
        ], [$type->unit_label => $type->units->pluck('name')->all()]);

        return SheetTools::download($book, 'poultrilic-' . $type->slug . '-template.xlsx');
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'kind' => 'required|string',
            'file' => 'required|file|max:20480|mimes:xlsx,xls,csv,ods',
            'flock_id' => 'nullable|integer|exists:flocks,id',
            'unit_id' => 'nullable|integer|exists:livestock_units,id',
            'create_missing' => 'nullable|boolean',
        ]);

        $path = $request->file('file')->store('imports', 'local');
        $batch = ImportBatch::create([
            'kind' => $data['kind'] === 'poultry' ? 'poultry' : 'livestock',
            'target_id' => $data['kind'] === 'poultry' ? null : LivestockType::where('slug', $data['kind'])->value('id'),
            'filename' => $request->file('file')->getClientOriginalName(),
            'stored_path' => $path,
            'status' => 'previewed',
            'summary' => ['options' => [
                'flock_id' => $data['flock_id'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'create_missing' => (bool) ($data['create_missing'] ?? false),
            ]],
            'user_id' => $request->user()?->id,
        ]);

        return redirect()->route('imports.show', $batch);
    }

    public function show(ImportBatch $batch)
    {
        $parsed = $this->parse($batch);

        return view('imports.preview', [
            'pagetitle' => 'Check the import',
            'batch' => $batch,
            'parsed' => $parsed,
            'type' => $batch->kind === 'livestock' ? LivestockType::find($batch->target_id) : null,
        ]);
    }

    public function commit(ImportBatch $batch, Request $request)
    {
        abort_if($batch->status === 'imported', 409, 'This file has already been imported.');
        $opts = $batch->summary['options'] ?? [];
        $create = (bool) ($opts['create_missing'] ?? false);
        $parsed = $this->parse($batch);

        if ($batch->kind === 'poultry') {
            $stats = app(FlockSheetImporter::class)->commit($parsed, $create);
        } else {
            $stats = app(LivestockSheetImporter::class)->commit(LivestockType::findOrFail($batch->target_id), $parsed, $create, $request->user()?->id);
        }

        $batch->update(['status' => 'imported', 'summary' => array_merge($batch->summary ?? [], ['result' => $stats])]);

        $msg = sprintf('%d records added, %d updated%s.', $stats['created'], $stats['updated'],
            $stats['skipped'] ? ", {$stats['skipped']} skipped" : '');
        if (! empty($stats['flocks_created']) || ! empty($stats['units_created'])) {
            $msg .= ' ' . ($stats['flocks_created'] ?? $stats['units_created']) . ' new ' . ($batch->kind === 'poultry' ? 'flock(s)' : 'unit(s)') . ' created.';
        }

        return redirect()->route('imports.index')->with($stats['errors'] ? 'warning' : 'success', $msg . ($stats['errors'] ? ' See the import history for the rows that were skipped.' : ''));
    }

    private function parse(ImportBatch $batch): array
    {
        $opts = $batch->summary['options'] ?? [];
        $file = Storage::disk('local')->path($batch->stored_path);
        abort_unless(is_file($file), 410, 'The uploaded file is no longer available. Upload it again.');

        if ($batch->kind === 'poultry') {
            return app(FlockSheetImporter::class)->parse($file, $opts['flock_id'] ?? null, (bool) ($opts['create_missing'] ?? false));
        }

        return app(LivestockSheetImporter::class)->parse(LivestockType::findOrFail($batch->target_id), $file, $opts['unit_id'] ?? null, (bool) ($opts['create_missing'] ?? false));
    }
}
