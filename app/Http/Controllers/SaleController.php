<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DailyEntry;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Support\Eggs;
use App\Support\FarmSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View sale', ['only' => ['index', 'show', 'invoice', 'receipt']]);
        $this->middleware('permission:Create sale', ['only' => ['store']]);
        $this->middleware('permission:Update sale', ['only' => ['update']]);
        $this->middleware('permission:Delete sale', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'customer_id' => 'nullable|integer',
            'status' => ['nullable', Rule::in(['paid', 'partial', 'unpaid', 'open'])],
        ]);

        $from = $filters['from'] ?? now()->subDays(30)->toDateString();
        $to = $filters['to'] ?? now()->toDateString();

        $query = Sale::query()->betweenDates($from, $to);
        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (! empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        // Summary for the whole filtered set, not just this page.
        $invoiced = (float) (clone $query)->sum('total');
        $eggsInvoiced = (int) (clone $query)->sum('eggs');
        $collected = (float) SalePayment::whereIn('sale_id', (clone $query)->select('sales.id'))->sum('amount');
        $eggsRecordedSold = (int) DailyEntry::betweenDates($from, $to)->sum('eggs_sold');

        $sales = $query->with('customer')->withSum('payments', 'amount')
            ->orderByDesc('sale_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('sales.index', [
            'pagetitle' => 'Sales',
            'sales' => $sales,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'filters' => ['from' => $from, 'to' => $to, 'customer_id' => $filters['customer_id'] ?? null, 'status' => $filters['status'] ?? null],
            'invoiced' => $invoiced,
            'collected' => $collected,
            'outstanding' => max($invoiced - $collected, 0),
            'eggsInvoiced' => $eggsInvoiced,
            'eggsRecordedSold' => $eggsRecordedSold,
            'defaultPrice' => FarmSettings::eggPrice(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $sale = DB::transaction(function () use ($data, $request) {
            return Sale::create($data + [
                'invoice_number' => Sale::nextInvoiceNumber((int) substr($data['sale_date'], 0, 4)),
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('sales.show', $sale)->with('success', "Invoice {$sale->invoice_number} created.");
    }

    public function show(Sale $sale)
    {
        $sale->load(['customer', 'payments']);

        return view('sales.show', [
            'pagetitle' => $sale->invoice_number,
            'sale' => $sale,
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'methods' => SalePayment::METHODS,
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        $data = $this->validated($request);

        if ($data['total'] + 0.004 < $sale->paid) {
            throw ValidationException::withMessages([
                'eggs' => 'The new total (₦' . number_format($data['total'], 2) . ') is less than the ₦' . number_format($sale->paid, 2) . ' already paid. Delete a payment first.',
            ]);
        }

        // The invoice number never changes once issued, even if the date moves to another year.
        $sale->update($data);

        return back()->with('success', 'Invoice updated.');
    }

    public function destroy(Sale $sale)
    {
        if ($sale->payments()->exists()) {
            return back()->withErrors(['sale' => 'This invoice has payments. Delete the payments first.']);
        }

        $sale->delete();

        return redirect()->route('sales.index')->with('success', 'Invoice deleted.');
    }

    public function invoice(Sale $sale)
    {
        return $this->pdf('pdf.invoice', $this->pdfData($sale), "{$sale->invoice_number}.pdf");
    }

    public function receipt(Sale $sale, SalePayment $payment)
    {
        abort_unless($payment->sale_id === $sale->id, 404);

        return $this->pdf('pdf.receipt', $this->pdfData($sale) + ['payment' => $payment], "{$payment->receipt_number}.pdf");
    }

    private function pdfData(Sale $sale): array
    {
        $sale->load(['customer', 'payments']);

        return [
            'sale' => $sale,
            'business' => [
                'name' => FarmSettings::text('farm_name'),
                'address' => FarmSettings::text('farm_address'),
                'phone' => FarmSettings::text('farm_phone'),
                'footer' => FarmSettings::text('invoice_footer'),
            ],
        ];
    }

    private function pdf(string $view, array $data, string $filename)
    {
        return Pdf::loadView($view, $data)->setPaper('a4')->stream($filename);
    }

    /**
     * Quantity arrives as crates + pieces; price defaults to the farm egg price.
     */
    private function validated(Request $request): array
    {
        $v = $request->validate([
            'sale_date' => 'required|date|before_or_equal:today',
            'customer_id' => 'nullable|exists:customers,id',
            'crates' => 'nullable|integer|min:0|max:1000000',
            'pieces' => 'nullable|integer|min:0|max:' . (Eggs::PER_CRATE - 1),
            'price_per_egg' => 'nullable|numeric|gt:0|max:1000000',
            'notes' => 'nullable|string|max:255',
        ]);

        $eggs = Eggs::toPieces((int) ($v['crates'] ?? 0), (int) ($v['pieces'] ?? 0));
        if ($eggs < 1) {
            throw ValidationException::withMessages(['eggs' => 'Enter at least one egg (crates and/or pieces).']);
        }

        $price = (float) ($v['price_per_egg'] ?? FarmSettings::eggPrice());

        return [
            'sale_date' => $v['sale_date'],
            'customer_id' => $v['customer_id'] ?? null,
            'eggs' => $eggs,
            'price_per_egg' => $price,
            'total' => Sale::computeTotal($eggs, $price),
            'notes' => $v['notes'] ?? null,
        ];
    }
}
