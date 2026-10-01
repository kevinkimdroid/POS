@extends('layouts.app')
@section('title', 'Shift · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Books</p><h1>Shift</h1><p class="sub">Open the drawer, sell, then count the cash.</p></div></div>
    @if ($open)
        <div class="panel" style="padding: 16px; margin-bottom: 16px;">
            <p>Open at {{ $open->branch->name }} since {{ $open->opened_at->format('H:i') }} with KES {{ number_format($open->opening_float, 2) }}.</p>
            <table><tbody>@foreach ($methods as $row)<tr><td>{{ ucfirst($row->method) }}</td><td>KES {{ number_format($row->total, 2) }}</td></tr>@endforeach</tbody></table>
            <form method="POST" action="{{ route('shifts.close') }}">
                @csrf
                <div class="field"><label>Cash counted</label><input name="counted_cash" type="number" min="0" step="0.01" required></div>
                <button class="btn" type="submit">Close shift</button>
            </form>
        </div>
    @else
        <form class="panel" style="padding: 16px; margin-bottom: 16px;" method="POST" action="{{ route('shifts.open') }}">
            @csrf
            <div class="field"><label>Branch</label><select name="branch_id">@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
            <div class="field"><label>Opening float</label><input name="opening_float" type="number" min="0" step="0.01" value="0" required></div>
            <button class="btn" type="submit">Open shift</button>
        </form>
    @endif
    <div class="panel"><table><thead><tr><th>Who</th><th>Opened</th><th>Variance</th></tr></thead><tbody>
        @foreach ($recent as $shift)
            <tr><td>{{ $shift->user->name }}</td><td>{{ $shift->opened_at->format('d M H:i') }}</td><td>{{ $shift->variance === null ? 'Open' : 'KES '.number_format($shift->variance, 2) }}</td></tr>
        @endforeach
    </tbody></table></div>
@endsection
