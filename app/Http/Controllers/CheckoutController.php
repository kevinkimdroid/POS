<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuantityPrice;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SerialNumber;
use App\Models\Shift;
use App\Models\Batch;
use App\Models\DeliveryNote;
use App\Services\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->allows('sell'), 403);

        $preload = [];
        $customerId = null;
        $priceLevel = 'retail';
        $heldId = null;
        $quoteId = null;

        if ($request->filled('hold')) {
            $held = Sale::with('lines.product')->where('status', 'held')->find($request->integer('hold'));
            if ($held) {
                $heldId = $held->id;
                $customerId = $held->customer_id;
                $priceLevel = $held->price_level;
                $preload = $this->linesFromSale($held);
            }
        }

        if ($request->filled('quote')) {
            $quote = Quotation::with('lines')->where('status', 'open')->find($request->integer('quote'));
            if ($quote) {
                $quoteId = $quote->id;
                $customerId = $quote->customer_id;
                $priceLevel = $quote->price_level;
                $preload = $quote->lines->map(fn ($line) => [
                    'product_id' => $line->product_id,
                    'variant_id' => $line->product_variant_id,
                    'name' => $line->description,
                    'variant' => null,
                    'sku' => '',
                    'unit' => 'Piece',
                    'sold_unit' => $line->sold_unit,
                    'quantity' => (float) $line->quantity,
                    'prices' => [
                        'retail' => (float) $line->unit_price,
                        'wholesale' => (float) $line->unit_price,
                        'contractor' => (float) $line->unit_price,
                    ],
                    'fractional' => true,
                    'bulk_unit' => null,
                    'units_per_bulk' => null,
                    'track' => false,
                    'stock' => 0,
                ])->all();
            }
        }

        return view('checkout.index', [
            'branch' => Branch::orderBy('id')->first(),
            'receipt' => session('receipt'),
            'customers' => Customer::orderBy('name')->get(),
            'shift' => $this->ensureShift($request),
            'held' => Sale::with('customer')->where('status', 'held')->latest()->limit(8)->get(),
            'quotes' => Quotation::with('customer')->where('status', 'open')->latest()->limit(8)->get(),
            'preload' => $preload,
            'customerId' => $customerId,
            'priceLevel' => $priceLevel,
            'heldId' => $heldId,
            'quoteId' => $quoteId,
            'maxDiscount' => $request->user()->maxDiscountPercent(),
            'mpesaReady' => Shop::mpesaReady(),
            'catalogue' => $this->catalogue(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json([]);
        }

        $like = '%'.addcslashes($term, '%_\\').'%';

        $products = Product::query()
            ->with(['variants.stocks', 'stocks', 'quantityPrices'])
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('barcode', 'like', $like)
                    ->orWhereHas('variants', function ($variants) use ($like) {
                        $variants->where('name', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('barcode', 'like', $like);
                    });
            })
            ->orderBy('name')
            ->limit(12)
            ->get();

        $rows = [];
        foreach ($products as $product) {
            if ($product->variants->isEmpty()) {
                $rows[] = $this->sellable($product, null, $term);
                continue;
            }

            foreach ($product->variants as $variant) {
                $rows[] = $this->sellable($product, $variant, $term);
            }
        }

        return response()->json(array_slice($rows, 0, 12));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allows('sell'), 403);

        $lines = collect($request->input('lines', []))
            ->filter(function ($line) {
                if (! is_array($line)) {
                    return false;
                }
                $productId = $line['product_id'] ?? null;
                if (! is_numeric($productId) || ! Product::whereKey($productId)->exists()) {
                    return false;
                }

                return (float) ($line['quantity'] ?? 0) > 0;
            })
            ->values()
            ->all();
        $request->merge(['lines' => $lines]);

        if ($lines === []) {
            throw ValidationException::withMessages([
                'lines' => 'Tap an item on the shelf, then set how many you are selling.',
            ]);
        }

        $data = $request->validate([
            'action' => ['nullable', Rule::in(['complete', 'hold', 'quote'])],
            'price_level' => ['required', Rule::in(['retail', 'wholesale', 'contractor'])],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'held_id' => ['nullable', 'integer'],
            'quote_id' => ['nullable', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.variant_id' => ['nullable'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit' => ['nullable', Rule::in(['base', 'bulk'])],
            'lines.*.discount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.batch_code' => ['nullable', 'string', 'max:80'],
            'lines.*.serial' => ['nullable', 'string', 'max:80'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', Rule::in(['cash', 'mpesa', 'card', 'bank', 'cheque', 'credit'])],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:80'],
            'tendered' => ['nullable', 'numeric', 'min:0'],
        ]);

        $action = $data['action'] ?? 'complete';

        if ($action === 'quote') {
            return $this->saveQuote($request, $data);
        }

        $shift = $this->ensureShift($request);

        $branch = Branch::orderBy('id')->first();
        $receipt = DB::transaction(function () use ($request, $data, $action, $shift, $branch) {
            $prepared = $this->prepareLines($data);
            $gross = round(collect($prepared)->sum('line_total'), 2);
            $saleDiscount = round((float) ($data['discount'] ?? 0), 2);
            $this->guardDiscount($request, $saleDiscount, $prepared);
            $net = max(0, round($gross - $saleDiscount, 2));
            $tax = $gross > 0
                ? round(collect($prepared)->sum('tax') * ($net / $gross), 2)
                : 0;

            $sale = Sale::create([
                'user_id' => $request->user()->id,
                'branch_id' => $branch->id,
                'customer_id' => $data['customer_id'] ?? null,
                'shift_id' => $shift?->id,
                'quotation_id' => $data['quote_id'] ?? null,
                'receipt_no' => 'TMP-'.uniqid(),
                'price_level' => $data['price_level'],
                'status' => $action === 'hold' ? 'held' : 'completed',
                'discount' => $saleDiscount,
                'delivery_address' => $data['delivery_address'] ?? null,
                'etims_status' => 'local',
                'subtotal' => $net,
                'tax' => $tax,
                'total' => $net,
            ]);
            $sale->update(['receipt_no' => 'V'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT)]);

            $receiptLines = [];
            foreach ($prepared as $row) {
                if ($action === 'complete') {
                    Shop::moveStock($row['product']->id, $row['variant']?->id, $branch->id, -1 * $row['base_qty'], 'sale', $sale->receipt_no);
                    $this->consumeBatchOrSerial($row, $branch->id);
                }

                $line = SaleLine::create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'product_variant_id' => $row['variant']?->id,
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'line_total' => $row['line_total'],
                    'discount' => $row['discount'],
                    'sold_unit' => $row['sold_unit'],
                    'base_quantity' => $row['base_qty'],
                    'cost_price' => $row['product']->buying_price,
                    'batch_code' => $row['batch_code'],
                    'serial' => $row['serial'],
                ]);

                if ($action === 'complete' && $row['serial']) {
                    SerialNumber::where('serial', $row['serial'])->update([
                        'status' => 'sold',
                        'sale_line_id' => $line->id,
                    ]);
                }

                $receiptLines[] = [
                    'description' => $row['description'],
                    'quantity' => $this->qty($row['quantity']),
                    'unit' => $row['sold_unit'] === 'bulk' ? ($row['product']->bulk_unit ?: 'bulk') : $row['product']->base_unit,
                    'total' => number_format($row['line_total'], 2),
                ];
            }

            $change = 0.0;
            $tendered = 0.0;
            if ($action === 'complete') {
                [$tendered, $change] = $this->takePayments($sale, $data, $net);
                if (! empty($data['delivery_address'] ?? null)) {
                    DeliveryNote::create([
                        'sale_id' => $sale->id,
                        'address' => $data['delivery_address'],
                    ]);
                }
                $customer = $sale->customer;
                if ($customer?->loyalty) {
                    $customer->loyalty_points += (int) floor($net / 100);
                    $customer->save();
                }
                if (! empty($data['quote_id'] ?? null)) {
                    Quotation::where('id', $data['quote_id'])->update(['status' => 'converted']);
                }
                if (! empty($data['held_id'] ?? null)) {
                    Sale::where('status', 'held')->where('id', $data['held_id'])->delete();
                }
            }

            return [
                'number' => $sale->receipt_no,
                'held' => $action === 'hold',
                'lines' => $receiptLines,
                'total' => number_format($net, 2),
                'tax' => number_format($tax, 2),
                'tendered' => number_format($tendered, 2),
                'change' => number_format($change, 2),
                'level' => ucfirst($data['price_level']),
                'etims' => Shop::etimsReady() ? 'ready' : 'local',
                'id' => $sale->id,
            ];
        });

        if ($receipt['held']) {
            return redirect()->route('checkout.index')->with('status', 'Sale '.$receipt['number'].' is on hold.');
        }

        return redirect()->route('checkout.index')->with('receipt', $receipt);
    }

    private function saveQuote(Request $request, array $data): RedirectResponse
    {
        $prepared = $this->prepareLines($data, false);
        $total = round(collect($prepared)->sum('line_total'), 2);

        $quote = Quotation::create([
            'customer_id' => $data['customer_id'] ?? null,
            'user_id' => $request->user()->id,
            'price_level' => $data['price_level'],
            'valid_until' => now()->addDays(14)->toDateString(),
            'total' => $total,
        ]);

        foreach ($prepared as $row) {
            QuotationLine::create([
                'quotation_id' => $quote->id,
                'product_id' => $row['product']->id,
                'product_variant_id' => $row['variant']?->id,
                'description' => $row['description'],
                'quantity' => $row['quantity'],
                'sold_unit' => $row['sold_unit'],
                'unit_price' => $row['unit_price'],
            ]);
        }

        return redirect()->route('quotes.index')->with('status', 'Quote saved for KES '.number_format($total, 2).'.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array<string, mixed>>
     */
    private function prepareLines(array $data, bool $checkStock = true): array
    {
        $prepared = [];
        $branch = Branch::orderBy('id')->first();

        foreach ($data['lines'] as $line) {
            $product = Product::lockForUpdate()->findOrFail($line['product_id']);
            $variant = null;
            if (! empty($line['variant_id'])) {
                $variant = ProductVariant::where('product_id', $product->id)->findOrFail($line['variant_id']);
            } elseif ($product->variants()->exists()) {
                throw ValidationException::withMessages(['lines' => 'Choose a size for '.$product->name.'.']);
            }

            $quantity = (float) $line['quantity'];
            $soldUnit = ($line['unit'] ?? 'base') === 'bulk' ? 'bulk' : 'base';
            if ($soldUnit === 'bulk' && (float) $product->units_per_bulk <= 0) {
                throw ValidationException::withMessages(['lines' => $product->name.' has no bulk conversion.']);
            }

            $baseQty = $soldUnit === 'bulk' ? $quantity * (float) $product->units_per_bulk : $quantity;
            if (! $product->allow_fractional && fmod($baseQty, 1.0) > 0.0001) {
                throw ValidationException::withMessages(['lines' => $product->name.' sells in whole units.']);
            }

            if ($product->track_serial_batch && blank($line['batch_code'] ?? null) && blank($line['serial'] ?? null)) {
                throw ValidationException::withMessages(['lines' => 'Enter a batch or serial for '.$product->name.'.']);
            }

            if ($checkStock && $branch) {
                $onHand = (float) \App\Models\Stock::query()
                    ->where('product_id', $product->id)
                    ->where('branch_id', $branch->id)
                    ->when($variant, fn ($q) => $q->where('product_variant_id', $variant->id), fn ($q) => $q->whereNull('product_variant_id'))
                    ->value('quantity');
                if ($onHand + 0.0001 < $baseQty) {
                    throw ValidationException::withMessages(['lines' => 'Only '.$this->qty($onHand).' left of '.$product->name.'.']);
                }
            }

            $basePrice = $this->unitPrice($product, $variant, $data['price_level'], $baseQty);
            $unitPrice = $soldUnit === 'bulk' ? $basePrice * (float) $product->units_per_bulk : $basePrice;
            $discount = round((float) ($line['discount'] ?? 0), 2);
            $lineTotal = max(0, round($unitPrice * $quantity - $discount, 2));

            $prepared[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'sold_unit' => $soldUnit,
                'base_qty' => $baseQty,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
                'tax' => $product->tax_type === 'vat_16' ? round($lineTotal * 16 / 116, 2) : 0,
                'description' => $product->name.($variant ? ' '.$variant->name : ''),
                'batch_code' => $line['batch_code'] ?? null,
                'serial' => $line['serial'] ?? null,
            ];
        }

        return $prepared;
    }

    private function unitPrice(Product $product, ?ProductVariant $variant, string $level, float $baseQty): float
    {
        $break = QuantityPrice::query()
            ->where('product_id', $product->id)
            ->where('price_level', $level)
            ->where('min_quantity', '<=', $baseQty)
            ->orderByDesc('min_quantity')
            ->first();

        if ($break) {
            return (float) $break->price;
        }

        return $variant ? $variant->priceFor($level) : $product->priceFor($level);
    }

    /**
     * @param  list<array<string, mixed>>  $prepared
     */
    private function guardDiscount(Request $request, float $saleDiscount, array $prepared): void
    {
        $lineDiscount = collect($prepared)->sum('discount');
        $shelf = round(collect($prepared)->sum(fn ($row) => $row['unit_price'] * $row['quantity']), 2);
        $discount = $saleDiscount + $lineDiscount;
        if ($discount <= 0 || $shelf <= 0) {
            return;
        }

        abort_unless($request->user()->allows('discount'), 403);
        $percent = ($discount / $shelf) * 100;
        if ($percent - $request->user()->maxDiscountPercent() > 0.01) {
            throw ValidationException::withMessages([
                'discount' => 'This role can discount up to '.$request->user()->maxDiscountPercent().'%.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: float, 1: float}
     */
    private function takePayments(Sale $sale, array $data, float $net): array
    {
        $rows = collect($data['payments'] ?? [])->filter(fn ($row) => (float) ($row['amount'] ?? 0) > 0)->values();
        if ($rows->isEmpty()) {
            $tendered = round((float) ($data['tendered'] ?? 0), 2);
            if ($tendered + 0.001 < $net) {
                throw ValidationException::withMessages([
                    'tendered' => 'Cash is short by KES '.number_format($net - $tendered, 2).'.',
                ]);
            }
            $rows = collect([['method' => 'cash', 'amount' => $net, 'tendered' => $tendered, 'reference' => null]]);
        }

        if (abs($rows->sum(fn ($row) => (float) $row['amount']) - $net) > 0.05) {
            throw ValidationException::withMessages(['payments' => 'Payments must add up to the sale total.']);
        }

        $tendered = 0.0;
        $change = 0.0;
        foreach ($rows as $row) {
            $method = $row['method'];
            $amount = round((float) $row['amount'], 2);
            if ($method === 'mpesa' && ! Shop::mpesaReady()) {
                throw ValidationException::withMessages([
                    'payments' => 'M-Pesa STK is not configured. Add Daraja keys before taking M-Pesa.',
                ]);
            }
            if ($method === 'credit') {
                $customer = Customer::lockForUpdate()->find($sale->customer_id);
                if (! $customer) {
                    throw ValidationException::withMessages(['customer_id' => 'Choose a customer for a credit sale.']);
                }
                $next = (float) $customer->balance + $amount;
                if ($next - (float) $customer->credit_limit > 0.01) {
                    throw ValidationException::withMessages(['payments' => $customer->name.' is over the credit limit.']);
                }
                $customer->balance = $next;
                $customer->save();
            }

            $cashTendered = $method === 'cash' ? round((float) ($row['tendered'] ?? $amount), 2) : $amount;
            if ($method === 'cash' && $cashTendered + 0.001 < $amount) {
                throw ValidationException::withMessages(['tendered' => 'Cash received is less than the cash portion.']);
            }
            $cashChange = $method === 'cash' ? round($cashTendered - $amount, 2) : 0;
            $tendered += $cashTendered;
            $change += $cashChange;

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $method,
                'amount' => $amount,
                'tendered' => $cashTendered,
                'change_amount' => $cashChange,
                'reference' => $row['reference'] ?? null,
                'status' => 'confirmed',
            ]);
        }

        return [$tendered, $change];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function consumeBatchOrSerial(array $row, int $branchId): void
    {
        if ($row['batch_code']) {
            $batch = Batch::query()
                ->where('product_id', $row['product']->id)
                ->where('branch_id', $branchId)
                ->where('code', $row['batch_code'])
                ->first();
            if ($batch) {
                $batch->quantity = max(0, (float) $batch->quantity - $row['base_qty']);
                $batch->save();
            }
        }

        if ($row['serial']) {
            $serial = SerialNumber::where('serial', $row['serial'])->where('status', 'in_stock')->first();
            if (! $serial) {
                throw ValidationException::withMessages(['lines' => 'Serial '.$row['serial'].' is not in stock.']);
            }
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function linesFromSale(Sale $sale): array
    {
        return $sale->lines->map(function (SaleLine $line) {
            $product = $line->product ?? Product::find($line->product_id);

            return [
                'product_id' => $line->product_id,
                'variant_id' => $line->product_variant_id,
                'name' => $line->description,
                'variant' => null,
                'sku' => $product?->sku,
                'unit' => $product?->base_unit ?? '',
                'sold_unit' => $line->sold_unit,
                'quantity' => (float) $line->quantity,
                'prices' => [
                    'retail' => (float) $line->unit_price,
                    'wholesale' => (float) $line->unit_price,
                    'contractor' => (float) $line->unit_price,
                ],
                'fractional' => (bool) $product?->allow_fractional,
                'bulk_unit' => $product?->bulk_unit,
                'units_per_bulk' => $product?->units_per_bulk,
                'track' => (bool) $product?->track_serial_batch,
                'stock' => 0,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function sellable(Product $product, ?ProductVariant $variant, string $term): array
    {
        $stocks = $variant ? $variant->stocks : $product->stocks->whereNull('product_variant_id');
        $priceSource = $variant ?? $product;
        $barcode = (string) ($variant?->barcode ?: $product->barcode);
        $sku = (string) ($variant?->sku ?: $product->sku);

        return [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'name' => $product->name,
            'variant' => $variant?->name,
            'sku' => $sku,
            'unit' => $product->base_unit,
            'bulk_unit' => $product->bulk_unit,
            'units_per_bulk' => (float) $product->units_per_bulk,
            'stock' => (float) $stocks->sum('quantity'),
            'fractional' => $product->allow_fractional,
            'track' => $product->track_serial_batch,
            'exact' => strcasecmp($barcode, $term) === 0 || strcasecmp($sku, $term) === 0,
            'barcode' => $barcode,
            'prices' => [
                'retail' => $priceSource->priceFor('retail'),
                'wholesale' => $priceSource->priceFor('wholesale'),
                'contractor' => $priceSource->priceFor('contractor'),
            ],
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function catalogue(): array
    {
        $products = Product::query()
            ->with(['category', 'variants.stocks', 'stocks'])
            ->orderBy('name')
            ->get();

        $groups = [];
        foreach ($products as $product) {
            $category = $product->category->name ?? 'Other';
            if ($product->variants->isEmpty()) {
                $groups[$category][] = $this->sellable($product, null, '');
                continue;
            }
            foreach ($product->variants as $variant) {
                $groups[$category][] = $this->sellable($product, $variant, '');
            }
        }

        $order = ['Building Materials', 'Plumbing', 'Electrical', 'Paint', 'Tools', 'Fasteners', 'Roofing'];
        $sorted = [];
        foreach ($order as $name) {
            if (! empty($groups[$name])) {
                $sorted[$name] = $groups[$name];
            }
        }
        foreach ($groups as $name => $rows) {
            if (! isset($sorted[$name])) {
                $sorted[$name] = $rows;
            }
        }

        return $sorted;
    }

    private function ensureShift(Request $request): ?Shift
    {
        $open = Shift::where('user_id', $request->user()->id)->whereNull('closed_at')->first();
        if ($open) {
            return $open;
        }

        $branch = Branch::orderBy('id')->first();
        if (! $branch) {
            return null;
        }

        return Shift::create([
            'user_id' => $request->user()->id,
            'branch_id' => $branch->id,
            'opening_float' => 0,
            'opened_at' => now(),
        ]);
    }

    private function qty(float $quantity): string
    {
        $formatted = rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
