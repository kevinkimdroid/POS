@extends('layouts.app')
@section('title', 'Team · Vancha')
@section('content')
    <div class="top"><div><p class="eyebrow">Team</p><h1>Team</h1><p class="sub">Admin, manager, cashier, storekeeper. Cashiers can discount up to 5%.</p></div></div>
    <form class="panel" style="padding: 16px; margin-bottom: 16px;" method="POST" action="{{ route('team.store') }}">
        @csrf
        <div class="form-grid">
            <div class="field"><label>Name</label><input name="name" required></div>
            <div class="field"><label>Username</label><input name="username" required></div>
            <div class="field"><label>Email</label><input name="email" type="email" required></div>
            <div class="field"><label>Password</label><input name="password" type="password" required></div>
            <div class="field"><label>Role</label><select name="role"><option value="cashier">Cashier</option><option value="storekeeper">Storekeeper</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
        </div>
        <button class="btn" type="submit">Add user</button>
    </form>
    <div class="panel" style="margin-bottom: 16px;"><table><thead><tr><th>Name</th><th>Username</th><th>Role</th></tr></thead><tbody>
        @foreach ($users as $user)<tr><td>{{ $user->name }}</td><td>{{ $user->username }}</td><td>{{ $user->role }}</td></tr>@endforeach
    </tbody></table></div>
    <h2>Audit</h2>
    <div class="panel"><table><thead><tr><th>When</th><th>Who</th><th>What</th></tr></thead><tbody>
        @forelse ($logs as $log)
            <tr><td>{{ $log->created_at->format('d M H:i') }}</td><td>{{ $log->user->name ?? '—' }}</td><td>{{ $log->action }} · {{ $log->description }}</td></tr>
        @empty
            <tr><td colspan="3">Price changes, voids, refunds, and stock counts show up here.</td></tr>
        @endforelse
    </tbody></table></div>
@endsection
