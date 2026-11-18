@extends('layouts.page')
@php
    use App\Models\ReportDefinition as R;
    $sel = old('metrics', $report->metrics ?? []);
    $ids = old('ids', $report->filters['ids'] ?? []);
    $isPoultry = $report->source === 'poultry';
@endphp
@section('page')
    @include('partials.page-header', ['icon' => 'ri-equalizer-line', 'title' => $report->exists ? 'Edit report' : 'New report',
        'crumbs' => ['Reports' => route('reports.index'), ($report->exists ? $report->name : 'New') => null]])

    <form method="POST" action="{{ $report->exists ? route('reports.update', $report) : route('reports.store') }}">
        @csrf @if ($report->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-7"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $report->name) }}" placeholder="e.g. Weekly laying by flock" required></div>
                            <div class="col-md-5"><label class="form-label">Report on</label>
                                <select name="source" class="form-select" onchange="window.location = '{{ $report->exists ? route('reports.edit', $report) : route('reports.create') }}?source=' + encodeURIComponent(this.value)">
                                    @foreach ($sources as $k => $label)<option value="{{ $k }}" @selected($report->source === $k)>{{ $label }}</option>@endforeach
                                </select></div>
                            <div class="col-12"><label class="form-label">Description <span class="text-muted">(optional)</span></label><input name="description" class="form-control" value="{{ old('description', $report->description) }}"></div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">Figures to show <span class="text-muted fs-12 fw-normal">(up to 8; the first is charted per {{ $isPoultry ? 'flock' : 'unit' }} when you choose one line each)</span></h5></div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($catalogue as $key => [$label, $unit])
                                <div class="col-md-6 col-xl-4">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="metrics[]" value="{{ $key }}" id="m_{{ md5($key) }}" @checked(in_array($key, $sel))>
                                        <label class="form-check-label fs-13" for="m_{{ md5($key) }}">{{ $label }} @if ($unit)<span class="text-muted">({{ $unit }})</span>@endif</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Group by</label>
                            <select name="group_by" class="form-select">
                                @foreach (['month' => 'Month', 'week' => 'Week', 'day' => 'Day', 'flock' => $isPoultry ? 'Flock' : 'Unit', 'all' => 'Whole period (one row)'] as $k => $l)
                                    <option value="{{ $k }}" @selected(old('group_by', $report->group_by) === $k)>{{ $l }}</option>
                                @endforeach
                            </select></div>
                        <div class="mb-3"><label class="form-label">Period</label>
                            <select name="period" class="form-select" onchange="document.getElementById('customDates').classList.toggle('d-none', this.value !== 'custom')">
                                @foreach (R::PERIODS as $k => $l)<option value="{{ $k }}" @selected(old('period', $report->period) === $k)>{{ $l }}</option>@endforeach
                            </select>
                            <div class="form-text">Relative periods end on the latest record.</div>
                            <div id="customDates" class="row g-2 mt-1 {{ old('period', $report->period) === 'custom' ? '' : 'd-none' }}">
                                <div class="col"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ old('date_from', $report->date_from?->toDateString()) }}"></div>
                                <div class="col"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ old('date_to', $report->date_to?->toDateString()) }}"></div>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label">Chart</label>
                            <select name="chart" class="form-select">@foreach (R::CHARTS as $k => $l)<option value="{{ $k }}" @selected(old('chart', $report->chart) === $k)>{{ $l }}</option>@endforeach</select></div>
                        <div class="mb-3"><label class="form-label">{{ $isPoultry ? 'Flocks' : 'Units' }} <span class="text-muted">(none ticked = all)</span></label>
                            <div style="max-height: 180px; overflow-y: auto" class="border rounded p-2">
                                @forelse ($entities as $id => $name)
                                    <div class="form-check"><input class="form-check-input" type="checkbox" name="ids[]" value="{{ $id }}" id="e{{ $id }}" @checked(in_array($id, $ids))><label class="form-check-label fs-13" for="e{{ $id }}">{{ $name }}</label></div>
                                @empty
                                    <span class="text-muted fs-12">None yet.</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="per_entity" value="1" id="pe" @checked(old('per_entity', $report->per_entity))><label class="form-check-label" for="pe">One line per {{ $isPoultry ? 'flock' : 'unit' }} (first figure)</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="pinned" value="1" id="pin" @checked(old('pinned', $report->pinned))><label class="form-check-label" for="pin">Pin to the farm overview</label></div>
                    </div>
                    <div class="card-footer d-flex">
                        <button class="btn btn-primary">Save and run</button>
                        <a href="{{ $report->exists ? route('reports.show', $report) : route('reports.index') }}" class="btn btn-light ms-2">Cancel</a>
                    </div>
                </div>
                @if ($report->exists)
                    <button form="delRep" class="btn btn-link text-danger px-0">Delete report</button>
                @endif
            </div>
        </div>
    </form>
    @if ($report->exists)
        <form id="delRep" method="POST" action="{{ route('reports.destroy', $report) }}" data-confirm="Delete this report?" class="d-none">@csrf @method('DELETE')</form>
    @endif
@endsection
