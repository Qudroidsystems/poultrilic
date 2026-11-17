<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $sale->invoice_number }}</title>@include('pdf._style')</head>
<body>
<table class="head"><tr>
    <td>
        <div class="biz">{{ $business['name'] }}</div>
        @if ($business['address'])<div class="muted">{{ $business['address'] }}</div>@endif
        @if ($business['phone'])<div class="muted">Tel: {{ $business['phone'] }}</div>@endif
    </td>
    <td class="right">
        <h1>INVOICE</h1>
        <div>{{ $sale->invoice_number }}</div>
        <div class="muted">Date: {{ $sale->sale_date->format('j M Y') }}</div>
    </td>
</tr></table>

<table class="box"><tr>
    <td>
        <div class="muted">Billed to</div>
        <strong>{{ $sale->customer_name }}</strong>
        @if ($sale->customer?->address)<div>{{ $sale->customer->address }}</div>@endif
        @if ($sale->customer?->phone)<div>{{ $sale->customer->phone }}</div>@endif
    </td>
    <td class="right">
        <span class="stamp {{ $sale->status === 'paid' ? 'paid' : 'due' }}">{{ ['paid' => 'PAID', 'partial' => 'PART PAID', 'unpaid' => 'UNPAID'][$sale->status] }}</span>
    </td>
</tr></table>

<table class="items">
    <thead><tr><th>Description</th><th class="right">Quantity</th><th class="right">Unit price</th><th class="right">Amount</th></tr></thead>
    <tbody><tr>
        <td>Table eggs<br><span class="muted">{{ $sale->quantity_label }} ({{ number_format($sale->eggs) }} eggs, 30 per crate)</span>@if ($sale->notes)<br><span class="muted">{{ $sale->notes }}</span>@endif</td>
        <td class="right">{{ number_format($sale->eggs) }}</td>
        <td class="right">₦{{ number_format($sale->price_per_egg, 2) }}</td>
        <td class="right">₦{{ number_format($sale->total, 2) }}</td>
    </tr></tbody>
</table>

<table class="totals">
    <tr><td>Total</td><td class="right">₦{{ number_format($sale->total, 2) }}</td></tr>
    @foreach ($sale->payments as $p)
        <tr><td class="muted">Paid {{ $p->paid_on->format('j M Y') }} ({{ $p->method_label }})</td><td class="right muted">- ₦{{ number_format($p->amount, 2) }}</td></tr>
    @endforeach
    <tr class="grand"><td>Balance due</td><td class="right">₦{{ number_format($sale->balance, 2) }}</td></tr>
</table>

@if ($business['footer'])<div class="foot">{{ $business['footer'] }}</div>@endif
</body></html>
