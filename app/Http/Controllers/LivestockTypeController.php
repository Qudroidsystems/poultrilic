<?php

namespace App\Http\Controllers;

use App\Models\LivestockField;
use App\Models\LivestockType;
use App\Support\LivestockTemplates;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** The livestock builder: the admin defines farm enterprises and what is recorded for them. */
class LivestockTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Manage livestock types');
    }

    public function index()
    {
        return view('livestock.types.index', [
            'pagetitle' => 'Livestock & farm units setup',
            'types' => LivestockType::withCount(['units', 'fields'])->orderBy('sort')->get(),
            'templates' => LivestockTemplates::TEMPLATES,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->filled('template')) {
            $request->validate(['template' => ['required', Rule::in(array_keys(LivestockTemplates::TEMPLATES))]]);
            $type = LivestockTemplates::install($request->input('template'));

            return redirect()->route('livestock.types.edit', $type)->with('success', "{$type->name} added with a ready-made record form. Change any field to suit your farm.");
        }

        $data = $request->validate($this->typeRules());
        $type = LivestockType::create($data + ['active' => true, 'sort' => LivestockType::max('sort') + 1]);

        return redirect()->route('livestock.types.edit', $type)->with('success', "{$type->name} created. Now add the fields you want to record.");
    }

    public function edit(LivestockType $type)
    {
        return view('livestock.types.edit', [
            'pagetitle' => "Set up {$type->name}",
            'type' => $type->load('fields'),
        ]);
    }

    public function update(Request $request, LivestockType $type)
    {
        $data = $request->validate($this->typeRules() + ['active' => 'nullable|boolean']);
        $data['active'] = $request->boolean('active');
        $type->update($data);

        return back()->with('success', 'Saved.');
    }

    public function destroy(LivestockType $type)
    {
        if ($type->units()->exists()) {
            return back()->withErrors(['type' => "{$type->name} still has " . strtolower($type->unit_label) . 's. Delete them first, or switch the type off instead.']);
        }
        $type->delete();

        return redirect()->route('livestock.types.index')->with('success', 'Deleted.');
    }

    public function storeField(Request $request, LivestockType $type)
    {
        $data = $this->fieldData($request, $type);
        $data['key'] = $this->uniqueKey($type, Str::snake(Str::ascii($data['label'])));
        $data['sort'] = (int) $type->fields()->max('sort') + 1;
        $type->fields()->create($data);

        return back()->with('success', "Field \"{$data['label']}\" added.");
    }

    public function updateField(Request $request, LivestockType $type, LivestockField $field)
    {
        abort_unless($field->livestock_type_id === $type->id, 404);
        $data = $this->fieldData($request, $type);
        $field->update($data);
        // A change to how a field affects the head count changes every unit's running count.
        if ($field->wasChanged('headcount')) {
            $type->units->each(fn ($u) => \App\Services\LivestockLedger::rebuild($u));
        }

        return back()->with('success', "Field \"{$field->label}\" saved.");
    }

    public function destroyField(LivestockType $type, LivestockField $field)
    {
        abort_unless($field->livestock_type_id === $type->id, 404);
        $field->delete();
        $type->units->each(fn ($u) => \App\Services\LivestockLedger::rebuild($u));

        return back()->with('success', 'Field removed. Values already recorded for it are kept in the records but no longer shown.');
    }

    private function typeRules(): array
    {
        return [
            'name' => 'required|string|max:80',
            'unit_label' => 'required|string|max:40',
            'count_label' => 'required|string|max:40',
            'icon' => 'nullable|string|max:60',
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => 'nullable|string|max:500',
        ];
    }

    private function fieldData(Request $request, LivestockType $type): array
    {
        $data = $request->validate([
            'label' => 'required|string|max:80',
            'kind' => ['required', Rule::in(array_keys(LivestockField::KINDS))],
            'unit' => 'nullable|string|max:30',
            'aggregate' => ['required', Rule::in(array_keys(LivestockField::AGGREGATES))],
            'headcount' => ['required', Rule::in(array_keys(LivestockField::HEADCOUNT))],
            'options' => 'nullable|string|max:1000',
            'required' => 'nullable|boolean',
            'help' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|min:0',
        ]);
        $data['required'] = $request->boolean('required');
        $data['options'] = $data['kind'] === 'select'
            ? array_values(array_filter(array_map('trim', preg_split('/[,\n]/', (string) ($data['options'] ?? '')))))
            : null;
        if (! in_array($data['kind'], ['number', 'decimal', 'money'], true)) {
            $data['headcount'] = 'none';
            if (! in_array($data['aggregate'], ['count', 'none'], true)) {
                $data['aggregate'] = $data['kind'] === 'yes_no' ? 'count' : 'none';
            }
        }
        if ($data['headcount'] !== 'none' && $data['kind'] !== 'number') {
            $data['kind'] = 'number';   // head counts are whole animals
        }
        if (! isset($data['sort'])) {
            unset($data['sort']);
        }

        return $data;
    }

    private function uniqueKey(LivestockType $type, string $key): string
    {
        $key = trim(preg_replace('/[^a-z0-9_]/', '', $key), '_') ?: 'field';
        $base = Str::limit($key, 50, '');
        $i = 2;
        while ($type->fields()->where('key', $key)->exists()) {
            $key = $base . '_' . $i++;
        }

        return $key;
    }
}
