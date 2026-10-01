@extends('layouts.app')
@section('title', 'Purchasing · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Buying</p><h1>Purchasing</h1><p class="sub">Suppliers, orders, goods received, and what you still owe.</p></div></div>
    @if ($errors->any()) <div class="error">{{ $errors->first() }}</div> @endif
    <div class="form-grid">
        <form class="panel" style="padding: 16px;" method="POST" action="{{ route('purchasing.suppliers.store') }}">
            @csrf
            <h2 style="margin-top: 0;">Supplier</h2>
            <div class="field"><label>Name</label><input name="name" required></div>
            <div class="field"><label>Phone</label><input name="phone"></div>
            <div class="field"><label>Email</label><input name="email" type="email"></div>
            <div class="field"><label>Terms</label><input name="payment_terms" placeholder="30 days"></div>
            <button class="btn" type="submit">Save supplier</button>
        </form>
        <form class="panel" style="padding: 16px;" method="POST" action="{{ route('purchasing.orders.store') }}">
            @csrf
            <h2 style="margin-top: 0;">Purchase order</h2>
            <div class="field"><label>Supplier</label><select name="supplier_id" required>@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="field"><label>Deliver to</label><select name="branch_id">@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="field"><label>Item</label><select name="product_id">@foreach ($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
            <div class="field"><label>Size / finish</label><select name="product_variant_id"><option value="">—</option>@foreach ($products as $product)@foreach ($product->variants as $variant)<option value="{{ $variant->id }}" data-product="{{ $product->id }}">{{ $variant->name }}</option>@endforeach @endforeach</select></div>
            <div class="field"><label>Quantity</label><input name="quantity" type="number" min="0.001" step="0.001" required></div>
            <div class="field"><label>Unit cost</label><input name="unit_cost" type="number" min="0" step="0.01" required></div>
            <button class="btn" type="submit">Create order</button>
        </form>
    </div>
    <div class="panel" style="margin-top: 16px;">
        <table>
            <thead><tr><th>Order</th><th>Supplier</th><th>Owed</th><th>Receive</th></tr></thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->id }} · {{ $order->status }}</td>
                        <td>{{ $order->supplier->name }}</td>
                        <td>KES {{ number_format($order->supplier->balance, 2) }}</td>
                        <td>
                            @foreach ($order->lines as $line)
                                <form method="POST" action="{{ route('purchasing.receive', $order) }}" style="display: flex; gap: 6px; align-items: center; margin-bottom: 6px;">
                                    @csrf
                                    <input type="hidden" name="line_id" value="{{ $line->id }}">
                                    <span>{{ $line->product->name }}{{ $line->variant ? ' '.$line->variant->name : '' }} {{ rtrim(rtrim(number_format($line->received, 3), '0'), '.') }}/{{ rtrim(rtrim(number_format($line->quantity, 3), '0'), '.') }}</span>
                                    <input name="quantity" type="number" min="0.001" step="0.001" placeholder="Qty" style="width: 80px;">
                                    <input name="transport_cost" type="number" min="0" step="0.01" placeholder="Transport" style="width: 100px;">
                                    <input name="handling_cost" type="number" min="0" step="0.01" placeholder="Handling" style="width: 100px;">
                                    <button class="btn" type="submit">Receive</button>
                                </form>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <form class="panel" style="padding: 16px; margin-top: 16px;" method="POST" action="{{ route('purchasing.pay') }}">
        @csrf
        <h2 style="margin-top: 0;">Pay a supplier</h2>
        <div class="form-grid">
            <div class="field"><label>Supplier</label><select name="supplier_id">@foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }} · KES {{ number_format($supplier->balance, 2) }}</option>@endforeach</select></div>
            <div class="field"><label>Amount</label><input name="amount" type="number" min="0.01" step="0.01" required></div>
            <div class="field"><label>Method</label><select name="method"><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="bank">Bank</option><option value="cheque">Cheque</option></select></div>
            <div class="field"><label>Reference</label><input name="reference"></div>
        </div>
        <button class="btn" type="submit">Record payment</button>
    </form>
@endsection
