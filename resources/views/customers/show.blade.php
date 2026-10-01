@extends('layouts.app')
@section('title', $customer->name.' · Vancha')
@section('content')
    <div class="top">
        <div>
            <p class="eyebrow">Accounts</p>
            <h1>{{ $customer->name }}</h1>
            <p class="sub">{{ ucfirst($customer->account_type) }} · {{ $customer->price_level }} · limit KES {{ number_format($customer->credit_limit, 2) }} · owes KES {{ number_format($customer->balance, 2) }}</p>
        </div>
    </div>
    <form class="panel" style="padding: 16px; margin-bottom: 16px;" method="POST" action="{{ route('customers.collect', $customer) }}">
        @csrf
        <div class="form-grid">
            <div class="field"><label>Collect</label><input name="amount" type="number" min="0.01" step="0.01" required></div>
            <div class="field"><label>Method</label><select name="method"><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="bank">Bank</option><option value="cheque">Cheque</option></select></div>
            <div class="field"><label>Reference</label><input name="reference"></div>
        </div>
        <button class="btn" type="submit">Record collection</button>
    </form>
    <div class="panel">
        <table>
            <thead><tr><th>Receipt</th><th>When</th><th>Total</th></tr></thead>
            <tbody>
                @forelse ($customer->sales as $sale)
                    <tr>
                        <td><a href="{{ route('sales.receipt', $sale) }}">{{ $sale->receipt_no }}</a></td>
                        <td>{{ $sale->created_at->format('d M Y H:i') }}</td>
                        <td>KES {{ number_format($sale->total, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">No purchases yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($customer->phone)
        <p class="sub" style="margin-top: 12px;"><a href="sms:{{ $customer->phone }}">Text {{ $customer->phone }}</a>@if ($customer->email) · <a href="mailto:{{ $customer->email }}">Email</a>@endif</p>
    @endif
@endsection
