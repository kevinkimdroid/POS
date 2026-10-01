<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\GoodsReceipt;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->allows('purchase'), 403);

        return view('purchasing.index', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'orders' => PurchaseOrder::with(['supplier', 'lines.product', 'lines.variant'])->latest()->limit(20)->get(),
            'products' => Product::with('variants')->orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('purchase'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:120'],
            'payment_terms' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        Supplier::create($data);

        return back()->with('status', $data['name'].' added.');
    }

    public function storeOrder(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('purchase'), 403);
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $order = PurchaseOrder::create([
            'supplier_id' => $data['supplier_id'],
            'branch_id' => $data['branch_id'],
            'user_id' => $request->user()->id,
            'note' => $data['note'] ?? null,
        ]);
        $variantId = Shop::variantForProduct((int) $data['product_id'], $data['product_variant_id'] ?? null);
        PurchaseOrderLine::create([
            'purchase_order_id' => $order->id,
            'product_id' => $data['product_id'],
            'product_variant_id' => $variantId,
            'quantity' => $data['quantity'],
            'unit_cost' => $data['unit_cost'],
        ]);

        return back()->with('status', 'Purchase order #'.$order->id.' created.');
    }

    public function receive(Request $request, PurchaseOrder $order): RedirectResponse
    {
        abort_unless($request->user()->allows('purchase'), 403);
        $data = $request->validate([
            'line_id' => ['required', 'exists:purchase_order_lines,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'handling_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $order, $data) {
            $line = PurchaseOrderLine::where('purchase_order_id', $order->id)->lockForUpdate()->findOrFail($data['line_id']);
            $qty = (float) $data['quantity'];
            $remaining = (float) $line->quantity - (float) $line->received;
            if ($qty - $remaining > 0.0001) {
                abort(422, 'That is more than the order still expects.');
            }

            $transport = (float) ($data['transport_cost'] ?? 0);
            $handling = (float) ($data['handling_cost'] ?? 0);
            $goods = $qty * (float) $line->unit_cost;
            $landed = $qty > 0 ? ((float) $line->unit_cost + (($transport + $handling) / $qty)) : (float) $line->unit_cost;

            $line->received = (float) $line->received + $qty;
            $line->save();

            Shop::moveStock($line->product_id, $line->product_variant_id, $order->branch_id, $qty, 'grn', 'PO #'.$order->id);

            $product = Product::find($line->product_id);
            $product->buying_price = round($landed, 2);
            $product->save();
            Shop::audit('price', 'Buying price for '.$product->name.' set from goods receipt.', [
                'product_id' => $product->id,
                'landed_cost' => $landed,
            ]);
            PriceHistory::create([
                'product_id' => $product->id,
                'buying_price' => $line->unit_cost,
                'landed_cost' => $landed,
                'source' => 'grn',
            ]);

            GoodsReceipt::create([
                'purchase_order_id' => $order->id,
                'user_id' => $request->user()->id,
                'transport_cost' => $transport,
                'handling_cost' => $handling,
            ]);

            $supplier = Supplier::lockForUpdate()->find($order->supplier_id);
            $supplier->balance = (float) $supplier->balance + $goods + $transport + $handling;
            $supplier->save();

            $order->load('lines');
            $open = $order->lines->contains(fn ($row) => (float) $row->received + 0.0001 < (float) $row->quantity);
            $order->status = $open ? 'partial' : 'received';
            $order->save();
        });

        return back()->with('status', 'Goods received onto the shelf.');
    }

    public function pay(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('purchase'), 403);
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,mpesa,bank,cheque'],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $supplier = Supplier::lockForUpdate()->findOrFail($data['supplier_id']);
            $supplier->balance = (float) $supplier->balance - (float) $data['amount'];
            $supplier->save();
            SupplierPayment::create([
                'supplier_id' => $supplier->id,
                'user_id' => $request->user()->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
            ]);
        });

        return back()->with('status', 'Supplier payment recorded.');
    }
}
