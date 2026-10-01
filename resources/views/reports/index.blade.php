@extends('layouts.app')
@section('title', 'Reports · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Books</p><h1>Reports</h1><p class="sub">Sales, margin, movement, and who still owes.</p></div></div>
    <div class="grid">
        <article class="panel stat"><span>Today</span><strong>KES {{ number_format((float) $sales['today'], 0) }}</strong></article>
        <article class="panel stat"><span>This week</span><strong>KES {{ number_format((float) $sales['week'], 0) }}</strong></article>
        <article class="panel stat"><span>This month</span><strong>KES {{ number_format((float) $sales['month'], 0) }}</strong></article>
        <article class="panel stat"><span>Stock at cost</span><strong>KES {{ number_format((float) $valuation, 0) }}</strong></article>
    </div>
    <h2>Margin by category</h2>
    <div class="panel"><table><thead><tr><th>Category</th><th>Revenue</th><th>Cost</th><th>Margin</th></tr></thead><tbody>
        @forelse ($margin as $row)
            @php $profit = (float) $row->revenue - (float) $row->cost; @endphp
            <tr><td>{{ $row->category }}</td><td>{{ number_format($row->revenue, 2) }}</td><td>{{ number_format($row->cost, 2) }}</td><td>{{ number_format($profit, 2) }}</td></tr>
        @empty
            <tr><td colspan="4">No completed sales yet.</td></tr>
        @endforelse
    </tbody></table></div>
    <div class="split">
        <section>
            <h2>Fast movers, 30 days</h2>
            <div class="panel"><table><tbody>
                @forelse ($movers as $row)<tr><td>{{ $row->description }}</td><td class="num">{{ rtrim(rtrim(number_format($row->qty, 3), '0'), '.') }}</td></tr>@empty<tr><td>No movement yet.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section>
            <h2>Dead stock, 90 days</h2>
            <div class="panel"><table><tbody>
                @forelse ($dead as $product)<tr><td>{{ $product->name }}</td><td>{{ rtrim(rtrim(number_format((float) $product->stocks_sum_quantity, 3), '0'), '.') }} on hand</td></tr>@empty<tr><td>Nothing sitting idle.</td></tr>@endforelse
            </tbody></table></div>
        </section>
        <section>
            <h2>By cashier</h2>
            <div class="panel"><table><tbody>@forelse ($byCashier as $row)<tr><td>{{ $row->name }}</td><td class="money">KES {{ number_format($row->total, 2) }}</td></tr>@empty<tr><td>No sales recorded yet.</td></tr>@endforelse</tbody></table></div>
        </section>
        <section>
            <h2>By payment method</h2>
            <div class="panel"><table><tbody>@forelse ($byMethod as $row)<tr><td>{{ ucfirst($row->method) }}</td><td class="money">KES {{ number_format($row->total, 2) }}</td></tr>@empty<tr><td>No payments recorded yet.</td></tr>@endforelse</tbody></table></div>
        </section>
    </div>
    <h2>Debtors</h2>
    <div class="panel"><table><thead><tr><th>Customer</th><th>Owes</th><th>Age</th></tr></thead><tbody>
        @forelse ($aging as $row)<tr><td>{{ $row['name'] }}</td><td>KES {{ number_format($row['balance'], 2) }}</td><td>{{ $row['bucket'] }} days</td></tr>@empty<tr><td colspan="3">No open credit.</td></tr>@endforelse
    </tbody></table></div>
@endsection
