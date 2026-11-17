@extends('layouts.master')
@section('content')
@php
    $badge = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger'][$sale->status];
    $label = ['paid' => 'Paid', 'partial' => 'Part paid', 'unpaid' => 'Unpaid'][$sale->status];
    $defaultPrice = (float) $sale->price_per_egg;
@endphp
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row"><div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Invoice {{ $sale->invoice_number }}</h4>
                    <div class="page-title-right"><ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Invoices</a></li>
                        <li class="breadcrumb-item active">{{ $sale->invoice_number }}</li>
                    </ol></div>
                </div>
            </div></div>

            @include('sales._alerts')

            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Details <span class="badge bg-{{ $badge }} ms-2">{{ $label }}</span></h5>
                            <div class="d-flex gap-2">
                                <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="btn btn-sm btn-primary">Download / print PDF</a>
                                @can('Update sale')
                                    <button type="button" class="btn btn-sm btn-subtle-primary" data-bs-toggle="modal" data-bs-target="#editSaleModal">Edit</button>
                                @endcan
                                @can('Delete sale')
                                    <form method="POST" action="{{ route('sales.destroy', $sale) }}" onsubmit="return confirm('Delete this invoice?');">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-subtle-danger" type="submit">Delete</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Customer</dt><dd class="col-sm-8">{{ $sale->customer_name }}
                                    @if ($sale->customer?->phone)<span class="text-muted">· {{ $sale->customer->phone }}</span>@endif</dd>
                                <dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ $sale->sale_date->format('j M Y') }}</dd>
                                <dt class="col-sm-4">Quantity</dt><dd class="col-sm-8">{{ $sale->quantity_label }} ({{ number_format($sale->eggs) }} eggs)</dd>
                                <dt class="col-sm-4">Price per egg</dt><dd class="col-sm-8">₦{{ number_format($sale->price_per_egg, 2) }}</dd>
                                <dt class="col-sm-4">Total</dt><dd class="col-sm-8"><strong>₦{{ number_format($sale->total, 2) }}</strong></dd>
                                <dt class="col-sm-4">Paid</dt><dd class="col-sm-8 text-success">₦{{ number_format($sale->paid, 2) }}</dd>
                                <dt class="col-sm-4">Balance</dt><dd class="col-sm-8 {{ $sale->balance > 0 ? 'text-danger' : '' }}"><strong>₦{{ number_format($sale->balance, 2) }}</strong></dd>
                                @if ($sale->notes)<dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $sale->notes }}</dd>@endif
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Payments</h5>
                            @if ($sale->balance > 0)
                                @can('Create payment')
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">Record payment</button>
                                @endcan
                            @endif
                        </div>
                        <div class="card-body">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Date</th><th>Method</th><th class="text-end">Amount (₦)</th><th class="text-end"></th></tr></thead>
                                <tbody>
                                @forelse ($sale->payments as $p)
                                    <tr>
                                        <td>{{ $p->paid_on->format('Y-m-d') }}</td>
                                        <td>{{ $p->method_label }}@if ($p->reference)<br><small class="text-muted">{{ $p->reference }}</small>@endif</td>
                                        <td class="text-end">{{ number_format($p->amount, 2) }}</td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ route('sales.receipt', [$sale, $p]) }}" target="_blank" class="btn btn-sm btn-subtle-secondary">Receipt</a>
                                            @can('Delete payment')
                                                <form method="POST" action="{{ route('sales.payments.destroy', [$sale, $p]) }}" class="d-inline"
                                                      onsubmit="return confirm('Delete this payment?');">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-subtle-danger">×</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No payments yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@can('Update sale')
<div class="modal fade" id="editSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sales.update', $sale) }}" class="modal-content" autocomplete="off">
            @csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Edit {{ $sale->invoice_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">@include('sales._sale_fields', ['prefix' => 'edit_', 'sale' => $sale])</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('Create payment')
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="modal-content" autocomplete="off">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Record payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <p class="text-muted">Outstanding balance: <strong>₦{{ number_format($sale->balance, 2) }}</strong></p>
                <div class="mb-3"><label class="form-label" for="paid_on">Date</label>
                    <input type="date" class="form-control" id="paid_on" name="paid_on" min="{{ $sale->sale_date->format('Y-m-d') }}" max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></div>
                <div class="mb-3"><label class="form-label" for="pay_amount">Amount (₦)</label>
                    <input type="number" step="0.01" min="0.01" max="{{ $sale->balance }}" class="form-control" id="pay_amount" name="amount" value="{{ $sale->balance }}" required></div>
                <div class="mb-3"><label class="form-label" for="method">Method</label>
                    <select class="form-select" id="method" name="method">
                        @foreach ($methods as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
                    </select></div>
                <div class="mb-3"><label class="form-label" for="reference">Reference</label>
                    <input type="text" class="form-control" id="reference" name="reference" maxlength="255" placeholder="Transfer ref, POS slip no., etc."></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save payment</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
