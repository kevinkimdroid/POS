@extends('layouts.app')
@section('title', 'Quotes · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Sell</p><h1>Quotes</h1><p class="sub">Open a quote at the counter when the contractor is ready to buy.</p></div></div>
    <div class="panel">
        <table>
            <thead><tr><th>Quote</th><th>Customer</th><th>Total</th><th>Until</th><th></th></tr></thead>
            <tbody>
                @forelse ($quotes as $quote)
                    <tr>
                        <td>#{{ $quote->id }} · {{ $quote->status }}</td>
                        <td>{{ $quote->customer->name ?? 'Walk-in' }}</td>
                        <td>KES {{ number_format($quote->total, 2) }}</td>
                        <td>{{ $quote->valid_until?->format('d M Y') }}</td>
                        <td>@if ($quote->status === 'open')<a href="{{ route('checkout.index', ['quote' => $quote->id]) }}">Sell</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Save a quote from the counter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
