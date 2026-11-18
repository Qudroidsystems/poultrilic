@extends('layouts.page')
@section('page')
    @include('partials.page-header', ['icon' => 'ri-bar-chart-2-line', 'title' => 'Reports', 'subtitle' => 'The farm performance report, plus reports you design: pick the figures, how to group them and the period.',
        'crumbs' => ['Overview' => route('poultry.overview'), 'Reports' => null],
        'actions' => auth()->user()->can('Manage reports') ? '<a href="' . route('reports.create') . '" class="btn btn-primary btn-sm"><i class="ri-add-line align-bottom"></i> New report</a>' : ''])

    <div class="row g-3">
        <div class="col-xl-4">
            <a href="{{ route('reports.performance') }}" class="card card-animate text-body">
                <div class="card-body d-flex gap-3">
                    <span class="avatar-sm flex-shrink-0"><span class="avatar-title bg-primary-subtle text-primary rounded fs-3"><i class="ri-file-chart-line"></i></span></span>
                    <div>
                        <h5 class="mb-1">Farm performance report</h5>
                        <p class="text-muted mb-0 fs-13">Flock status, laying rate, mortality, feed efficiency, data checks, livestock and money, with plain-language findings. Download as PDF.</p>
                    </div>
                </div>
            </a>
        </div>
        @foreach ($reports as $rep)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <a href="{{ route('reports.show', $rep) }}" class="flex-grow-1 text-body">
                                <h6 class="mb-1">{{ $rep->name }} @if ($rep->pinned)<i class="ri-pushpin-fill text-warning" title="Pinned to the overview"></i>@endif</h6>
                                <p class="text-muted fs-12 mb-2">{{ $sources[$rep->source] ?? $rep->source }} · by {{ \App\Services\PoultryMetrics::GROUPS[$rep->group_by] ?? $rep->group_by }} · {{ \App\Models\ReportDefinition::PERIODS[$rep->period] ?? $rep->period }}</p>
                                @if ($rep->description)<p class="fs-13 mb-0">{{ $rep->description }}</p>@endif
                            </a>
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2 py-2">
                        <a href="{{ route('reports.show', $rep) }}" class="btn btn-sm btn-soft-primary">Open</a>
                        <a href="{{ route('reports.export', [$rep, 'xlsx']) }}" class="btn btn-sm btn-soft-success">Excel</a>
                        <a href="{{ route('reports.export', [$rep, 'pdf']) }}" class="btn btn-sm btn-soft-danger">PDF</a>
                        @can('Manage reports')<a href="{{ route('reports.edit', $rep) }}" class="btn btn-sm btn-light ms-auto">Edit</a>@endcan
                    </div>
                </div>
            </div>
        @endforeach
        @if ($reports->isEmpty())
            <div class="col-xl-8">
                <div class="card border-dashed"><div class="card-body text-center py-4">
                    <p class="text-muted">No saved reports yet.</p>
                    @can('Manage reports')
                        <a href="{{ route('reports.index', ['starters' => 1]) }}" class="btn btn-soft-primary">Add starter reports</a>
                        <a href="{{ route('reports.create') }}" class="btn btn-light">Design one</a>
                    @endcan
                </div></div>
            </div>
        @endif
    </div>
@endsection
