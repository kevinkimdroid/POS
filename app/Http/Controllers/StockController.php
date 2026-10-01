<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockTake;
use App\Models\StockTakeLine;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Services\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->allows('stock'), 403);

        $low = Product::with('category')->withSum('stocks', 'quantity')->orderBy('name')->get()
            ->filter(fn (Product $product) => (float) $product->stocks_sum_quantity <= (float) $product->reorder_level);

        return view('stock.index', [
            'branches' => Branch::orderBy('name')->get(),
            'products' => Product::with('variants')->orderBy('name')->get(),
            'low' => $low,
            'movements' => StockMovement::with(['product', 'branch', 'user'])->latest()->limit(12)->get(),
            'counts' => StockTake::with('branch')->latest()->limit(8)->get(),
        ]);
    }

    public function adjust(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('stock'), 403);
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'in:damage,theft,breakage,count correction'],
            'note' => ['nullable', 'string', 'max:255'],
            'batch_code' => ['nullable', 'string', 'max:80'],
            'expires_on' => ['nullable', 'date'],
            'serial' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $variantId = Shop::variantForProduct((int) $data['product_id'], $data['product_variant_id'] ?? null);
            $note = trim((string) ($data['note'] ?? ''));
            Shop::moveStock(
                (int) $data['product_id'],
                $variantId,
                (int) $data['branch_id'],
                (float) $data['quantity'],
                'adjustment',
                $data['reason'].($note !== '' ? ': '.$note : ''),
            );

            if (! empty($data['batch_code'] ?? null)) {
                $batch = Batch::firstOrCreate(
                    [
                        'product_id' => $data['product_id'],
                        'product_variant_id' => $variantId,
                        'branch_id' => $data['branch_id'],
                        'code' => $data['batch_code'],
                    ],
                    ['expires_on' => $data['expires_on'] ?? null, 'quantity' => 0],
                );
                $batch->quantity = (float) $batch->quantity + (float) $data['quantity'];
                $batch->expires_on = $data['expires_on'] ?? $batch->expires_on;
                $batch->save();
            }

            if (! empty($data['serial'] ?? null)) {
                SerialNumber::create([
                    'product_id' => $data['product_id'],
                    'product_variant_id' => $variantId,
                    'serial' => $data['serial'],
                ]);
            }
        });

        return back()->with('status', 'Stock adjusted.');
    }

    public function transfer(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('stock'), 403);
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'from_branch_id' => ['required', 'exists:branches,id', 'different:to_branch_id'],
            'to_branch_id' => ['required', 'exists:branches,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $variantId = Shop::variantForProduct((int) $data['product_id'], $data['product_variant_id'] ?? null);
            $qty = (float) $data['quantity'];
            $transfer = StockTransfer::create([
                'from_branch_id' => $data['from_branch_id'],
                'to_branch_id' => $data['to_branch_id'],
                'user_id' => $request->user()->id,
                'note' => $data['note'] ?? null,
            ]);
            StockTransferLine::create([
                'stock_transfer_id' => $transfer->id,
                'product_id' => $data['product_id'],
                'product_variant_id' => $variantId,
                'quantity' => $qty,
            ]);
            Shop::moveStock((int) $data['product_id'], $variantId, (int) $data['from_branch_id'], -1 * $qty, 'transfer', 'Transfer #'.$transfer->id);
            Shop::moveStock((int) $data['product_id'], $variantId, (int) $data['to_branch_id'], $qty, 'transfer', 'Transfer #'.$transfer->id);
        });

        return back()->with('status', 'Stock transferred.');
    }

    public function startCount(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('stock'), 403);
        $data = $request->validate(['branch_id' => ['required', 'exists:branches,id']]);

        $take = DB::transaction(function () use ($data, $request) {
            $take = StockTake::create([
                'branch_id' => $data['branch_id'],
                'user_id' => $request->user()->id,
            ]);
            $stocks = Stock::where('branch_id', $data['branch_id'])->get();
            foreach ($stocks as $stock) {
                StockTakeLine::create([
                    'stock_take_id' => $take->id,
                    'product_id' => $stock->product_id,
                    'product_variant_id' => $stock->product_variant_id,
                    'expected' => $stock->quantity,
                ]);
            }

            return $take;
        });

        return redirect()->route('stock.count', $take);
    }

    public function count(StockTake $stockTake): View
    {
        abort_unless(auth()->user()->allows('stock'), 403);
        $stockTake->load(['lines.product', 'lines.variant', 'branch']);

        return view('stock.count', ['take' => $stockTake]);
    }

    public function postCount(Request $request, StockTake $stockTake): RedirectResponse
    {
        abort_unless($request->user()->allows('stock'), 403);
        $data = $request->validate([
            'counted' => ['required', 'array'],
            'counted.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($stockTake, $data) {
            foreach ($stockTake->lines as $line) {
                if (! array_key_exists($line->id, $data['counted']) || $data['counted'][$line->id] === null || $data['counted'][$line->id] === '') {
                    continue;
                }
                $counted = (float) $data['counted'][$line->id];
                $line->counted = $counted;
                $line->save();
                $delta = round($counted - (float) $line->expected, 3);
                if (abs($delta) > 0.0001) {
                    Shop::moveStock($line->product_id, $line->product_variant_id, $stockTake->branch_id, $delta, 'count', 'Stock take #'.$stockTake->id);
                }
            }
            $stockTake->status = 'posted';
            $stockTake->save();
        });

        return redirect()->route('stock.index')->with('status', 'Stock take posted.');
    }
}
