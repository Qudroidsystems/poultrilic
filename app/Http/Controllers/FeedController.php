<?php

namespace App\Http\Controllers;

use App\Models\DailyEntry;
use App\Models\Expense;
use App\Models\FeedPurchase;
use App\Services\FeedStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View feed', ['only' => ['index']]);
        $this->middleware('permission:Create feed', ['only' => ['store']]);
        $this->middleware('permission:Update feed', ['only' => ['update']]);
        $this->middleware('permission:Delete feed', ['only' => ['destroy']]);
    }

    public function index(FeedStockService $stock)
    {
        $snapshot = $stock->snapshot();

        // Last 14 days of farm-wide feeding, for the usage table.
        $usage = DailyEntry::where('entry_date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('entry_date, SUM(daily_feeds) as bags')
            ->groupBy('entry_date')
            ->orderByDesc('entry_date')
            ->get();

        return view('feed.index', [
            'pagetitle' => 'Feed Stock',
            'stock' => $snapshot,
            'purchases' => FeedPurchase::orderByDesc('purchase_date')->orderByDesc('id')->paginate(15),
            'usage' => $usage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($data, $request) {
            $purchase = FeedPurchase::create($data + ['created_by' => $request->user()->id]);
            $this->syncExpense($purchase);
        });

        return back()->with('success', 'Feed purchase recorded.');
    }

    public function update(Request $request, FeedPurchase $feed)
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($feed, $data) {
            $feed->update($data);
            $this->syncExpense($feed);
        });

        return back()->with('success', 'Feed purchase updated.');
    }

    public function destroy(FeedPurchase $feed)
    {
        DB::transaction(function () use ($feed) {
            $expense = $feed->expense;
            $feed->delete();
            $expense?->delete();
        });

        return back()->with('success', 'Feed purchase deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'purchase_date' => 'required|date|before_or_equal:today',
            'bags' => 'required|numeric|gt:0|max:1000000',
            // 0 (or blank) marks opening stock already on the farm.
            'cost_per_bag' => 'nullable|numeric|min:0|max:100000000',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);

        $data['cost_per_bag'] = $data['cost_per_bag'] ?? 0;

        return $data;
    }

    /**
     * Keep one feed expense in step with the purchase so the Expenses page and
     * the dashboard (in recorded-expense mode) include what was spent on feed.
     * Opening stock (no cost) has no expense.
     */
    private function syncExpense(FeedPurchase $purchase): void
    {
        $total = $purchase->total_cost;

        if ($total <= 0) {
            if ($purchase->expense_id) {
                $purchase->expense()->delete();
                $purchase->update(['expense_id' => null]);
            }

            return;
        }

        $attributes = [
            'expense_date' => $purchase->purchase_date->toDateString(),
            'category' => 'feed',
            'amount' => $total,
            'vendor' => $purchase->vendor,
            'description' => 'Feed purchase: ' . rtrim(rtrim(number_format((float) $purchase->bags, 2, '.', ''), '0'), '.') . ' bags',
        ];

        if ($purchase->expense_id && $expense = Expense::find($purchase->expense_id)) {
            $expense->update($attributes);

            return;
        }

        $expense = Expense::create($attributes + ['created_by' => $purchase->created_by]);
        $purchase->update(['expense_id' => $expense->id]);
    }
}
