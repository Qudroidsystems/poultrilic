<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View customer', ['only' => ['index']]);
        $this->middleware('permission:Create customer', ['only' => ['store']]);
        $this->middleware('permission:Update customer', ['only' => ['update']]);
        $this->middleware('permission:Delete customer', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $customers = Customer::query()
            ->withCount('sales')
            ->withSum('sales', 'total')
            ->when($search !== '', fn ($q) => $q->where(function ($w) use ($search) {
                $w->where('name', 'like', "%$search%")->orWhere('phone', 'like', "%$search%");
            }))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        // What each listed customer still owes.
        $paid = \App\Models\SalePayment::query()
            ->join('sales', 'sales.id', '=', 'sale_payments.sale_id')
            ->whereIn('sales.customer_id', $customers->pluck('id'))
            ->groupBy('sales.customer_id')
            ->selectRaw('sales.customer_id, SUM(sale_payments.amount) as paid')
            ->pluck('paid', 'customer_id');

        return view('customers.index', [
            'pagetitle' => 'Customers',
            'customers' => $customers,
            'paid' => $paid,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        Customer::create($this->validated($request));

        return back()->with('success', 'Customer added.');
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return back()->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        // Invoices keep their history, so a customer with invoices cannot be removed.
        if ($customer->sales()->exists()) {
            return back()->withErrors(['customer' => 'This customer has invoices and cannot be deleted.']);
        }

        $customer->delete();

        return back()->with('success', 'Customer deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:255',
        ]);
    }
}
