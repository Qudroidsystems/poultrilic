@extends('layouts.page')
@section('page')
    @include('partials.page-header', ['icon' => 'ri-git-merge-line', 'title' => 'Combine flocks', 'subtitle' => 'For when flocks are moved into one house, e.g. 650 + 770 birds becoming one flock of 1,420.',
        'crumbs' => ['Overview' => route('poultry.overview'), 'Combine flocks' => null]])
    @php($pre = array_map('intval', (array) request('sources', [])))
    <div class="row">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('poultry.merge.store') }}" class="card" data-confirm="Combine these flocks? The flocks being combined will be closed.">
                @csrf
                <div class="card-body">
                    <h6 class="mb-2">1. Flocks being combined</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light"><tr><th></th><th>Flock</th><th class="text-end">Birds now</th><th>Last record</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach ($flocks as $f)
                                    <tr class="{{ $f->status !== 'active' ? 'text-muted' : '' }}">
                                        <td><input type="checkbox" class="form-check-input" name="sources[]" value="{{ $f->id }}" id="s{{ $f->id }}" @checked(in_array($f->id, old('sources', $pre))) @disabled($f->status !== 'active')></td>
                                        <td><label for="s{{ $f->id }}" class="mb-0">{{ $f->display_name }}</label></td>
                                        <td class="text-end">{{ number_format($f->current_bird_count) }}</td>
                                        <td>{{ $f->last_record ? \Carbon\Carbon::parse($f->last_record)->format('j M Y') : '–' }}</td>
                                        <td>{{ ucfirst($f->status) }}{{ $f->merged_into_id ? ' (combined)' : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="mb-2">2. Date they were combined</h6>
                    <div class="mb-3" style="max-width: 220px">
                        <input type="date" name="date" class="form-control" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                        <div class="form-text">The last day of the old flocks' records.</div>
                    </div>

                    <h6 class="mb-2">3. They go into</h6>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="target" value="new" id="tNew" @checked(old('target', 'new') === 'new')>
                        <label class="form-check-label" for="tNew">A new flock, starting with the birds they have alive</label>
                        <input type="text" name="name" class="form-control form-control-sm mt-1" style="max-width: 340px" placeholder="Name, e.g. Merged 1,420 (B+C)" value="{{ old('name') }}">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="target" value="existing" id="tOld" @checked(old('target') === 'existing')>
                        <label class="form-check-label" for="tOld">An existing flock whose records have already started (e.g. after an import)</label>
                        <select name="target_id" class="form-select form-select-sm mt-1" style="max-width: 340px">
                            <option value="">Choose…</option>
                            @foreach ($flocks->where('status', 'active') as $f)
                                <option value="{{ $f->id }}" @selected(old('target_id') == $f->id)>{{ $f->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary"><i class="ri-git-merge-line align-bottom me-1"></i>Combine flocks</button></div>
            </form>
        </div>
        <div class="col-xl-4">
            <div class="card"><div class="card-body fs-13 text-muted">
                <p><strong class="text-body">What happens</strong></p>
                <p>The flocks you tick are closed on the date you give and keep all their records, so their history, reports and data checks stay as they were.</p>
                <p class="mb-0">Farm totals count the birds once: at the start in the old flocks and at the end in the new one.</p>
            </div></div>
        </div>
    </div>
@endsection
