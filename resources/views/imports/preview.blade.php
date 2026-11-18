@extends('layouts.page')
@php($fieldNames = [
    'flock' => 'Flock', 'entry_date' => 'Date', 'week' => 'Week', 'day' => 'Day', 'daily_mortality' => 'Deaths today', 'total_deaths' => 'Total deaths (checked)',
    'birds_stolen' => 'Stolen today', 'stolen_total' => 'Stolen to date', 'birds_culled' => 'Culled', 'birds_alive' => 'Birds alive (checked)',
    'daily_feeds' => 'Feed fed (bags)', 'available_feeds' => 'Feed in store', 'eggs_produced' => 'Eggs laid', 'eggs_sold' => 'Eggs sold', 'broken_egg' => 'Broken',
    'bad_eggs' => 'Bad eggs', 'eggs_in_farm' => 'Eggs in farm (opening stock)', 'sick_bay' => 'Sick bay', 'drugs' => 'Medication', 'avg_weight_g' => 'Weight (g)',
    'notes' => 'Notes', 'reported_at' => 'Sent on', 'unit' => 'Unit', 'date' => 'Date',
])
@section('page')
    @include('partials.page-header', ['icon' => 'ri-file-search-line', 'title' => 'Check the import', 'subtitle' => $batch->filename . ' → ' . $batch->target_label,
        'crumbs' => ['Import' => route('imports.index'), 'Check' => null]])

    @php($groups = $batch->kind === 'poultry' ? ($parsed['flocks'] ?? []) : ($parsed['units'] ?? []))
    @php($rowCount = count($parsed['rows'] ?? []))

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">{{ number_format($rowCount) }} rows found{{ isset($parsed['sheet']) ? ' on sheet "' . $parsed['sheet'] . '"' : '' }}</h5></div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $batch->kind === 'poultry' ? 'Flock' : ($type->unit_label ?? 'Unit') }}</th><th>From</th><th>To</th><th class="text-end">Rows</th>
                            @if ($batch->kind === 'poultry')<th class="text-end">Updates</th><th class="text-end">Deaths</th><th class="text-end">Eggs laid</th><th class="text-end">Birds at start</th>@endif
                            <th>Action</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($groups as $g)
                                <tr>
                                    <td class="fw-medium">{{ $g['name'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($g['from'])->format('j M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($g['to'])->format('j M Y') }}</td>
                                    <td class="text-end">{{ number_format($g['rows']) }}</td>
                                    @if ($batch->kind === 'poultry')
                                        <td class="text-end">{{ number_format($g['updates']) }}</td>
                                        <td class="text-end">{{ number_format($g['deaths']) }}</td>
                                        <td class="text-end">{{ number_format($g['eggs']) }}</td>
                                        <td class="text-end">{{ number_format($g['initial_birds']) }}</td>
                                        <td>
                                            @if ($g['flock_id'])<span class="badge bg-primary-subtle text-primary">Add to existing flock</span>
                                            @elseif ($g['can_create'])<span class="badge bg-success-subtle text-success">Create flock</span>
                                            @else<span class="badge bg-danger-subtle text-danger">Skipped: no such flock</span>@endif
                                        </td>
                                    @else
                                        <td>
                                            @if ($g['unit_id'])<span class="badge bg-primary-subtle text-primary">Existing</span>
                                            @elseif (! empty($batch->summary['options']['create_missing']))<span class="badge bg-success-subtle text-success">Create</span>
                                            @else<span class="badge bg-danger-subtle text-danger">Skipped</span>@endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">Nothing to import.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($batch->status !== 'imported' && $rowCount)
                    <div class="card-footer d-flex align-items-center gap-2">
                        <form method="POST" action="{{ route('imports.commit', $batch) }}">@csrf
                            <button class="btn btn-success"><i class="ri-upload-cloud-2-line align-bottom me-1"></i>Import {{ number_format($rowCount) }} rows</button>
                        </form>
                        <a href="{{ route('imports.index') }}" class="btn btn-light">Cancel</a>
                        <span class="text-muted fs-12 ms-auto">Days already recorded are updated, not duplicated. Running totals are recalculated afterwards.</span>
                    </div>
                @endif
            </div>

            @if (! empty($parsed['errors']))
                <div class="card border-warning">
                    <div class="card-header bg-warning-subtle"><h5 class="card-title mb-0">{{ count($parsed['errors']) }} thing(s) to check</h5></div>
                    <ul class="list-group list-group-flush">
                        @foreach (array_slice($parsed['errors'], 0, 50) as $e)
                            <li class="list-group-item fs-13">@if ($e['row'])<span class="fw-medium">Row {{ $e['row'] }}:</span> @endif{{ $e['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($batch->kind === 'poultry' && $rowCount)
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0">First rows, as they will be saved</h5></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0 fs-12">
                            <thead class="table-light"><tr><th>Row</th><th>Flock</th><th>Date</th><th class="text-end">Deaths</th><th class="text-end">Stolen</th><th class="text-end">Feed</th><th class="text-end">In store</th><th class="text-end">Laid</th><th class="text-end">Sold</th><th class="text-end">Broken</th><th class="text-end">Bad</th><th>Medication</th></tr></thead>
                            <tbody>
                                @foreach (array_slice($parsed['rows'], 0, 12) as $r)
                                    <tr><td>{{ $r['row'] }}</td><td>{{ $r['flock_name'] }}</td><td>{{ $r['entry_date'] }}</td><td class="text-end">{{ $r['daily_mortality'] ?? '–' }}</td><td class="text-end">{{ $r['birds_stolen'] ?? ($r['stolen_total'] !== null ? '(total ' . $r['stolen_total'] . ')' : '–') }}</td>
                                        <td class="text-end">{{ $r['daily_feeds'] }}</td><td class="text-end">{{ $r['available_feeds'] ?? '–' }}</td><td class="text-end">{{ $r['eggs_produced'] }}</td><td class="text-end">{{ $r['eggs_sold'] }}</td><td class="text-end">{{ $r['broken_egg'] }}</td><td class="text-end">{{ $r['bad_eggs'] }}</td><td>{{ $r['drugs'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Columns recognised</h5></div>
                <ul class="list-group list-group-flush">
                    @forelse ($parsed['mapped'] ?? [] as $field => $header)
                        <li class="list-group-item d-flex justify-content-between fs-13">
                            <span class="text-muted">"{{ $header }}"</span>
                            <span>→ {{ $fieldNames[$field] ?? (str_starts_with($field, 'f:') ? ($type?->fields->firstWhere('key', substr($field, 2))?->label ?? substr($field, 2)) : $field) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">None.</li>
                    @endforelse
                </ul>
                <div class="card-body fs-12 text-muted">Columns not listed are ignored. Totals in the sheet (birds alive, total deaths) are only used to check the daily figures.</div>
            </div>
        </div>
    </div>
@endsection
