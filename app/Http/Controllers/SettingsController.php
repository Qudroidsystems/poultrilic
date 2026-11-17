<?php

namespace App\Http\Controllers;

use App\Support\FarmSettings;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View settings', ['only' => ['index']]);
        $this->middleware('permission:Update settings', ['only' => ['update']]);
    }

    public function index()
    {
        return view('settings.index', [
            'pagetitle' => 'Settings',
            'settings' => FarmSettings::all(),
            'fields' => FarmSettings::FIELDS,
            'defaults' => FarmSettings::DEFAULTS,
            'textFields' => FarmSettings::TEXT_FIELDS,
            'texts' => collect(FarmSettings::TEXT_FIELDS)->mapWithKeys(fn ($m, $k) => [$k => FarmSettings::text($k)])->all(),
        ]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach (array_keys(FarmSettings::FIELDS) as $key) {
            $rules[$key] = 'required|numeric|min:0|max:1000000000';
        }
        $rules['use_recorded_expenses'] = 'nullable|boolean';
        foreach (array_keys(FarmSettings::TEXT_FIELDS) as $key) {
            $rules[$key] = 'nullable|string|max:255';
        }

        $validated = $request->validate($rules);
        $validated['use_recorded_expenses'] = $request->boolean('use_recorded_expenses') ? 1 : 0;
        foreach (array_keys(FarmSettings::TEXT_FIELDS) as $key) {
            $validated[$key] = trim((string) ($validated[$key] ?? ''));
        }

        FarmSettings::set($validated);

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}
