<?php

namespace App\Http\Controllers;

use App\Models\DeliveryNote;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleReturn;
use App\Models\SaleReturnLine;
use App\Services\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function receipt(Sale $sale): View
    {
        $sale->load(['lines', 'payments', 'customer', 'user']);

        return view('sales.receipt', ['sale' => $sale]);
    }

    public function label(int $product): View
    {
        $product = \App\Models\Product::with('variants')->findOrFail($product);

        return view('inventory.label', ['product' => $product]);
    }

    public function returns(): View
    {
        abort_unless(auth()->user()->allows('refund'), 403);

        return view('sales.returns', [
            'sales' => Sale::with('lines')->where('status', 'completed')->latest()->limit(15)->get(),
        ]);
    }

    public function refund(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($request->user()->allows('refund'), 403);
        $data = $request->validate([
            'sale_line_id' => ['required', 'exists:sale_lines,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        DB::transaction(function () use ($request, $sale, $data) {
            $line = SaleLine::where('sale_id', $sale->id)->lockForUpdate()->findOrFail($data['sale_line_id']);
            $already = (float) SaleReturnLine::where('sale_line_id', $line->id)->sum('quantity');
            $qty = (float) $data['quantity'];
            if ($already + $qty - (float) $line->quantity > 0.0001) {
                throw ValidationException::withMessages(['quantity' => 'That is more than was sold.']);
            }

            $share = $qty / (float) $line->quantity;
            $amount = round((float) $line->line_total * $share, 2);
            $base = (float) $line->base_quantity * $share;
            $method = Payment::where('sale_id', $sale->id)->orderByDesc('amount')->value('method') ?? 'cash';

            Shop::moveStock($line->product_id, $line->product_variant_id, $sale->branch_id, $base, 'refund', 'Return on '.$sale->receipt_no);

            if ($method === 'credit' && $sale->customer) {
                $sale->customer->balance = max(0, (float) $sale->customer->balance - $amount);
                $sale->customer->save();
            }

            $return = SaleReturn::create([
                'sale_id' => $sale->id,
                'user_id' => $request->user()->id,
                'method' => $method,
                'total' => $amount,
            ]);
            SaleReturnLine::create([
                'sale_return_id' => $return->id,
                'sale_line_id' => $line->id,
                'quantity' => $qty,
                'amount' => $amount,
            ]);
        });

        return back()->with('status', 'Return posted against '.$sale->receipt_no.'.');
    }

    public function void(Request $request, Sale $sale): RedirectResponse
    {
        abort_unless($request->user()->allows('void'), 403);

        DB::transaction(function () use ($sale) {
            $sale = Sale::lockForUpdate()->find($sale->id);
            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Only a completed sale can be voided.']);
            }
            foreach ($sale->lines as $line) {
                Shop::moveStock($line->product_id, $line->product_variant_id, $sale->branch_id, (float) $line->base_quantity, 'void', 'Void '.$sale->receipt_no);
            }
            if ($sale->customer) {
                $credit = (float) $sale->payments()->where('method', 'credit')->sum('amount');
                $sale->customer->balance = max(0, (float) $sale->customer->balance - $credit);
                $sale->customer->save();
            }
            $sale->status = 'void';
            $sale->save();
        });

        return back()->with('status', $sale->receipt_no.' voided.');
    }

    public function deliveries(): View
    {
        return view('sales.deliveries', [
            'notes' => DeliveryNote::with('sale')->latest()->limit(20)->get(),
        ]);
    }

    public function dispatchNote(DeliveryNote $note): RedirectResponse
    {
        $note->update([
            'status' => $note->status === 'pending' ? 'dispatched' : 'delivered',
            'dispatched_at' => $note->dispatched_at ?? now(),
        ]);

        return back()->with('status', 'Delivery updated.');
    }
}
