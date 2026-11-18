@extends('layouts.page')
@php
    use App\Models\LivestockField as F;
@endphp
@section('page')
    @include('partials.page-header', ['title' => "Set up {$type->name}", 'subtitle' => 'What is recorded for each ' . strtolower($type->unit_label) . ', and how it adds up.',
        'crumbs' => ['Livestock setup' => route('livestock.types.index'), $type->name => null],
        'actions' => '<a href="' . route('livestock.index', $type) . '" class="btn btn-sm btn-primary">Go to ' . e($type->name) . '</a>'])

    <div class="row g-3">
        <div class="col-xl-4">
            <form method="POST" action="{{ route('livestock.types.update', $type) }}" class="card">
                @csrf @method('PUT')
                <div class="card-header"><h5 class="card-title mb-0">Details</h5></div>
                <div class="card-body">
                    <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $type->name) }}" required></div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Unit is a…</label><input name="unit_label" class="form-control" value="{{ old('unit_label', $type->unit_label) }}" required></div>
                        <div class="col"><label class="form-label">Animals called</label><input name="count_label" class="form-control" value="{{ old('count_label', $type->count_label) }}" required></div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-8"><label class="form-label">Icon <a href="https://remixicon.com" target="_blank" rel="noopener" class="fs-12">(Remix icon name)</a></label><input name="icon" class="form-control" value="{{ old('icon', $type->icon) }}"></div>
                        <div class="col-4"><label class="form-label">Colour</label><input type="color" name="color" class="form-control form-control-color w-100" value="{{ old('color', $type->color) }}"></div>
                    </div>
                    <div class="mb-2"><label class="form-label">Description</label><textarea name="description" rows="2" class="form-control">{{ old('description', $type->description) }}</textarea></div>
                    <div class="form-check form-switch"><input type="checkbox" name="active" value="1" class="form-check-input" id="act" @checked($type->active)><label class="form-check-label" for="act">Show in the menu</label></div>
                </div>
                <div class="card-footer d-flex">
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
            <form method="POST" action="{{ route('livestock.types.destroy', $type) }}" data-confirm="Delete {{ $type->name }}?">@csrf @method('DELETE')
                <button class="btn btn-link text-danger px-0">Delete this type</button>
            </form>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Record form</h5></div>
                <div class="card-body p-0">
                    <p class="text-muted fs-13 px-3 pt-3">Every field appears on the daily record form, in the Excel template and in reports. <strong>Head count</strong> says whether a field changes how many {{ strtolower($type->count_label) }} a {{ strtolower($type->unit_label) }} has.</p>
                    @forelse ($type->fields as $f)
                        <form method="POST" action="{{ route('livestock.fields.update', [$type, $f]) }}" class="border-top px-3 py-2">
                            @csrf @method('PUT')
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3"><label class="form-label fs-12 mb-0">Label</label><input name="label" class="form-control form-control-sm" value="{{ $f->label }}" required></div>
                                <div class="col-md-2"><label class="form-label fs-12 mb-0">Kind</label>
                                    <select name="kind" class="form-select form-select-sm">@foreach (F::KINDS as $k => $l)<option value="{{ $k }}" @selected($f->kind === $k)>{{ $l }}</option>@endforeach</select></div>
                                <div class="col-md-1"><label class="form-label fs-12 mb-0">Unit</label><input name="unit" class="form-control form-control-sm" value="{{ $f->unit }}"></div>
                                <div class="col-md-2"><label class="form-label fs-12 mb-0">Totals as</label>
                                    <select name="aggregate" class="form-select form-select-sm">@foreach (F::AGGREGATES as $k => $l)<option value="{{ $k }}" @selected($f->aggregate === $k)>{{ $l }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label fs-12 mb-0">Head count</label>
                                    <select name="headcount" class="form-select form-select-sm">@foreach (F::HEADCOUNT as $k => $l)<option value="{{ $k }}" @selected($f->headcount === $k) title="{{ $l }}">{{ \Illuminate\Support\Str::before($l, ' (') }}</option>@endforeach</select></div>
                                <div class="col-md-2 d-flex gap-1">
                                    <input type="number" name="sort" class="form-control form-control-sm" value="{{ $f->sort }}" title="Order" style="width: 56px">
                                    <button class="btn btn-sm btn-soft-primary" title="Save"><i class="ri-save-line"></i></button>
                                    <button form="del{{ $f->id }}" class="btn btn-sm btn-soft-danger" title="Remove"><i class="ri-delete-bin-line"></i></button>
                                </div>
                                @if ($f->kind === 'select')
                                    <div class="col-12"><input name="options" class="form-control form-control-sm" value="{{ implode(', ', $f->options ?? []) }}" placeholder="Choices, separated by commas"></div>
                                @else
                                    <input type="hidden" name="options" value="">
                                @endif
                                <div class="col-12 d-flex gap-3 fs-12 text-muted">
                                    <span>key: <code>{{ $f->key }}</code></span>
                                    <label class="mb-0"><input type="checkbox" name="required" value="1" @checked($f->required)> required</label>
                                    <input name="help" class="form-control form-control-sm border-0 p-0 fs-12" value="{{ $f->help }}" placeholder="Help text shown under the field (optional)">
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('livestock.fields.destroy', [$type, $f]) }}" id="del{{ $f->id }}" data-confirm="Remove the field {{ $f->label }}?" class="d-none">@csrf @method('DELETE')</form>
                    @empty
                        <p class="px-3 text-muted">No fields yet. Add the first one below.</p>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('livestock.fields.store', $type) }}" class="card">
                @csrf
                <div class="card-header"><h5 class="card-title mb-0">Add a field</h5></div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4"><label class="form-label">Label</label><input name="label" class="form-control" placeholder="e.g. Deaths, Milk, Water pH" required></div>
                        <div class="col-md-3"><label class="form-label">Kind</label><select name="kind" class="form-select">@foreach (F::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Unit</label><input name="unit" class="form-control" placeholder="kg, litres"></div>
                        <div class="col-md-3"><label class="form-label">Totals as</label><select name="aggregate" class="form-select">@foreach (F::AGGREGATES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Effect on head count</label><select name="headcount" class="form-select">@foreach (F::HEADCOUNT as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">Choices (for "Choice from a list")</label><input name="options" class="form-control" placeholder="Comma separated"></div>
                        <div class="col-12 d-flex align-items-center gap-3">
                            <label class="mb-0"><input type="checkbox" name="required" value="1"> Required</label>
                            <button class="btn btn-primary ms-auto"><i class="ri-add-line align-bottom"></i> Add field</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
