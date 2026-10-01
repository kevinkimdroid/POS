<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment;
use App\Models\Shift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->allows('shift'), 403);
        $open = Shift::with('branch')->where('user_id', $request->user()->id)->whereNull('closed_at')->first();

        return view('shifts.index', [
            'open' => $open,
            'branches' => Branch::orderBy('name')->get(),
            'recent' => Shift::with('user')->latest('opened_at')->limit(10)->get(),
            'methods' => $open ? $this->methods($open) : collect(),
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('shift'), 403);
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'opening_float' => ['required', 'numeric', 'min:0'],
        ]);

        if (Shift::where('user_id', $request->user()->id)->whereNull('closed_at')->exists()) {
            return back()->with('status', 'You already have a shift open.');
        }

        Shift::create([
            'user_id' => $request->user()->id,
            'branch_id' => $data['branch_id'],
            'opening_float' => $data['opening_float'],
            'opened_at' => now(),
        ]);

        return back()->with('status', 'Shift opened.');
    }

    public function close(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('shift'), 403);
        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $shift = Shift::where('user_id', $request->user()->id)->whereNull('closed_at')->firstOrFail();
        $cash = (float) Payment::query()
            ->where('method', 'cash')
            ->whereHas('sale', fn ($query) => $query->where('shift_id', $shift->id)->where('status', 'completed'))
            ->sum('amount');
        $change = (float) Payment::query()
            ->where('method', 'cash')
            ->whereHas('sale', fn ($query) => $query->where('shift_id', $shift->id)->where('status', 'completed'))
            ->sum('change_amount');
        $expected = (float) $shift->opening_float + $cash - $change;
        $counted = (float) $data['counted_cash'];

        $shift->update([
            'counted_cash' => $counted,
            'expected_cash' => $expected,
            'variance' => round($counted - $expected, 2),
            'closed_at' => now(),
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Shift closed. Variance KES '.number_format($counted - $expected, 2).'.');
    }

    private function methods(Shift $shift)
    {
        return Payment::query()
            ->selectRaw('method, sum(amount) as total')
            ->whereHas('sale', fn ($query) => $query->where('shift_id', $shift->id)->where('status', 'completed'))
            ->groupBy('method')
            ->get();
    }
}
