{{-- Period (+ optional flock / unit) filter: one row above the charts. Vars: $period, $from, $to, optional $entities (id => name), $selected, $entityName --}}
@php($periods = \App\Models\ReportDefinition::PERIODS)
<form method="GET" class="card pl-filter mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-sm-auto">
                <label class="form-label fs-12 text-muted mb-1">Period</label>
                <select name="period" class="form-select form-select-sm" onchange="this.form.querySelectorAll('.custom-dates').forEach(e => e.classList.toggle('d-none', this.value !== 'custom')); if (this.value !== 'custom') this.form.submit();">
                    @foreach ($periods as $k => $label)
                        <option value="{{ $k }}" @selected($period === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-auto custom-dates {{ $period === 'custom' ? '' : 'd-none' }}">
                <label class="form-label fs-12 text-muted mb-1">From</label>
                <input type="date" name="from" value="{{ request('from', $from) }}" class="form-control form-control-sm">
            </div>
            <div class="col-sm-auto custom-dates {{ $period === 'custom' ? '' : 'd-none' }}">
                <label class="form-label fs-12 text-muted mb-1">To</label>
                <input type="date" name="to" value="{{ request('to', $to) }}" class="form-control form-control-sm">
            </div>
            @isset($entities)
                <div class="col-sm">
                    <label class="form-label fs-12 text-muted mb-1">{{ $entityName ?? 'Flocks' }}</label>
                    <select name="{{ $entityField ?? 'flocks' }}[]" class="form-select form-select-sm" multiple size="1" style="min-height: 31px" onchange="this.size = 1">
                        @foreach ($entities as $id => $name)
                            <option value="{{ $id }}" @selected(in_array($id, $selected ?? []))>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endisset
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-primary"><i class="ri-filter-3-line align-bottom"></i> Apply</button>
                @if (request()->hasAny(['period', 'flocks', 'from']))
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-light">Reset</a>
                @endif
            </div>
            <div class="col-sm-auto ms-auto text-muted fs-12 align-self-center">
                @if ($from || $to)
                    {{ $from ? \Carbon\Carbon::parse($from)->format('j M Y') : 'First record' }} – {{ $to ? \Carbon\Carbon::parse($to)->format('j M Y') : 'latest' }}
                @else
                    All records
                @endif
            </div>
        </div>
    </div>
</form>
