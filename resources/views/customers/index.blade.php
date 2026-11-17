@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row"><div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0">Customers</h4>
                    <div class="page-title-right"><ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Sales</a></li>
                        <li class="breadcrumb-item active">Customers</li>
                    </ol></div>
                </div>
            </div></div>

            @include('sales._alerts')

            <div class="card"><div class="card-body">
                <form method="GET" action="{{ route('customers.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-5"><label class="form-label" for="q">Search name or phone</label>
                        <input type="text" class="form-control" id="q" name="q" value="{{ $search }}"></div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @can('Create customer')
                            <button type="button" class="btn btn-success" id="add-customer-btn">Add customer</button>
                        @endcan
                    </div>
                </form>
            </div></div>

            <div class="card"><div class="card-body">
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="table-active"><tr>
                            <th>Name</th><th>Phone</th><th>Address</th><th class="text-end">Invoices</th>
                            <th class="text-end">Billed (₦)</th><th class="text-end">Owes (₦)</th><th class="text-end">Action</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($customers as $c)
                            @php $owes = max((float) $c->sales_sum_total - (float) ($paid[$c->id] ?? 0), 0); @endphp
                            <tr>
                                <td>{{ $c->name }}</td>
                                <td>{{ $c->phone }}</td>
                                <td>{{ $c->address }}</td>
                                <td class="text-end">
                                    @can('View sale')
                                        <a href="{{ route('sales.index', ['customer_id' => $c->id, 'from' => '2000-01-01']) }}">{{ $c->sales_count }}</a>
                                    @else {{ $c->sales_count }} @endcan
                                </td>
                                <td class="text-end">{{ number_format((float) $c->sales_sum_total, 2) }}</td>
                                <td class="text-end {{ $owes > 0 ? 'text-danger' : '' }}">{{ number_format($owes, 2) }}</td>
                                <td class="text-end">
                                    @can('Update customer')
                                        <button type="button" class="btn btn-sm btn-subtle-primary edit-customer-btn"
                                            data-url="{{ route('customers.update', $c) }}"
                                            data-customer="{{ json_encode(['name' => $c->name, 'phone' => $c->phone, 'email' => $c->email, 'address' => $c->address, 'notes' => $c->notes]) }}">Edit</button>
                                    @endcan
                                    @can('Delete customer')
                                        <form method="POST" action="{{ route('customers.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete this customer?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-subtle-danger">Delete</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No customers yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $customers->links() }}</div>
            </div></div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('customers.store') }}" id="customer-form" class="modal-content" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST" id="customer-method">
            <div class="modal-header"><h5 class="modal-title" id="customer-title">Add customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label" for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" maxlength="255" required></div>
                <div class="mb-3"><label class="form-label" for="phone">Phone</label>
                    <input type="text" class="form-control" id="phone" name="phone" maxlength="40"></div>
                <div class="mb-3"><label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" maxlength="255"></div>
                <div class="mb-3"><label class="form-label" for="address">Address</label>
                    <input type="text" class="form-control" id="address" name="address" maxlength="255"></div>
                <div class="mb-3"><label class="form-label" for="notes">Notes</label>
                    <input type="text" class="form-control" id="notes" name="notes" maxlength="255"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('customerModal');
    const form = document.getElementById('customer-form');
    const method = document.getElementById('customer-method');
    const title = document.getElementById('customer-title');
    const storeUrl = @json(route('customers.store'));
    const fields = ['name', 'phone', 'email', 'address', 'notes'];

    function open(url, httpMethod, heading, values) {
        form.action = url; method.value = httpMethod; title.textContent = heading;
        fields.forEach(f => { form.elements[f].value = (values && values[f] != null) ? values[f] : ''; });
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    const addBtn = document.getElementById('add-customer-btn');
    if (addBtn) addBtn.addEventListener('click', () => open(storeUrl, 'POST', 'Add customer', null));
    document.querySelectorAll('.edit-customer-btn').forEach(btn => {
        btn.addEventListener('click', () => open(btn.dataset.url, 'PUT', 'Edit customer', JSON.parse(btn.dataset.customer)));
    });
});
</script>
@endsection
