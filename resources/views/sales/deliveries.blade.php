@extends('layouts.app')
@section('title', 'Deliveries · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Sell</p><h1>Deliveries</h1><p class="sub">Cement, sheets, and timber leaving the yard.</p></div></div>
    <div class="panel">
        <table>
            <thead><tr><th>Receipt</th><th>Address</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($notes as $note)
                    <tr>
                        <td>{{ $note->sale->receipt_no }}</td>
                        <td>{{ $note->address }}</td>
                        <td>{{ $note->status }}</td>
                        <td>
                            @if ($note->status !== 'delivered')
                                <form method="POST" action="{{ route('deliveries.update', $note) }}">@csrf<button class="btn" type="submit">{{ $note->status === 'pending' ? 'Dispatch' : 'Delivered' }}</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No delivery notes yet. Add an address on the counter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
