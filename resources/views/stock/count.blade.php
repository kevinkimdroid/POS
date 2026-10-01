@extends('layouts.app')
@section('title', 'Stock take · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Yard</p><h1>Count · {{ $take->branch->name }}</h1><p class="sub">Enter what is on the shelf. Posting writes the difference.</p></div></div>
    <form class="panel" method="POST" action="{{ route('stock.counts.post', $take) }}">
        @csrf
        <table>
            <thead><tr><th>Item</th><th>Expected</th><th>Counted</th></tr></thead>
            <tbody>
                @foreach ($take->lines as $line)
                    <tr>
                        <td>{{ $line->product->name }} {{ $line->variant?->name }}</td>
                        <td>{{ $line->expected }}</td>
                        <td><input name="counted[{{ $line->id }}]" type="number" min="0" step="0.001" value="{{ $line->counted }}" @disabled($take->status === 'posted')></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($take->status !== 'posted')
            <div style="padding: 14px;"><button class="btn" type="submit">Post count</button></div>
        @endif
    </form>
@endsection
