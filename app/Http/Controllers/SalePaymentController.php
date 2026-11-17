<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SalePaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:Create payment', ['only' => ['store']]);
        $this->middleware('permission:Delete payment', ['only' => ['destroy']]);
    }

    public function store(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'paid_on' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $sale->sale_date->toDateString()],
            'amount' => 'required|numeric|gt:0|max:999999999999',
            'method' => ['required', Rule::in(array_keys(SalePayment::METHODS))],
            'reference' => 'nullable|string|max:255',
        ]);

        $balance = $sale->balance;
        if ((float) $data['amount'] > $balance + 0.004) {
            throw ValidationException::withMessages([
                'amount' => 'That is more than the outstanding balance of ₦' . number_format($balance, 2) . '.',
            ]);
        }

        $sale->payments()->create($data + ['created_by' => $request->user()->id]);

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(Sale $sale, SalePayment $payment)
    {
        abort_unless($payment->sale_id === $sale->id, 404);

        $payment->delete();

        return back()->with('success', 'Payment deleted.');
    }
}
