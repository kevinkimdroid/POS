@extends('layouts.app')

@section('title', 'Inventory · Vancha')

@section('content')
    <div class="top">
        <div>
            <p class="eyebrow">Shelf</p>
            <h1>Inventory</h1>
            <p class="sub">Hardware catalogue. Search by name, SKU, barcode, or brand.</p>
        </div>
        <div class="head-actions">
            <a class="btn secondary" href="{{ route('inventory.template') }}">Excel template</a>
            <a class="btn" href="{{ route('inventory.create') }}">Add item</a>
        </div>
    </div>

    <form class="panel" style="padding: 16px; margin-bottom: 16px;" method="POST" action="{{ route('inventory.import') }}" enctype="multipart/form-data">
        @csrf
        <h2 style="margin-top: 0;">Upload Excel</h2>
        <p class="sub">Use the template. Prices are in KES. Category names must match the Categories sheet.</p>
        <div class="form-grid" style="margin-top: 12px; align-items: end;">
            <div class="field"><label for="file">Spreadsheet</label><input id="file" name="file" type="file" accept=".xlsx,.xls,.csv" required></div>
            <div class="field"><button class="btn" type="submit">Upload items</button></div>
        </div>
        @if ($errors->has('file'))
            <p class="hint">{{ $errors->first('file') }}</p>
        @endif
    </form>

    <form class="search" method="GET" action="{{ route('inventory.index') }}">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search the shelf" autofocus>
        <button class="btn" type="submit">Search</button>
    </form>

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Retail</th>
                    <th>On hand</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    @php $onHand = (float) $product->stocks_sum_quantity; @endphp
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            @if ($product->brand)
                                <div class="sub">{{ $product->brand }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $product->category->name }}
                            @if ($product->subcategory)
                                · {{ $product->subcategory->name }}
                            @endif
                        </td>
                        <td>{{ $product->sku }} <a class="chip" href="{{ route('inventory.label', $product) }}">Label</a></td>
                        <td class="money">KES {{ number_format($product->retail_price, 2) }}</td>
                        <td class="{{ $onHand <= (float) $product->reorder_level ? 'low' : 'num' }}">
                            {{ rtrim(rtrim(number_format($onHand, 3), '0'), '.') }} {{ $product->base_unit }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Nothing matches. Add an item to start the shelf.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($products->hasPages())
            <div class="pager">
                @if (! $products->onFirstPage())
                    <a href="{{ $products->previousPageUrl() }}">Previous</a>
                @endif
                <span>Page {{ $products->currentPage() }}</span>
                @if ($products->hasMorePages())
                    <a href="{{ $products->nextPageUrl() }}">Next</a>
                @endif
            </div>
        @endif
    </div>
@endsection
