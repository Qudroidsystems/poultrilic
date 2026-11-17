{{-- Shared by the "new invoice" and "edit invoice" modals. $sale is optional. --}}
@php
    $s = $sale ?? null;
    $crates = $s ? intdiv($s->eggs, \App\Support\Eggs::PER_CRATE) : '';
    $pieces = $s ? $s->eggs % \App\Support\Eggs::PER_CRATE : '';
@endphp
<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}sale_date">Date</label>
    <input type="date" class="form-control" id="{{ $prefix }}sale_date" name="sale_date" max="{{ now()->toDateString() }}"
           value="{{ old('sale_date', $s?->sale_date->format('Y-m-d') ?? now()->toDateString()) }}" required>
</div>
<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}customer_id">Customer</label>
    <select class="form-select" id="{{ $prefix }}customer_id" name="customer_id">
        <option value="">Walk-in customer</option>
        @foreach ($customers as $c)
            <option value="{{ $c->id }}" @selected(old('customer_id', $s?->customer_id) == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
</div>
<div class="row">
    <div class="col-6 mb-3">
        <label class="form-label" for="{{ $prefix }}crates">Crates</label>
        <input type="number" min="0" step="1" class="form-control sale-qty" id="{{ $prefix }}crates" name="crates" value="{{ old('crates', $crates) }}" placeholder="0">
    </div>
    <div class="col-6 mb-3">
        <label class="form-label" for="{{ $prefix }}pieces">Pieces (0-29)</label>
        <input type="number" min="0" max="29" step="1" class="form-control sale-qty" id="{{ $prefix }}pieces" name="pieces" value="{{ old('pieces', $pieces) }}" placeholder="0">
    </div>
</div>
<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}price_per_egg">Price per egg (₦)</label>
    <input type="number" min="0.01" step="0.01" class="form-control sale-price" id="{{ $prefix }}price_per_egg" name="price_per_egg"
           value="{{ old('price_per_egg', $s ? (float) $s->price_per_egg : $defaultPrice) }}" required>
    <div class="form-text">Total: <strong class="sale-total">₦0.00</strong> (30 eggs per crate)</div>
</div>
<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}notes">Notes</label>
    <input type="text" maxlength="255" class="form-control" id="{{ $prefix }}notes" name="notes" value="{{ old('notes', $s?->notes) }}">
</div>
<script>
(function () {
    const form = document.currentScript.closest('form');
    function total() {
        const eggs = (parseInt(form.querySelector('[name=crates]').value) || 0) * 30 + (parseInt(form.querySelector('[name=pieces]').value) || 0);
        const t = eggs * (parseFloat(form.querySelector('[name=price_per_egg]').value) || 0);
        form.querySelector('.sale-total').textContent = '₦' + t.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    form.querySelectorAll('.sale-qty, .sale-price').forEach(el => el.addEventListener('input', total));
    total();
})();
</script>
