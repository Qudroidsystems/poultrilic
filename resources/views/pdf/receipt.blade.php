<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>{{ $payment->receipt_number }}</title>@include('pdf._style')</head>
<body>
<table class="head"><tr>
    <td>
        <div class="biz">{{ $business['name'] }}</div>
        @if ($business['address'])<div class="muted">{{ $business['address'] }}</div>@endif
        @if ($business['phone'])<div class="muted">Tel: {{ $business['phone'] }}</div>@endif
    </td>
    <td class="right">
        <h1>RECEIPT</h1>
        <div>{{ $payment->receipt_number }}</div>
        <div class="muted">Date: {{ $payment->paid_on->format('j M Y') }}</div>
    </td>
</tr></table>

<table class="box"><tr>
    <td>
        <div class="muted">Received from</div>
        <strong>{{ $sale->customer_name }}</strong>
        @if ($sale->customer?->phone)<div>{{ $sale->customer->phone }}</div>@endif
    </td>
    <td class="right"><div class="muted">For invoice</div><strong>{{ $sale->invoice_number }}</strong> · {{ $sale->sale_date->format('j M Y') }}</td>
</tr></table>

<table class="items">
    <thead><tr><th>Payment</th><th>Method</th><th>Reference</th><th class="right">Amount</th></tr></thead>
    <tbody><tr>
        <td>Payment for {{ $sale->quantity_label }} of eggs</td>
        <td>{{ $payment->method_label }}</td>
        <td>{{ $payment->reference ?: '—' }}</td>
        <td class="right">₦{{ number_format($payment->amount, 2) }}</td>
    </tr></tbody>
</table>

@php $paidToDate = (float) $sale->payments->where('id', '<=', $payment->id)->sum('amount'); @endphp
<table class="totals">
    <tr><td>Invoice total</td><td class="right">₦{{ number_format($sale->total, 2) }}</td></tr>
    <tr><td>Paid to date (incl. this receipt)</td><td class="right">₦{{ number_format($paidToDate, 2) }}</td></tr>
    <tr class="grand"><td>Balance after this payment</td><td class="right">₦{{ number_format(max((float) $sale->total - $paidToDate, 0), 2) }}</td></tr>
</table>

@if ($business['footer'])<div class="foot">{{ $business['footer'] }}</div>@endif
</body></html>
