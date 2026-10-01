@extends('layouts.app')
@section('title', 'Customers · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Accounts</p><h1>Customers</h1><p class="sub">Contractors keep their own price and a credit limit. Loyalty is optional.</p></div></div>
    <form class="panel" style="padding: 16px; margin-bottom: 16px;" method="POST" action="{{ route('customers.store') }}">
        @csrf
        <div class="form-grid">
            <div class="field"><label>Name</label><input name="name" required></div>
            <div class="field"><label>Phone</label><input name="phone"></div>
            <div class="field"><label>Email</label><input name="email" type="email"></div>
            <div class="field"><label>Account</label><select name="account_type"><option value="individual">Individual</option><option value="contractor">Contractor</option><option value="company">Company</option></select></div>
            <div class="field"><label>Price</label><select name="price_level"><option value="retail">Retail</option><option value="wholesale">Wholesale</option><option value="contractor">Contractor</option></select></div>
            <div class="field"><label>Credit limit</label><input name="credit_limit" type="number" min="0" step="0.01" value="0"></div>
        </div>
        <label class="checks"><input type="checkbox" name="loyalty" value="1"> Earn loyalty points</label>
        <div style="margin-top: 12px;"><button class="btn" type="submit">Save customer</button></div>
    </form>
    <div class="panel">
        <table>
            <thead><tr><th>Name</th><th>Type</th><th>Price</th><th>Owes</th><th>Points</th></tr></thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td><a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></td>
                        <td>{{ $customer->account_type }}</td>
                        <td>{{ $customer->price_level }}</td>
                        <td>KES {{ number_format($customer->balance, 2) }}</td>
                        <td>{{ $customer->loyalty ? $customer->loyalty_points : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
