<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->role === 'admin', 403);

        return view('team.index', [
            'users' => User::orderBy('name')->get(),
            'logs' => AuditLog::with('user')->latest()->limit(30)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:4'],
            'role' => ['required', Rule::in(['admin', 'manager', 'cashier', 'storekeeper'])],
        ]);

        User::create($data);

        return back()->with('status', $data['name'].' can sign in as '.$data['username'].'.');
    }
}
