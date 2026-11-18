@extends('layouts.page')
@section('page')
    @include('partials.page-header', ['icon' => 'ri-leaf-line', 'title' => 'Livestock & farm units', 'subtitle' => 'Set up anything else the farm keeps: cattle, goats, fish ponds, pigs, crops. Each gets its own record form, Excel import and reports.',
        'crumbs' => ['Settings' => route('settings.index'), 'Livestock setup' => null]])

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Your farm units</h5></div>
                <div class="list-group list-group-flush">
                    @forelse ($types as $t)
                        <div class="list-group-item d-flex align-items-center gap-3">
                            <span class="avatar-xs flex-shrink-0"><span class="avatar-title rounded fs-5" style="background: {{ $t->color }}22; color: {{ $t->color }}"><i class="{{ $t->icon }}"></i></span></span>
                            <div class="flex-grow-1">
                                <div class="fw-medium">{{ $t->name }} @unless ($t->active)<span class="badge bg-light text-muted">Off</span>@endunless</div>
                                <div class="text-muted fs-12">{{ $t->units_count }} {{ strtolower(\Illuminate\Support\Str::plural($t->unit_label, $t->units_count)) }} · {{ $t->fields_count }} fields</div>
                            </div>
                            <a href="{{ route('livestock.index', $t) }}" class="btn btn-sm btn-soft-secondary">Open</a>
                            <a href="{{ route('livestock.types.edit', $t) }}" class="btn btn-sm btn-soft-primary">Set up</a>
                        </div>
                    @empty
                        <div class="list-group-item text-muted py-4 text-center">Nothing yet. Start from a template on the right, or create your own.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Start from a template</h5></div>
                <div class="list-group list-group-flush">
                    @foreach ($templates as $key => $tpl)
                        <form method="POST" action="{{ route('livestock.types.store') }}" class="list-group-item d-flex align-items-center gap-2">
                            @csrf <input type="hidden" name="template" value="{{ $key }}">
                            <i class="{{ $tpl['type']['icon'] }} fs-4" style="color: {{ $tpl['type']['color'] }}"></i>
                            <div class="flex-grow-1"><div class="fw-medium">{{ $tpl['type']['name'] }}</div><div class="text-muted fs-12">{{ $tpl['type']['description'] }}</div></div>
                            <button class="btn btn-sm btn-light">Add</button>
                        </form>
                    @endforeach
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Or create your own</h5></div>
                <form method="POST" action="{{ route('livestock.types.store') }}" class="card-body">
                    @csrf
                    <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" placeholder="e.g. Snails, Rabbits, Bee hives" required></div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Each unit is a…</label><input name="unit_label" class="form-control" placeholder="Pen, Hutch, Hive" value="Pen" required></div>
                        <div class="col"><label class="form-label">The animals are called</label><input name="count_label" class="form-control" placeholder="Rabbits" value="Animals" required></div>
                    </div>
                    <button class="btn btn-primary">Create and add fields</button>
                </form>
            </div>
        </div>
    </div>
@endsection
