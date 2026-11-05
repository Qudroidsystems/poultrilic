<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Flock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View expense', ['only' => ['index']]);
        $this->middleware('permission:Create expense', ['only' => ['store']]);
        $this->middleware('permission:Update expense', ['only' => ['update']]);
        $this->middleware('permission:Delete expense', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'flock_id' => 'nullable|integer',
            'category' => ['nullable', Rule::in(array_keys(Expense::CATEGORIES))],
        ]);

        $from = $filters['from'] ?? now()->subDays(30)->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        $query = Expense::with('flock')->betweenDates($from, $to);

        if (! empty($filters['flock_id'])) {
            $query->where('flock_id', $filters['flock_id']);
        }
        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        // Totals reflect the whole filtered set, not just the current page.
        $total = (float) (clone $query)->sum('amount');
        $byCategory = (clone $query)->reorder()
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $expenses = $query->orderByDesc('expense_date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('expenses.index', [
            'pagetitle' => 'Expenses',
            'expenses' => $expenses,
            'total' => $total,
            'byCategory' => $byCategory,
            'categories' => Expense::CATEGORIES,
            'flocks' => Flock::orderBy('name')->get(['id', 'name']),
            'filters' => ['from' => $from, 'to' => $to, 'flock_id' => $filters['flock_id'] ?? null, 'category' => $filters['category'] ?? null],
        ]);
    }

    public function store(Request $request)
    {
        Expense::create($this->validated($request) + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Expense recorded.');
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return back()->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return back()->with('success', 'Expense deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_date' => 'required|date|before_or_equal:today',
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'flock_id' => 'nullable|exists:flocks,id',
            'amount' => 'required|numeric|gt:0|max:999999999999',
            'description' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|max:255',
        ]);
    }
}
