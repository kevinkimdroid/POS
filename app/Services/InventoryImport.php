<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Stock;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InventoryImport
{
    /**
     * @return array{imported: int, skipped: list<string>}
     */
    public function import(UploadedFile $file): array
    {
        $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        if ($rows === []) {
            return ['imported' => 0, 'skipped' => ['The file has no rows.']];
        }

        $headers = array_map(fn ($cell) => $this->header((string) $cell), array_shift($rows));
        $main = Branch::query()->orderBy('id')->first();
        $imported = 0;
        $skipped = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $data = [];
            foreach ($headers as $column => $name) {
                if ($name === '') {
                    continue;
                }
                $data[$name] = isset($row[$column]) ? trim((string) $row[$column]) : '';
            }

            if ($this->blank($data)) {
                continue;
            }

            $reason = $this->storeRow($data, $main);
            if ($reason) {
                $skipped[] = 'Row '.$line.': '.$reason;
                continue;
            }
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => array_slice($skipped, 0, 12)];
    }

    public function template(): string
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Items');
        $headers = [
            'Name', 'Category', 'Subcategory', 'Brand', 'SKU', 'Barcode', 'Unit',
            'Buying price', 'Retail price', 'Wholesale price', 'Contractor price',
            'Reorder level', 'Stock', 'Fractional',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            'Bamburi Nguvu 42.5', 'Building Materials', 'Cement', 'Bamburi', 'BLD-CEM-425', '', 'Bag',
            760, 950, 884, 836, 40, 100, 'no',
        ], null, 'A2');
        $sheet->fromArray([
            'Binding wire', 'Building Materials', 'Iron sheets', '', 'BLD-WIR-BND', '', 'Kilogram',
            165, 220, '', '', 25, 50, 'yes',
        ], null, 'A3');

        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('A1:N1')->getFont()->setBold(true);

        $guide = $book->createSheet();
        $guide->setTitle('Categories');
        $guide->fromArray(['Category', 'Subcategory'], null, 'A1');
        $row = 2;
        Category::query()->whereNull('parent_id')->orderBy('name')->with('children')->get()->each(function (Category $category) use ($guide, &$row) {
            foreach ($category->children as $child) {
                $guide->fromArray([$category->name, $child->name], null, 'A'.$row);
                $row++;
            }
        });
        $guide->getStyle('A1:B1')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setAutoSize(true);
        $guide->getColumnDimension('B')->setAutoSize(true);
        $book->setActiveSheetIndex(0);

        $path = storage_path('app/vancha-inventory-template.xlsx');
        (new Xlsx($book))->save($path);

        return $path;
    }

    /**
     * @param  array<string, string>  $data
     */
    private function storeRow(array $data, ?Branch $main): ?string
    {
        $name = $data['name'] ?? '';
        if ($name === '') {
            return 'name is missing.';
        }

        $category = Category::query()
            ->whereNull('parent_id')
            ->whereRaw('lower(name) = ?', [mb_strtolower($data['category'] ?? '')])
            ->first();
        if (! $category) {
            return 'category must be one of the names on the Categories sheet.';
        }

        $subcategory = null;
        if (($data['subcategory'] ?? '') !== '') {
            $subcategory = Category::query()
                ->where('parent_id', $category->id)
                ->whereRaw('lower(name) = ?', [mb_strtolower($data['subcategory'])])
                ->first();
            if (! $subcategory) {
                return 'subcategory is not under '.$category->name.'.';
            }
        }

        $retail = $this->money($data['retail_price'] ?? '');
        $buying = $this->money($data['buying_price'] ?? '');
        if ($retail === null && $buying === null) {
            return 'enter a retail price or a buying price.';
        }
        $retail ??= round($buying / 0.75, 2);
        $buying ??= round($retail * 0.75, 2);
        $wholesale = $this->money($data['wholesale_price'] ?? '') ?? round($retail * 0.93, 2);
        $contractor = $this->money($data['contractor_price'] ?? '') ?? round($retail * 0.88, 2);

        $unit = $data['unit'] ?? '';
        if ($unit === '') {
            return 'unit is missing.';
        }

        $sku = $data['sku'] ?? '';
        if ($sku === '') {
            $sku = $this->nextSku($category);
        }
        if (ProductVariant::query()->where('sku', $sku)->exists()) {
            return 'SKU '.$sku.' is already used by a size.';
        }

        $barcode = $data['barcode'] ?? '';
        if ($barcode !== '' && $this->barcodeTaken($barcode, $sku)) {
            return 'barcode '.$barcode.' is already used.';
        }

        DB::transaction(function () use ($name, $category, $subcategory, $data, $sku, $barcode, $unit, $buying, $retail, $wholesale, $contractor, $main) {
            $product = Product::query()->where('sku', $sku)->first() ?? new Product;
            $product->fill([
                'category_id' => $category->id,
                'subcategory_id' => $subcategory?->id,
                'name' => $name,
                'sku' => $sku,
                'barcode' => $barcode !== '' ? $barcode : $product->barcode,
                'brand' => ($data['brand'] ?? '') !== '' ? $data['brand'] : null,
                'base_unit' => $unit,
                'buying_price' => $buying,
                'retail_price' => $retail,
                'wholesale_price' => $wholesale,
                'contractor_price' => $contractor,
                'tax_type' => 'vat_16',
                'reorder_level' => $this->money($data['reorder_level'] ?? '') ?? 0,
                'allow_fractional' => $this->yes($data['fractional'] ?? ''),
                'track_serial_batch' => (bool) $product->track_serial_batch,
            ]);
            $product->save();

            PriceHistory::create([
                'product_id' => $product->id,
                'buying_price' => $buying,
                'landed_cost' => $buying,
                'source' => 'import',
            ]);

            if ($main && array_key_exists('stock', $data) && $data['stock'] !== '') {
                $stock = Stock::query()->firstOrNew([
                    'product_id' => $product->id,
                    'product_variant_id' => null,
                    'branch_id' => $main->id,
                ]);
                $stock->quantity = $this->money($data['stock']) ?? 0;
                $stock->save();
            }
        });

        return null;
    }

    private function header(string $value): string
    {
        $key = str_replace([' ', '-'], '_', mb_strtolower(trim($value)));

        return match ($key) {
            'item', 'item_name', 'product' => 'name',
            'cat' => 'category',
            'sub_category', 'sub' => 'subcategory',
            'base_unit', 'uom' => 'unit',
            'buy', 'cost', 'buying' => 'buying_price',
            'retail', 'selling_price', 'price' => 'retail_price',
            'wholesale' => 'wholesale_price',
            'contractor' => 'contractor_price',
            'reorder', 'reorder_qty' => 'reorder_level',
            'qty', 'quantity', 'on_hand', 'opening_stock' => 'stock',
            'allow_fractional' => 'fractional',
            default => $key,
        };
    }

    /**
     * @param  array<string, string>  $data
     */
    private function blank(array $data): bool
    {
        return implode('', $data) === '';
    }

    private function money(string $value): ?float
    {
        $clean = str_replace([',', 'KES', 'kes', 'Ksh', 'ksh'], '', $value);
        $clean = trim($clean);
        if ($clean === '') {
            return null;
        }

        return is_numeric($clean) ? round((float) $clean, 2) : null;
    }

    private function yes(string $value): bool
    {
        return in_array(mb_strtolower($value), ['1', 'yes', 'y', 'true'], true);
    }

    private function nextSku(Category $category): string
    {
        $count = Product::where('sku', 'like', $category->code.'-%')->count() + 1;

        return sprintf('%s-%04d', $category->code, $count);
    }

    private function barcodeTaken(string $barcode, string $sku): bool
    {
        return Product::query()->where('barcode', $barcode)->where('sku', '!=', $sku)->exists();
    }
}
