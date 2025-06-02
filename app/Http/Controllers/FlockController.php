<?php

namespace App\Http\Controllers;

use App\Models\Flock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FlockController extends Controller
{
    public function index()
    {
        $flocks = Flock::paginate(5);
        $bird_count_ranges = [
            '0-100' => Flock::where('initial_bird_count', '<=', 100)->count(),
            '101-200' => Flock::whereBetween('initial_bird_count', [101, 200])->count(),
            '201-500' => Flock::whereBetween('initial_bird_count', [201, 500])->count(),
            '501+' => Flock::where('initial_bird_count', '>', 500)->count(),
        ];
        return view('flocks.flocks.index', [
            'flocks' => $flocks,
            'bird_count_ranges' => $bird_count_ranges,
            'pagetitle' => 'Flock Management | Flocks',
        ]);
    }

    public function create()
    {
        return view('flocks.create', [
            'pagetitle' => 'Flock Management | Create Flock',
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'initial_bird_count' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $flock = Flock::create([
            'initial_bird_count' => $request->initial_bird_count,
            'current_bird_count' => $request->initial_bird_count,
        ]);

        return response()->json([
            'message' => 'Flock added successfully',
            'id' => $flock->id,
            'initial_bird_count' => $flock->initial_bird_count,
            'current_bird_count' => $flock->current_bird_count,
            'created_at' => $flock->created_at->format('Y-m-d'),
        ], 201);
    }

    public function show(string $id)
    {
        try {
            $flock = Flock::with('weeks.dailyEntries')->findOrFail($id);
            return view('flocks.show', [
                'flock' => $flock,
                'page_title' => 'Flock Management | View Flock #' . $flock->id,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Flock not found'], 404);
        }
    }

    public function edit(string $id)
    {
        try {
            $flock = Flock::findOrFail($id);
            return view('flocks.edit', [
                'flock' => $flock,
                'page_title' => 'Flock Management | Edit Flock #' . $flock->id,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Flock not found'], 404);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $flock = Flock::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'initial_bird_count' => 'required|integer|min:0',
                'current_bird_count' => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $flock->update([
                'initial_bird_count' => $request->initial_bird_count,
                'current_bird_count' => $request->current_bird_count,
            ]);

            return response()->json([
                'message' => 'Flock updated successfully',
                'id' => $flock->id,
                'initial_bird_count' => $flock->initial_bird_count,
                'current_bird_count' => $flock->current_bird_count,
                'created_at' => $flock->created_at->format('Y-m-d'),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => "Flock ID $id not found"], 404);
        }
    }

    public function destroy(string $id)
    {
        try {
            $flock = Flock::findOrFail($id);
            $flock->delete();

            return response()->json([
                'message' => 'Flock deleted successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => "Flock ID $id not found"], 404);
        }
    }
}