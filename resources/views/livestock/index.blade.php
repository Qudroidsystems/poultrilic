@extends('layouts.page')
@php
    use App\Services\LivestockMetrics as LM;
    $fmt = fn ($k, $row) => LM::format($catalogue[$k][2] ?? 'dec2', $row[$k] ?? null);
    $numeric = collect($catalogue)->keys()->filter(fn ($k) => str_starts_with($k, 'field:'))->values();
    $chartKeys = $numeric->take(3)->all();
@endphp
@section('page')
    @include('partials.page-header', ['icon' => $type->icon, 
        'title' => $type->name,
        'subtitle' => $type->description,
        'crumbs' => ['Overview' => route('poultry.overview'), $type->name => null],
        'actions' => (auth()->user()->can('Import records') ? '<a href="' . route('imports.index') . '" class="btn btn-sm btn-soft-primary"><i class="ri-file-excel-2-line align-bottom me-1"></i>Import</a> ' : '')
            . '<a href="' . route('livestock.export', $type) . '" class="btn btn-sm btn-soft-success"><i class="ri-download-2-line align-bottom me-1"></i>Excel</a> '
            . (auth()->user()->can('Manage livestock types') ? '<a href="' . route('livestock.types.edit', $type) . '" class="btn btn-sm btn-light"><i class="ri-settings-3-line align-bottom"></i> Set up</a>' : ''),
    ])

    @include('partials.period-filter')

    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => $type->count_label . ' now', 'value' => number_format($units->where('status', 'active')->sum('current_count')), 'sub' => $units->where('status', 'active')->count() . ' active ' . strtolower(\Illuminate\Support\Str::plural($type->unit_label)), 'icon' => $type->icon, 'tone' => 'success'])</div>
        @foreach ($numeric->take(3) as $k)
            <div class="col-6 col-xl-3">@include('partials.kpi', ['label' => $catalogue[$k][0], 'value' => $fmt($k, $summary) . ($catalogue[$k][1] && $catalogue[$k][2] !== 'money' ? ' ' . $catalogue[$k][1] : ''), 'sub' => 'in the period'])</div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header d-flex align-items-center"><h5 class="card-title mb-0 flex-grow-1">{{ \Illuminate\Support\Str::plural($type->unit_label) }}</h5>
                    @can('Manage livestock units')<button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#newUnit"><i class="ri-add-line align-bottom"></i> Add {{ strtolower($type->unit_label) }}</button>@endcan</div>
                @can('Manage livestock units')
                    <form method="POST" action="{{ route('livestock.units.store', $type) }}" class="collapse border-bottom {{ $units->isEmpty() ? 'show' : '' }}" id="newUnit">
                        @csrf
                        <div class="card-body row g-2">
                            <div class="col-7"><label class="form-label">Name</label><input name="name" class="form-control" placeholder="{{ $type->unit_label }} 1" required></div>
                            <div class="col-5"><label class="form-label">{{ $type->count_label }} at start</label><input type="number" min="0" name="opening_count" class="form-control" value="0" required></div>
                            <div class="col-6"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}"></div>
                            <div class="col-6"><label class="form-label">Breed / species</label><input name="breed" class="form-control"></div>
                            <div class="col-12"><button class="btn btn-success btn-sm">Save</button></div>
                        </div>
                    </form>
                @endcan
                <div class="list-group list-group-flush">
                    @forelse ($units as $u)
                        <a href="{{ route('livestock.units.show', [$type, $u]) }}" class="list-group-item list-group-item-action d-flex align-items-center">
                            <div class="flex-grow-1">
                                <div class="fw-medium">{{ $u->name }} @if ($u->status !== 'active')<span class="badge bg-light text-muted">Closed</span>@endif</div>
                                <div class="text-muted fs-12">{{ $u->breed }} {{ $u->records_count }} records · last {{ $u->last_record ? \Carbon\Carbon::parse($u->last_record)->format('j M Y') : 'never' }}</div>
                            </div>
                            <div class="text-end"><div class="fw-semibold">{{ number_format($u->current_count) }}</div><div class="text-muted fs-12">{{ strtolower($type->count_label) }}</div></div>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">No {{ strtolower(\Illuminate\Support\Str::plural($type->unit_label)) }} yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">By month</h5></div>
                <div class="card-body">
                    @if ($chartKeys)
                        @include('partials.chart', ['id' => 'lsTrend', 'type' => 'bar', 'labels' => array_column($trend, 'label'),
                            'series' => [['name' => $catalogue[$chartKeys[0]][0], 'data' => array_column($trend, $chartKeys[0])]], 'unit' => $catalogue[$chartKeys[0]][1], 'height' => 260])
                    @else
                        <p class="text-muted mb-0">Add number fields in Set up to see totals here.</p>
                    @endif
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Totals by {{ strtolower($type->unit_label) }}</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>{{ $type->unit_label }}</th><th class="text-end">Days</th><th class="text-end">At start</th><th class="text-end">At end</th>
                            @foreach ($numeric as $k)<th class="text-end">{{ $catalogue[$k][0] }}{{ $catalogue[$k][1] ? ' (' . $catalogue[$k][1] . ')' : '' }}</th>@endforeach</tr></thead>
                        <tbody>
                            @forelse ($byUnit as $row)
                                <tr><td>{{ $row['label'] }}</td><td class="text-end">{{ $row['records'] }}</td><td class="text-end">{{ number_format($row['head_start']) }}</td><td class="text-end">{{ number_format($row['head_end']) }}</td>
                                    @foreach ($numeric as $k)<td class="text-end">{{ $fmt($k, $row) }}</td>@endforeach</tr>
                            @empty
                                <tr><td colspan="{{ 4 + $numeric->count() }}" class="text-center text-muted py-3">No records in this period.</td></tr>
                            @endforelse
                            @if (count($byUnit) > 1)
                                <tr class="fw-semibold table-light"><td>All</td><td class="text-end">{{ $summary['records'] }}</td><td class="text-end">{{ number_format($summary['head_start']) }}</td><td class="text-end">{{ number_format($summary['head_end']) }}</td>
                                    @foreach ($numeric as $k)<td class="text-end">{{ $fmt($k, $summary) }}</td>@endforeach</tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
