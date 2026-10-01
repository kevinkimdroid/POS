<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuantityPrice;
use App\Models\Stock;
use App\Models\Supplier;
use App\Services\InventoryImport;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with(['category', 'subcategory'])
            ->withSum('stocks', 'quantity')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like)
                        ->orWhere('brand', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('inventory.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('inventory.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        $product = DB::transaction(function () use ($request, $data) {
            $category = Category::findOrFail($data['category_id']);
            $sku = $data['sku'] ?: $this->nextSku($category);
            $this->assertSkuAvailable($sku);

            if (! empty($data['barcode'])) {
                $this->assertBarcodeAvailable($data['barcode']);
            }

            $product = Product::create([
                ...$this->productAttributes($data, $request),
                'sku' => $sku,
                'image_path' => $this->storeImage($request),
            ]);

            $this->storeVariantsAndStock($product, $data, $sku);

            PriceHistory::create([
                'product_id' => $product->id,
                'buying_price' => $product->buying_price,
                'landed_cost' => $product->buying_price,
                'source' => 'opening',
            ]);

            if ($request->filled('break_qty') && $request->filled('break_price')) {
                QuantityPrice::create([
                    'product_id' => $product->id,
                    'price_level' => 'retail',
                    'min_quantity' => $request->input('break_qty'),
                    'price' => $request->input('break_price'),
                ]);
            }

            return $product;
        });

        return redirect()
            ->route('inventory.index')
            ->with('status', $product->name.' is on the shelf and ready at the counter.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => Category::whereNull('parent_id')->orderBy('name')->get(),
            'subcategories' => Category::whereNotNull('parent_id')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'units' => ['Piece', 'Bag', 'Kilogram', 'Metre', 'Roll', 'Length', 'Litre', 'Carton', 'Bundle', 'Box'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:40'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'brand' => ['nullable', 'string', 'max:80'],
            'base_unit' => ['required', 'string', 'max:40'],
            'bulk_unit' => ['nullable', 'string', 'max:40'],
            'units_per_bulk' => ['nullable', 'numeric', 'gt:0'],
            'buying_price' => ['required', 'numeric', 'min:0'],
            'retail_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['required', 'numeric', 'min:0'],
            'contractor_price' => ['required', 'numeric', 'min:0'],
            'tax_type' => ['required', Rule::in(['vat_16', 'zero', 'exempt'])],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'opening_stock' => ['nullable', 'array'],
            'opening_stock.*' => ['nullable', 'numeric', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['nullable', 'string', 'max:80'],
            'variants.*.sku' => ['nullable', 'string', 'max:40'],
            'variants.*.barcode' => ['nullable', 'string', 'max:64'],
            'variants.*.buying_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.retail_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.contractor_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.opening_stock' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'break_qty' => ['nullable', 'numeric', 'gt:0'],
            'break_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data += [
            'subcategory_id' => null,
            'sku' => null,
            'barcode' => null,
            'brand' => null,
            'bulk_unit' => null,
            'units_per_bulk' => null,
            'reorder_level' => 0,
            'supplier_id' => null,
            'opening_stock' => [],
            'variants' => [],
        ];

        if (filled($data['bulk_unit'] ?? null) && blank($data['units_per_bulk'] ?? null)) {
            throw ValidationException::withMessages([
                'units_per_bulk' => 'Enter how many base units are in one '.$data['bulk_unit'].'.',
            ]);
        }

        if (! empty($data['subcategory_id'])) {
            $child = Category::find($data['subcategory_id']);
            if (! $child || (int) $child->parent_id !== (int) $data['category_id']) {
                throw ValidationException::withMessages([
                    'subcategory_id' => 'Choose a subcategory from the selected category.',
                ]);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function productAttributes(array $data, Request $request): array
    {
        return [
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?: null,
            'supplier_id' => $data['supplier_id'] ?: null,
            'name' => $data['name'],
            'barcode' => $data['barcode'] ?: null,
            'brand' => $data['brand'] ?: null,
            'base_unit' => $data['base_unit'],
            'bulk_unit' => $data['bulk_unit'] ?: null,
            'units_per_bulk' => $data['units_per_bulk'] ?: null,
            'buying_price' => $data['buying_price'],
            'retail_price' => $data['retail_price'],
            'wholesale_price' => $data['wholesale_price'],
            'contractor_price' => $data['contractor_price'],
            'tax_type' => $data['tax_type'],
            'reorder_level' => $data['reorder_level'] ?? 0,
            'track_serial_batch' => $request->boolean('track_serial_batch'),
            'allow_fractional' => $request->boolean('allow_fractional'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function storeVariantsAndStock(Product $product, array $data, string $sku): void
    {
        $variants = collect($data['variants'] ?? [])
            ->filter(fn ($row) => filled($row['name'] ?? null))
            ->values();

        $mainBranch = Branch::orderBy('id')->first();

        if ($variants->isEmpty()) {
            foreach (Branch::all() as $branch) {
                Stock::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity' => $data['opening_stock'][$branch->id] ?? 0,
                ]);
            }

            return;
        }

        foreach ($variants as $index => $row) {
            $variantSku = filled($row['sku'] ?? null) ? $row['sku'] : $sku.'-'.($index + 1);
            $this->assertSkuAvailable($variantSku);
            if (! empty($row['barcode'])) {
                $this->assertBarcodeAvailable($row['barcode']);
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'name' => $row['name'],
                'sku' => $variantSku,
                'barcode' => $row['barcode'] ?: null,
                'buying_price' => $row['buying_price'] ?? $product->buying_price,
                'retail_price' => $row['retail_price'] ?? $product->retail_price,
                'wholesale_price' => $row['wholesale_price'] ?? $product->wholesale_price,
                'contractor_price' => $row['contractor_price'] ?? $product->contractor_price,
            ]);

            if ($mainBranch) {
                Stock::create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'branch_id' => $mainBranch->id,
                    'quantity' => $row['opening_stock'] ?? 0,
                ]);
            }
        }
    }

    private function nextSku(Category $category): string
    {
        $prefix = $category->code;
        $count = Product::where('sku', 'like', $prefix.'-%')->count()
            + ProductVariant::where('sku', 'like', $prefix.'-%')->count();

        return sprintf('%s-%04d', $prefix, $count + 1);
    }

    private function assertSkuAvailable(string $sku): void
    {
        $taken = Product::where('sku', $sku)->exists()
            || ProductVariant::where('sku', $sku)->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'sku' => 'SKU '.$sku.' is already in use.',
            ]);
        }
    }

    private function assertBarcodeAvailable(string $barcode): void
    {
        $taken = Product::where('barcode', $barcode)->exists()
            || ProductVariant::where('barcode', $barcode)->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'barcode' => 'That barcode is already on another item.',
            ]);
        }
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $name = Str::uuid()->toString().'.'.$file->extension();
        $directory = public_path('uploads/products');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $file->move($directory, $name);

        return 'uploads/products/'.$name;
    }

    public function template(InventoryImport $import): BinaryFileResponse
    {
        return response()->download(
            $import->template(),
            'vancha-inventory-template.xlsx',
        );
    }

    public function import(Request $request, InventoryImport $import): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $result = $import->import($data['file']);
        $message = $result['imported'].' item'.($result['imported'] === 1 ? '' : 's').' saved from Excel.';
        if ($result['skipped'] !== []) {
            $message .= ' Skipped: '.implode(' ', $result['skipped']);
        }

        return back()->with('status', $message);
    }
}
