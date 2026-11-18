@extends('layouts.page')
@php
    use App\Models\Flock;
    $canCreate = auth()->user()->can('Create flock');
    $canUpdate = auth()->user()->can('Update flock');
    $canDelete = auth()->user()->can('Delete flock');
@endphp
@section('page')
    @include('partials.page-header', [
        'title' => 'Flocks',
        'subtitle' => ($counts['active'] ?? 0) . ' active · ' . number_format($birdsNow) . ' birds on the farm',
        'crumbs' => ['Overview' => route('poultry.overview'), 'Flocks' => null],
        'actions' => ($canCreate ? '<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#flockModal" data-mode="add"><i class="ri-add-line align-bottom"></i> Add flock</button>' : ''),
    ])

    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <div class="btn-group btn-group-sm" role="group" aria-label="Status">
            @foreach (['active' => 'Active', 'ended' => 'Ended', 'sold' => 'Sold', 'inactive' => 'Inactive', 'all' => 'All'] as $k => $l)
                <a href="{{ route('flocks.index', ['status' => $k, 'search' => $search ?: null]) }}" class="btn {{ $status === $k ? 'btn-primary' : 'btn-light' }}">{{ $l }} @if ($k !== 'all')<span class="opacity-75">{{ $counts[$k] ?? 0 }}</span>@endif</a>
            @endforeach
        </div>
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="search" name="search" value="{{ $search }}" class="form-control form-control-sm" style="max-width: 240px" placeholder="Search name, breed, house">
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Flock</th><th>Type</th><th>Arrived</th><th class="text-end">Age</th><th class="text-end">Birds at start</th><th class="text-end">Deaths</th><th class="text-end">Birds now</th><th>Last record</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($flocks as $f)
                        <tr>
                            <td><a href="{{ route('poultry.flocks.show', $f) }}" class="fw-medium">{{ $f->display_name }}</a>
                                <div class="text-muted fs-12">{{ collect([$f->breed, $f->house])->filter()->implode(' · ') }}</div></td>
                            <td>{{ $f->type_label }}</td>
                            <td>{{ $f->date_of_arrival?->format('j M Y') ?? '–' }}</td>
                            <td class="text-end">{{ $f->age_weeks_now ? $f->age_weeks_now . ' wks' : '–' }}</td>
                            <td class="text-end">{{ number_format($f->initial_bird_count) }}</td>
                            <td class="text-end">{{ number_format($deaths[$f->id] ?? 0) }}</td>
                            <td class="text-end fw-medium">{{ number_format($f->current_bird_count) }}</td>
                            <td>{{ isset($last[$f->id]) ? \Carbon\Carbon::parse($last[$f->id])->format('j M Y') : '–' }}</td>
                            <td>
                                <span class="badge {{ $f->status === 'active' ? 'bg-success-subtle text-success' : 'bg-light text-muted' }}">{{ ucfirst($f->status) }}</span>
                                @if ($f->merged_into_id)<span class="badge bg-light text-muted" title="Combined into another flock"><i class="ri-git-merge-line"></i></span>@endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('week-entries.index', $f->id) }}" class="btn btn-sm btn-soft-primary" title="Daily records"><i class="ri-edit-box-line"></i> Records</a>
                                @if ($canUpdate)
                                    <button class="btn btn-sm btn-soft-secondary" title="Edit" data-bs-toggle="modal" data-bs-target="#flockModal" data-mode="edit" data-flock="{{ json_encode($f->only(['id', 'name', 'breed', 'flock_type', 'house', 'initial_bird_count', 'opening_egg_stock', 'age_in_weeks', 'status', 'notes']) + ['date_of_arrival' => $f->date_of_arrival?->toDateString()]) }}"><i class="ri-pencil-line"></i></button>
                                @endif
                                @if ($canDelete)
                                    <form method="POST" action="{{ route('flocks.destroy', $f) }}" class="d-inline" data-confirm="Delete {{ $f->display_name }} and ALL its daily records? This cannot be undone.">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-soft-danger" title="Delete"><i class="ri-delete-bin-line"></i></button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No flocks here.
                            @if ($canCreate) Add one, or <a href="{{ route('imports.index') }}">import an Excel sheet</a> (flocks can be created from it).@endif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($flocks->hasPages())<div class="card-footer">{{ $flocks->links() }}</div>@endif
    </div>

    @if ($canCreate || $canUpdate)
        <div class="modal fade" id="flockModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form method="POST" action="{{ route('flocks.store') }}" class="modal-content" id="flockForm">
                    @csrf
                    <input type="hidden" name="_method" value="POST" id="flockMethod">
                    <div class="modal-header"><h5 class="modal-title" id="flockModalTitle">Add flock</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" required placeholder="e.g. Layers A, House 2 broilers"></div>
                            <div class="col-md-3"><label class="form-label">Type</label><select name="flock_type" class="form-select">@foreach (Flock::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select">@foreach (Flock::STATUSES as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Breed / strain</label><input name="breed" class="form-control" placeholder="Isa Brown, Arbor Acres"></div>
                            <div class="col-md-4"><label class="form-label">House / pen</label><input name="house" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Arrival date</label><input type="date" name="date_of_arrival" class="form-control"></div>
                            <div class="col-md-4"><label class="form-label">Birds at start</label><input type="number" min="0" name="initial_bird_count" class="form-control" required>
                                <div class="form-text">Birds alive when records begin.</div></div>
                            <div class="col-md-4"><label class="form-label">Age at arrival (weeks)</label><input type="number" min="0" name="age_in_weeks" class="form-control" value="0">
                                <div class="form-text">0 for day-old chicks; 16–18 for point-of-lay.</div></div>
                            <div class="col-md-4"><label class="form-label">Eggs in farm at start</label><input type="number" min="0" name="opening_egg_stock" class="form-control" value="0"></div>
                            <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control"></textarea></div>
                        </div>
                        <p class="text-muted fs-12 mt-3 mb-0">Birds alive, deaths to date and eggs in farm are worked out from the daily records.</p>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save flock</button></div>
                </form>
            </div>
        </div>
        @push('scripts')
        <script>
            document.getElementById('flockModal').addEventListener('show.bs.modal', function (ev) {
                var btn = ev.relatedTarget, form = document.getElementById('flockForm');
                form.reset();
                if (btn && btn.dataset.mode === 'edit') {
                    var f = JSON.parse(btn.dataset.flock);
                    form.action = @json(url('flocks')) + '/' + f.id;
                    document.getElementById('flockMethod').value = 'PUT';
                    document.getElementById('flockModalTitle').textContent = 'Edit ' + (f.name || 'flock');
                    Object.keys(f).forEach(function (k) { var el = form.elements[k]; if (el && f[k] !== null) { el.value = f[k]; } });
                } else {
                    form.action = @json(route('flocks.store'));
                    document.getElementById('flockMethod').value = 'POST';
                    document.getElementById('flockModalTitle').textContent = 'Add flock';
                }
            });
        </script>
        @endpush
    @endif
@endsection
