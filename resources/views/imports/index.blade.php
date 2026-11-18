@extends('layouts.page')
@section('page')
    @include('partials.page-header', ['icon' => 'ri-file-excel-2-line', 'title' => 'Import from Excel', 'subtitle' => 'Upload a sheet, check what will be saved, then import. Nothing is saved until you confirm.',
        'crumbs' => ['Overview' => route('poultry.overview'), 'Import' => null]])

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1"><i class="ri-hen-line me-1"></i>Poultry daily records</h5>
                    <a href="{{ route('imports.template', 'poultry') }}" class="btn btn-sm btn-soft-success"><i class="ri-download-2-line align-bottom"></i> Template</a>
                </div>
                <form method="POST" action="{{ route('imports.preview') }}" enctype="multipart/form-data" class="card-body">
                    @csrf
                    <input type="hidden" name="kind" value="poultry">
                    <div class="mb-3">
                        <label class="form-label">Excel or CSV file</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.ods" required>
                        <div class="form-text">The template, or the farm's own sheet: columns are matched by name (Date, Flock, Deaths today, Feed today (bags), Feed in store, Eggs laid, Eggs sold, Broken, Bad eggs, Medication…).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Flock</label>
                        <select name="flock_id" class="form-select">
                            <option value="">Use the Flock column in the sheet</option>
                            @foreach ($flocks as $f)
                                <option value="{{ $f->id }}">{{ $f->display_name }}{{ $f->status !== 'active' ? ' (closed)' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Pick one when the sheet is for a single flock and has no Flock column.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="create_missing" value="1" class="form-check-input" id="pcm">
                        <label for="pcm" class="form-check-label">Create flocks that are in the sheet but not in the portal yet</label>
                    </div>
                    <button class="btn btn-primary"><i class="ri-eye-line align-bottom me-1"></i>Check the file</button>
                </form>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><i class="ri-leaf-line me-1"></i>Livestock & other units</h5></div>
                <div class="card-body">
                    @if ($types->isEmpty())
                        <p class="text-muted">No livestock types yet.
                            @can('Manage livestock types')<a href="{{ route('livestock.types.index') }}">Set up cattle, goats, fish ponds…</a>@endcan</p>
                    @else
                        <form method="POST" action="{{ route('imports.preview') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="kind" class="form-select" required onchange="document.querySelectorAll('[data-type-units]').forEach(s => { const off = s.dataset.typeUnits !== this.value; s.classList.toggle('d-none', off); s.disabled = off; }); document.getElementById('ltpl').href = '{{ url('imports/template') }}/' + this.value;">
                                    @foreach ($types as $t)
                                        <option value="{{ $t->slug }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                                <a id="ltpl" href="{{ route('imports.template', $types->first()->slug) }}" class="fs-12"><i class="ri-download-2-line"></i> Download the template for this type</a>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">File</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.ods" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Unit</label>
                                @foreach ($types as $t)
                                    <select name="unit_id" class="form-select {{ $loop->first ? '' : 'd-none' }}" data-type-units="{{ $t->slug }}" @disabled(! $loop->first)>
                                        <option value="">Use the {{ $t->unit_label }} column</option>
                                        @foreach ($t->units as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                                    </select>
                                @endforeach
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="create_missing" value="1" class="form-check-input" id="lcm">
                                <label for="lcm" class="form-check-label">Create units that are not in the portal yet</label>
                            </div>
                            <button class="btn btn-primary"><i class="ri-eye-line align-bottom me-1"></i>Check the file</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h5 class="card-title mb-0">Import history</h5></div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light"><tr><th>When</th><th>File</th><th>Into</th><th>By</th><th>Status</th><th>Result</th></tr></thead>
                <tbody>
                    @forelse ($batches as $b)
                        @php($res = $b->summary['result'] ?? null)
                        <tr>
                            <td class="text-nowrap">{{ $b->created_at->format('j M Y H:i') }}</td>
                            <td>{{ $b->filename }}</td>
                            <td>{{ $b->target_label }}</td>
                            <td>{{ $b->user?->name ?? '–' }}</td>
                            <td>
                                @if ($b->status === 'imported')<span class="badge bg-success-subtle text-success">Imported</span>
                                @else<a href="{{ route('imports.show', $b) }}" class="badge bg-warning-subtle text-warning">Not imported – open</a>@endif
                            </td>
                            <td class="fs-12">
                                @if ($res)
                                    {{ $res['created'] }} added · {{ $res['updated'] }} updated @if ($res['skipped']) · {{ $res['skipped'] }} skipped @endif
                                    @foreach (array_slice($res['errors'] ?? [], 0, 3) as $e)<div class="text-danger">Row {{ $e['row'] }}: {{ $e['message'] }}</div>@endforeach
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">No imports yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
