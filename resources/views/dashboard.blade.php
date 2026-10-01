@extends('layouts.app')

@section('title', 'Vancha')

@section('content')
    <div class="top">
        <div>
            <p class="eyebrow">Floor</p>
            <h1>Good day.</h1>
            <p class="sub">The counter, the shelf, and what sold today.</p>
        </div>
        <div class="head-actions">
            <a class="btn secondary" href="{{ route('inventory.create') }}">Add item</a>
            <a class="btn" href="{{ route('checkout.index') }}">Open counter</a>
        </div>
    </div>

    <div class="grid">
        <article class="panel stat"><span>Items</span><strong>{{ number_format($itemCount) }}</strong></article>
        <article class="panel stat"><span>Stock at cost</span><strong>KES {{ number_format((float) $stockValue, 0) }}</strong></article>
        <article class="panel stat"><span>Sold today</span><strong>KES {{ number_format((float) $todaySales, 0) }}</strong></article>
        <article class="panel stat"><span>At or below reorder</span><strong>{{ number_format($lowStock) }}</strong></article>
    </div>

    <h2>Latest items</h2>
    <div class="panel">
        <table>
            <thead>
                <tr><th>Item</th><th>Category</th><th>SKU</th><th>Retail</th></tr>
            </thead>
            <tbody>
                @forelse ($recent as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->sku }}</td>
                        <td class="money">KES {{ number_format($product->retail_price, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No items yet. Add the first one from Inventory.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
