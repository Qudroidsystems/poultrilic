@extends('layouts.page')
@php
    use App\Services\LivestockMetrics as LM;
    $fmt = fn ($k, $row) => LM::format($catalogue[$k][2] ?? 'dec2', $row[$k] ?? null);
    $numeric = collect($catalogue)->keys()->filter(fn ($k) => str_starts_with($k, 'field:'))->values();
    $canRecord = auth()->user()->can('Create livestock record');
    $vals = $edit?->values ?? [];
@endphp
@section('page')
    @include('partials.page-header', [
        'title' => $unit->name,
        'subtitle' => collect([$type->unit_label, $unit->breed, number_format($unit->current_count) . ' ' . strtolower($type->count_label) . ' now', $unit->start_date ? 'since ' . $unit->start_date->format('j M Y') : null, $unit->status === 'active' ? null : 'Closed'])->filter()->implode(' · '),
        'crumbs' => [$type->name => route('livestock.index', $type), $unit->name => null],
    ])

    <div class="row g-3">
        <div class="col-xl-4">
            @if ($canRecord)
                <form method="POST" action="{{ route('livestock.records.store', [$type, $unit]) }}" class="card">
                    @csrf
                    @if ($edit)<input type="hidden" name="record_id" value="{{ $edit->id }}">@endif
                    <div class="card-header d-flex align-items-center"><h5 class="card-title mb-0 flex-grow-1">{{ $edit ? 'Edit record' : 'Record a day' }}</h5>
                        @if ($edit)<a href="{{ route('livestock.units.show', [$type, $unit]) }}" class="btn btn-sm btn-light">New</a>@endif</div>
                    <div class="card-body">
                        <div class="mb-2"><label class="form-label">Date</label>
                            <input type="date" name="record_date" class="form-control" value="{{ old('record_date', $edit?->record_date?->toDateString() ?? now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
                        @foreach ($type->fields as $f)
                            @php($name = "v[{$f->key}]")
                            @php($val = old("v.{$f->key}", $vals[$f->key] ?? null))
                            <div class="mb-2">
                                <label class="form-label mb-1">{{ $f->displayLabel() }} @if ($f->required)<span class="text-danger">*</span>@endif
                                    @if ($f->headcount === 'add')<span class="badge bg-success-subtle text-success ms-1">+ {{ strtolower($type->count_label) }}</span>@elseif ($f->headcount === 'subtract')<span class="badge bg-danger-subtle text-danger ms-1">− {{ strtolower($type->count_label) }}</span>@elseif ($f->headcount === 'set')<span class="badge bg-info-subtle text-info ms-1">count</span>@endif</label>
                                @switch($f->kind)
                                    @case('select')
                                        <select name="{{ $name }}" class="form-select form-select-sm"><option value=""></option>@foreach ($f->options ?? [] as $o)<option @selected((string) $val === (string) $o)>{{ $o }}</option>@endforeach</select>
                                        @break
                                    @case('yes_no')
                                        <select name="{{ $name }}" class="form-select form-select-sm"><option value=""></option><option value="1" @selected($val === true || $val === '1')>Yes</option><option value="0" @selected($val === false || $val === '0')>No</option></select>
                                        @break
                                    @case('text')
                                        <input type="text" name="{{ $name }}" class="form-control form-control-sm" value="{{ $val }}">
                                        @break
                                    @case('date')
                                        <input type="date" name="{{ $name }}" class="form-control form-control-sm" value="{{ $val }}">
                                        @break
                                    @default
                                        <input type="number" name="{{ $name }}" class="form-control form-control-sm" value="{{ $val }}" min="0" step="{{ $f->kind === 'number' ? '1' : '0.01' }}" inputmode="decimal">
                                @endswitch
                                @if ($f->help)<div class="form-text">{{ $f->help }}</div>@endif
                            </div>
                        @endforeach
                        <div class="mb-0"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $edit?->notes) }}</textarea></div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary w-100">Save record</button></div>
                </form>
            @endif

            @can('Manage livestock units')
                <form method="POST" action="{{ route('livestock.units.update', [$type, $unit]) }}" class="card">
                    @csrf @method('PUT')
                    <div class="card-header"><h6 class="card-title mb-0">{{ $type->unit_label }} details</h6></div>
                    <div class="card-body row g-2">
                        <div class="col-12"><input name="name" class="form-control form-control-sm" value="{{ $unit->name }}" required></div>
                        <div class="col-6"><label class="form-label fs-12 mb-0">{{ $type->count_label }} at start</label><input type="number" name="opening_count" min="0" class="form-control form-control-sm" value="{{ $unit->opening_count }}"></div>
                        <div class="col-6"><label class="form-label fs-12 mb-0">Start date</label><input type="date" name="start_date" class="form-control form-control-sm" value="{{ $unit->start_date?->toDateString() }}"></div>
                        <div class="col-6"><label class="form-label fs-12 mb-0">Breed</label><input name="breed" class="form-control form-control-sm" value="{{ $unit->breed }}"></div>
                        <div class="col-6"><label class="form-label fs-12 mb-0">Status</label><select name="status" class="form-select form-select-sm"><option value="active">Active</option><option value="closed" @selected($unit->status === 'closed')>Closed</option></select></div>
                        <div class="col-12 d-flex"><button class="btn btn-sm btn-soft-primary">Save</button>
                            <button form="delUnit" class="btn btn-sm btn-link text-danger ms-auto">Delete</button></div>
                    </div>
                </form>
                <form id="delUnit" method="POST" action="{{ route('livestock.units.destroy', [$type, $unit]) }}" data-confirm="Delete {{ $unit->name }} and all its records?" class="d-none">@csrf @method('DELETE')</form>
            @endcan
        </div>

        <div class="col-xl-8">
            @include('partials.period-filter')
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">@include('partials.kpi', ['label' => $type->count_label . ' at end', 'value' => number_format($summary['head_end']), 'sub' => 'from ' . number_format($summary['head_start']) . ($summary['change_pct'] !== null ? ' (' . ($summary['change_pct'] > 0 ? '+' : '') . $summary['change_pct'] . '%)' : '')])</div>
                @foreach ($numeric->take(3) as $k)
                    <div class="col-6 col-lg-3">@include('partials.kpi', ['label' => $catalogue[$k][0], 'value' => $fmt($k, $summary) . ($catalogue[$k][1] && $catalogue[$k][2] !== 'money' ? ' ' . $catalogue[$k][1] : '')])</div>
                @endforeach
            </div>
            @if (count($monthly) > 1 && $numeric->isNotEmpty())
                <div class="card"><div class="card-body">
                    @include('partials.chart', ['id' => 'uTrend', 'type' => 'line', 'labels' => array_column($monthly, 'label'), 'series' => [['name' => $type->count_label, 'data' => array_column($monthly, 'head_end')]], 'unit' => '', 'height' => 220])
                </div></div>
            @endif
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Records</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Date</th>@foreach ($type->fields as $f)<th class="{{ $f->isNumeric() ? 'text-end' : '' }}">{{ $f->label }}</th>@endforeach<th class="text-end">{{ $type->count_label }}</th><th>Notes</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($records as $r)
                                <tr>
                                    <td class="text-nowrap">{{ $r->record_date->format('D j M Y') }}</td>
                                    @foreach ($type->fields as $f)<td class="{{ $f->isNumeric() ? 'text-end' : '' }}">{{ $f->format($r->values[$f->key] ?? null) }}</td>@endforeach
                                    <td class="text-end fw-medium">{{ number_format($r->count_after) }}</td>
                                    <td class="text-muted fs-12">{{ $r->notes }}</td>
                                    <td class="text-end text-nowrap">
                                        @can('Update livestock record')<a href="{{ route('livestock.units.show', [$type, $unit, 'edit' => $r->id]) }}" class="btn btn-sm btn-soft-secondary" title="Edit"><i class="ri-pencil-line"></i></a>@endcan
                                        @can('Delete livestock record')
                                            <form method="POST" action="{{ route('livestock.records.destroy', [$type, $unit, $r]) }}" class="d-inline" data-confirm="Delete the record for {{ $r->record_date->format('j M Y') }}?">@csrf @method('DELETE')
                                                <button class="btn btn-sm btn-soft-danger" title="Delete"><i class="ri-delete-bin-line"></i></button></form>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $type->fields->count() + 4 }}" class="text-center text-muted py-3">No records in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($records->hasPages())<div class="card-footer">{{ $records->links() }}</div>@endif
            </div>
        </div>
    </div>
@endsection
