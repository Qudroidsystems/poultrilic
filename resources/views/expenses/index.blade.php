@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Expenses</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Finance</a></li>
                                <li class="breadcrumb-item active">Expenses</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Filters --}}
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label" for="from">From</label>
                            <input type="date" class="form-control" id="from" name="from" value="{{ $filters['from'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="to">To</label>
                            <input type="date" class="form-control" id="to" name="to" value="{{ $filters['to'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="filter_flock">Flock</label>
                            <select class="form-select" id="filter_flock" name="flock_id">
                                <option value="">All flocks</option>
                                @foreach ($flocks as $flock)
                                    <option value="{{ $flock->id }}" @selected($filters['flock_id'] == $flock->id)>{{ $flock->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="filter_category">Category</label>
                            <select class="form-select" id="filter_category" name="category">
                                <option value="">All categories</option>
                                @foreach ($categories as $key => $label)
                                    <option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            @can('Create expense')
                                <button type="button" class="btn btn-success" id="add-expense-btn">Add expense</button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>

            {{-- Summary --}}
            <div class="row">
                <div class="col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mb-1">Total ({{ $filters['from'] }} to {{ $filters['to'] }})</p>
                            <h3 class="mb-0">₦{{ number_format($total, 2) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mb-2">By category</p>
                            @forelse ($byCategory as $category => $sum)
                                @php $pct = $total > 0 ? round($sum / $total * 100) : 0; @endphp
                                <div class="d-flex align-items-center mb-2">
                                    <div style="width: 220px">{{ $categories[$category] ?? ucfirst($category) }}</div>
                                    <div class="progress flex-grow-1" style="height: 10px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-end" style="width: 190px">₦{{ number_format($sum, 2) }} <span class="text-muted">({{ $pct }}%)</span></div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No expenses in this period.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-active">
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Vendor</th>
                                    <th>Flock</th>
                                    <th class="text-end">Amount (₦)</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($expenses as $expense)
                                    <tr>
                                        <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                                        <td>{{ $expense->category_label }}</td>
                                        <td>{{ $expense->description }}</td>
                                        <td>{{ $expense->vendor }}</td>
                                        <td>{{ $expense->flock->name ?? 'Whole farm' }}</td>
                                        <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                                        <td class="text-end">
                                            @can('Update expense')
                                                <button type="button" class="btn btn-sm btn-subtle-primary edit-expense-btn"
                                                    data-url="{{ route('expenses.update', $expense) }}"
                                                    data-expense="{{ json_encode([
                                                        'expense_date' => $expense->expense_date->format('Y-m-d'),
                                                        'category' => $expense->category,
                                                        'flock_id' => $expense->flock_id,
                                                        'amount' => (string) $expense->amount,
                                                        'description' => $expense->description,
                                                        'vendor' => $expense->vendor,
                                                    ]) }}">Edit</button>
                                            @endcan
                                            @can('Delete expense')
                                                <form method="POST" action="{{ route('expenses.destroy', $expense) }}" class="d-inline"
                                                      onsubmit="return confirm('Delete this expense?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-subtle-danger">Delete</button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No expenses recorded for these filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $expenses->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / edit modal --}}
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('expenses.store') }}" id="expense-form" class="modal-content" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST" id="expense-method">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalLabel">Add expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="expense_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="expense_date" name="expense_date" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category" required>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="flock_id" class="form-label">Flock</label>
                    <select class="form-select" id="flock_id" name="flock_id">
                        <option value="">Whole farm (not tied to one flock)</option>
                        @foreach ($flocks as $flock)
                            <option value="{{ $flock->id }}">{{ $flock->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="amount" class="form-label">Amount (₦)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <input type="text" class="form-control" id="description" name="description" maxlength="255" placeholder="e.g. 20 bags layers mash">
                </div>
                <div class="mb-3">
                    <label for="vendor" class="form-label">Vendor</label>
                    <input type="text" class="form-control" id="vendor" name="vendor" maxlength="255">
                </div>
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
    const modalEl = document.getElementById('expenseModal');
    const form = document.getElementById('expense-form');
    const method = document.getElementById('expense-method');
    const title = document.getElementById('expenseModalLabel');
    const storeUrl = @json(route('expenses.store'));
    const fields = ['expense_date', 'category', 'flock_id', 'amount', 'description', 'vendor'];

    function open(url, httpMethod, heading, values) {
        form.action = url;
        method.value = httpMethod;
        title.textContent = heading;
        fields.forEach(f => { form.elements[f].value = (values && values[f] != null) ? values[f] : ''; });
        if (!values) { form.elements['expense_date'].value = @json(now()->toDateString()); }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    const addBtn = document.getElementById('add-expense-btn');
    if (addBtn) addBtn.addEventListener('click', () => open(storeUrl, 'POST', 'Add expense', null));

    document.querySelectorAll('.edit-expense-btn').forEach(btn => {
        btn.addEventListener('click', () => open(btn.dataset.url, 'PUT', 'Edit expense', JSON.parse(btn.dataset.expense)));
    });
});
</script>
@endsection
