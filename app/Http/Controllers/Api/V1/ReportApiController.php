<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ReportDefinition;
use App\Services\ReportRunner;
use Illuminate\Http\Request;

class ReportApiController extends ApiController
{
    public function index()
    {
        $this->authorizeTo('View reports');

        return $this->ok(ReportDefinition::orderByDesc('pinned')->orderBy('name')->get()->map(fn ($r) => [
            'id' => $r->id, 'name' => $r->name, 'description' => $r->description, 'source' => $r->source,
            'source_label' => ReportRunner::sources()[$r->source] ?? $r->source, 'group_by' => $r->group_by,
            'chart' => $r->chart, 'period' => $r->period, 'pinned' => $r->pinned,
            'pdf_url' => route('reports.export', [$r, 'pdf']), 'xlsx_url' => route('reports.export', [$r, 'xlsx']),
        ])->all());
    }

    public function show(Request $request, ReportDefinition $report, ReportRunner $runner)
    {
        $this->authorizeTo('View reports');
        if ($request->filled('period') && array_key_exists($request->input('period'), ReportDefinition::PERIODS)) {
            $report->period = $request->input('period');
            $report->date_from = $request->input('from');
            $report->date_to = $request->input('to');
        }
        $r = $runner->run($report);

        return $this->ok([
            'id' => $report->id, 'name' => $report->name, 'from' => $r['from'], 'to' => $r['to'],
            'metrics' => array_map(fn ($m) => ['key' => $m, 'label' => $r['catalogue'][$m][0], 'unit' => $r['catalogue'][$m][1], 'format' => $r['catalogue'][$m][2]], $r['metrics']),
            'rows' => $r['rows'], 'total' => $r['total'], 'chart' => $r['chart'], 'chart_type' => $report->chart,
        ]);
    }
}
