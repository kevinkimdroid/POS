@extends('layouts.app')
@section('title', 'Stock · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Yard</p><h1>Stock</h1><p class="sub">Adjustments, transfers, and counts. Low stock is listed first.</p></div></div>
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
    <div class="grid">
        <article class="panel stat"><span>At or below reorder</span><strong>{{ $low->count() }}</strong></article>
    </div>
    <div class="panel" style="margin: 16px 0;">
        <table>
            <thead><tr><th>Item</th><th>On hand</th><th>Reorder</th></tr></thead>
            <tbody>
                @forelse ($low as $product)
                    <tr><td>{{ $product->name }}</td><td class="low">{{ rtrim(rtrim(number_format((float) $product->stocks_sum_quantity, 3), '0'), '.') }}</td><td>{{ $product->reorder_level }}</td></tr>
                @empty
                    <tr><td colspan="3">Nothing is at the reorder line.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="form-grid">
        <form class="panel" style="padding: 16px;" method="POST" action="{{ route('stock.adjust') }}">
            @csrf
            <h2 style="margin-top: 0;">Adjustment</h2>
            <div class="field"><label>Item</label><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="field"><label>Size / finish</label><select name="product_variant_id"><option value="">—</option>@foreach ($products as $product)@foreach ($product->variants as $variant)<option value="{{ $variant->id }}" data-product="{{ $product->id }}">{{ $variant->name }}</option>@endforeach @endforeach</select></div>
            <div class="field"><label>Branch</label><select name="branch_id">@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="field"><label>Quantity change</label><input name="quantity" type="number" step="0.001" required placeholder="-2 or 5"></div>
            <div class="field"><label>Reason</label><select name="reason"><option>damage</option><option>theft</option><option>breakage</option><option>count correction</option></select></div>
            <div class="field"><label>Note</label><input name="note" placeholder="Optional"></div>
            <div class="field"><label>Batch code</label><input name="batch_code" placeholder="Paint, adhesive, sealant"></div>
            <div class="field"><label>Expiry</label><input name="expires_on" type="date"></div>
            <div class="field"><label>Serial</label><input name="serial" placeholder="Power tool warranty"></div>
            <button class="btn" type="submit">Save adjustment</button>
        </form>
        <form class="panel" style="padding: 16px;" method="POST" action="{{ route('stock.transfer') }}">
            @csrf
            <h2 style="margin-top: 0;">Transfer</h2>
            <div class="field"><label>Item</label><select name="product_id" required>@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="field"><label>Size / finish</label><select name="product_variant_id"><option value="">—</option>@foreach ($products as $product)@foreach ($product->variants as $variant)<option value="{{ $variant->id }}" data-product="{{ $product->id }}">{{ $variant->name }}</option>@endforeach @endforeach</select></div>
            <div class="field"><label>From</label><select name="from_branch_id">@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="field"><label>To</label><select name="to_branch_id">@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="field"><label>Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" required></div>
            <button class="btn" type="submit">Move stock</button>
        </form>
    </div>
    <form class="panel" style="padding: 16px; margin-top: 16px;" method="POST" action="{{ route('stock.counts.store') }}">
        @csrf
        <h2 style="margin-top: 0;">Stock take</h2>
        <div class="field"><label>Branch</label><select name="branch_id">@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
        <button class="btn" type="submit">Start a count</button>
        @foreach ($counts as $count)
            <div><a href="{{ route('stock.count', $count) }}">Count #{{ $count->id }}</a> · {{ $count->branch->name }} · {{ $count->status }}</div>
        @endforeach
    </form>
@endsection
