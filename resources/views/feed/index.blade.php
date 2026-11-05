@extends('layouts.master')
@section('content')
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Feed Stock</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Finance</a></li>
                                <li class="breadcrumb-item active">Feed Stock</li>
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

            @if (! $stock['tracking'])
                <div class="alert alert-info">
                    Feed tracking starts with your first entry. Add what is on the farm now as <strong>opening stock</strong>
                    (leave the cost per bag at 0), then add each purchase as you buy. Daily feeding from the daily entries
                    is deducted automatically from the date of the first entry.
                </div>
            @elseif ($stock['needs_reorder'])
                <div class="alert alert-danger">
                    <strong>Time to reorder feed.</strong>
                    @if ($stock['on_hand'] <= 0)
                        Recorded stock has run out.
                    @else
                        About {{ $stock['days_remaining'] }} days left (reorder warning is {{ $stock['reorder_days'] }} days).
                    @endif
                </div>
            @endif

            <div class="row">
                <div class="col-md-3">
                    <div class="card"><div class="card-body">
                        <p class="text-muted mb-1">Feed on hand</p>
                        <h3 class="mb-0">{{ number_format($stock['on_hand'], 1) }} <small class="text-muted fs-6">bags</small></h3>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body">
                        <p class="text-muted mb-1">Days remaining</p>
                        <h3 class="mb-0">{{ $stock['days_remaining'] !== null ? $stock['days_remaining'] : '—' }}</h3>
                        @if ($stock['run_out_date'])
                            <small class="text-muted">runs out around {{ $stock['run_out_date'] }}</small>
                        @endif
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body">
                        <p class="text-muted mb-1">Average daily use</p>
                        <h3 class="mb-0">{{ number_format($stock['avg_daily_use'], 1) }} <small class="text-muted fs-6">bags/day</small></h3>
                        <small class="text-muted">last {{ \App\Services\FeedStockService::USAGE_WINDOW_DAYS }} days with entries</small>
                    </div></div>
                </div>
                <div class="col-md-3">
                    <div class="card"><div class="card-body">
                        <p class="text-muted mb-1">Bought / fed (since {{ $stock['since'] ?? '—' }})</p>
                        <h3 class="mb-0">{{ number_format($stock['purchased'], 1) }} / {{ number_format($stock['consumed'], 1) }}</h3>
                        <small class="text-muted">bags</small>
                    </div></div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Purchases</h5>
                            @can('Create feed')
                                <button type="button" class="btn btn-success btn-sm" id="add-feed-btn">Add purchase / opening stock</button>
                            @endcan
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="table-active">
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-end">Bags</th>
                                            <th class="text-end">Cost / bag (₦)</th>
                                            <th class="text-end">Total (₦)</th>
                                            <th>Vendor</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($purchases as $p)
                                            <tr>
                                                <td>{{ $p->purchase_date->format('Y-m-d') }}</td>
                                                <td class="text-end">{{ rtrim(rtrim(number_format($p->bags, 2, '.', ''), '0'), '.') }}</td>
                                                <td class="text-end">{{ $p->is_opening_stock ? 'Opening stock' : number_format($p->cost_per_bag, 2) }}</td>
                                                <td class="text-end">{{ $p->is_opening_stock ? '—' : number_format($p->total_cost, 2) }}</td>
                                                <td>{{ $p->vendor }}</td>
                                                <td class="text-end">
                                                    @can('Update feed')
                                                        <button type="button" class="btn btn-sm btn-subtle-primary edit-feed-btn"
                                                            data-url="{{ route('feed.update', $p) }}"
                                                            data-feed="{{ json_encode([
                                                                'purchase_date' => $p->purchase_date->format('Y-m-d'),
                                                                'bags' => rtrim(rtrim(number_format($p->bags, 2, '.', ''), '0'), '.'),
                                                                'cost_per_bag' => (string) $p->cost_per_bag,
                                                                'vendor' => $p->vendor,
                                                                'notes' => $p->notes,
                                                            ]) }}">Edit</button>
                                                    @endcan
                                                    @can('Delete feed')
                                                        <form method="POST" action="{{ route('feed.destroy', $p) }}" class="d-inline"
                                                              onsubmit="return confirm('Delete this purchase? Its feed expense is removed too.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-subtle-danger">Delete</button>
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted py-4">No feed purchases yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">{{ $purchases->links() }}</div>
                            <p class="text-muted small mt-3 mb-0">Each purchase with a cost also appears as a Feed expense on the Expenses page. Editing or deleting it here updates that expense.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Feed used, last 14 days</h5></div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <thead><tr><th>Date</th><th class="text-end">Bags</th></tr></thead>
                                <tbody>
                                    @forelse ($usage as $row)
                                        <tr><td>{{ \Illuminate\Support\Carbon::parse($row->entry_date)->format('Y-m-d') }}</td><td class="text-end">{{ number_format($row->bags, 2) }}</td></tr>
                                    @empty
                                        <tr><td colspan="2" class="text-muted text-center">No daily entries in this period.</td></tr>
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

<div class="modal fade" id="feedModal" tabindex="-1" aria-labelledby="feedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('feed.store') }}" id="feed-form" class="modal-content" autocomplete="off">
            @csrf
            <input type="hidden" name="_method" value="POST" id="feed-method">
            <div class="modal-header">
                <h5 class="modal-title" id="feedModalLabel">Add feed purchase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="purchase_date" class="form-label">Date</label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="mb-3">
                    <label for="bags" class="form-label">Bags</label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="bags" name="bags" required>
                </div>
                <div class="mb-3">
                    <label for="cost_per_bag" class="form-label">Cost per bag (₦)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="cost_per_bag" name="cost_per_bag" placeholder="0 = opening stock already on the farm">
                    <div class="form-text">Total: <strong id="feed-total">₦0.00</strong></div>
                </div>
                <div class="mb-3">
                    <label for="vendor" class="form-label">Vendor</label>
                    <input type="text" class="form-control" id="vendor" name="vendor" maxlength="255">
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <input type="text" class="form-control" id="notes" name="notes" maxlength="255">
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
    const modalEl = document.getElementById('feedModal');
    const form = document.getElementById('feed-form');
    const method = document.getElementById('feed-method');
    const title = document.getElementById('feedModalLabel');
    const total = document.getElementById('feed-total');
    const storeUrl = @json(route('feed.store'));
    const fields = ['purchase_date', 'bags', 'cost_per_bag', 'vendor', 'notes'];

    function updateTotal() {
        const v = (parseFloat(form.elements['bags'].value) || 0) * (parseFloat(form.elements['cost_per_bag'].value) || 0);
        total.textContent = '₦' + v.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    ['bags', 'cost_per_bag'].forEach(f => form.elements[f].addEventListener('input', updateTotal));

    function open(url, httpMethod, heading, values) {
        form.action = url;
        method.value = httpMethod;
        title.textContent = heading;
        fields.forEach(f => { form.elements[f].value = (values && values[f] != null) ? values[f] : ''; });
        if (!values) { form.elements['purchase_date'].value = @json(now()->toDateString()); }
        updateTotal();
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    const addBtn = document.getElementById('add-feed-btn');
    if (addBtn) addBtn.addEventListener('click', () => open(storeUrl, 'POST', 'Add feed purchase', null));

    document.querySelectorAll('.edit-feed-btn').forEach(btn => {
        btn.addEventListener('click', () => open(btn.dataset.url, 'PUT', 'Edit feed purchase', JSON.parse(btn.dataset.feed)));
    });
});
</script>
@endsection
