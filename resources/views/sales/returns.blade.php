@extends('layouts.app')
@section('title', 'Returns · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Sell</p><h1>Returns</h1><p class="sub">The refund uses the original payment method. Stock goes back on the shelf.</p></div></div>
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
    @foreach ($sales as $sale)
        <form class="panel" style="padding: 12px 14px; margin-bottom: 10px;" method="POST" action="{{ route('sales.refund', $sale) }}">
            @csrf
            <strong><a href="{{ route('sales.receipt', $sale) }}">{{ $sale->receipt_no }}</a></strong>
            <div class="field"><label>Line</label>
                <select name="sale_line_id">
                    @foreach ($sale->lines as $line)
                        <option value="{{ $line->id }}">{{ $line->description }} · {{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field"><label>Quantity to return</label><input name="quantity" type="number" min="0.001" step="0.001" required></div>
            <button class="btn" type="submit">Refund</button>
        </form>
    @endforeach
@endsection
