@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row"><div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Sales &amp; Invoices</h4>
                    <div class="page-title-right"><ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Sales</a></li>
                        <li class="breadcrumb-item active">Invoices</li>
                    </ol></div>
                </div>
            </div></div>

            @include('sales._alerts')

            <div class="card"><div class="card-body">
                <form method="GET" action="{{ route('sales.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-2"><label class="form-label" for="from">From</label>
                        <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] }}"></div>
                    <div class="col-md-2"><label class="form-label" for="to">To</label>
                        <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] }}"></div>
                    <div class="col-md-3"><label class="form-label" for="f_customer">Customer</label>
                        <select class="form-select" id="f_customer" name="customer_id">
                            <option value="">All customers</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected($filters['customer_id'] == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-2"><label class="form-label" for="f_status">Status</label>
                        <select class="form-select" id="f_status" name="status">
                            <option value="">All</option>
                            @foreach (['open' => 'Outstanding (unpaid + part paid)', 'unpaid' => 'Unpaid', 'partial' => 'Part paid', 'paid' => 'Paid'] as $k => $l)
                                <option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        @can('Create sale')
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newSaleModal">New invoice</button>
                        @endcan
                    </div>
                </form>
            </div></div>

            <div class="row">
                <div class="col-md-3"><div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Invoiced</p><h4 class="mb-0">₦{{ number_format($invoiced, 2) }}</h4>
                </div></div></div>
                <div class="col-md-3"><div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Collected</p><h4 class="mb-0 text-success">₦{{ number_format($collected, 2) }}</h4>
                </div></div></div>
                <div class="col-md-3"><div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Outstanding</p><h4 class="mb-0 {{ $outstanding > 0 ? 'text-danger' : '' }}">₦{{ number_format($outstanding, 2) }}</h4>
                </div></div></div>
                <div class="col-md-3"><div class="card"><div class="card-body">
                    <p class="text-muted mb-1">Eggs invoiced vs. recorded sold</p>
                    <h4 class="mb-0">{{ \App\Support\Eggs::format($eggsInvoiced) }} / {{ \App\Support\Eggs::format($eggsRecordedSold) }}</h4>
                    <small class="text-muted">invoices vs. daily entries, same dates</small>
                </div></div></div>
            </div>

            <div class="card"><div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-active"><tr>
                            <th>Invoice</th><th>Date</th><th>Customer</th><th>Eggs</th>
                            <th class="text-end">Total (₦)</th><th class="text-end">Paid (₦)</th><th class="text-end">Balance (₦)</th><th>Status</th><th class="text-end">Action</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td><a href="{{ route('sales.show', $sale) }}">{{ $sale->invoice_number }}</a></td>
                                <td>{{ $sale->sale_date->format('Y-m-d') }}</td>
                                <td>{{ $sale->customer_name }}</td>
                                <td>{{ $sale->quantity_label }}</td>
                                <td class="text-end">{{ number_format($sale->total, 2) }}</td>
                                <td class="text-end">{{ number_format($sale->paid, 2) }}</td>
                                <td class="text-end">{{ number_format($sale->balance, 2) }}</td>
                                <td>
                                    @php $badge = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger'][$sale->status]; @endphp
                                    <span class="badge bg-{{ $badge }}">{{ ['paid' => 'Paid', 'partial' => 'Part paid', 'unpaid' => 'Unpaid'][$sale->status] }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-subtle-primary">Open</a>
                                    <a href="{{ route('sales.invoice', $sale) }}" target="_blank" class="btn btn-sm btn-subtle-secondary">PDF</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No invoices for these filters.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $sales->links() }}</div>
            </div></div>
        </div>
    </div>
</div>

@can('Create sale')
<div class="modal fade" id="newSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sales.store') }}" class="modal-content" autocomplete="off">
            @csrf
            <div class="modal-header"><h5 class="modal-title">New invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">@include('sales._sale_fields', ['prefix' => 'new_'])</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Create invoice</button>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection
