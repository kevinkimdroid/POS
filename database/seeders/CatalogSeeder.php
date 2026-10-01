<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SerialNumber;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $main = Branch::firstOrCreate(['name' => 'Main']);
        $yard = Branch::firstOrCreate(['name' => 'Yard']);

        $this->categories();
        $suppliers = $this->suppliers();

        Customer::firstOrCreate(
            ['name' => 'BuildRight Contractors'],
            [
                'phone' => '0712000000',
                'account_type' => 'contractor',
                'price_level' => 'contractor',
                'credit_limit' => 50000,
                'loyalty' => true,
            ],
        );

        $this->clearTradingHistory();

        $barcode = 6161000000001;
        foreach (array_merge($this->catalogue(), $this->moreItems()) as $item) {
            $parent = Category::where('name', $item['cat'])->whereNull('parent_id')->firstOrFail();
            $sub = Category::where('name', $item['sub'])->where('parent_id', $parent->id)->firstOrFail();
            $retail = $item['retail'];
            $buy = $item['buy'] ?? (int) round($retail * 0.75);

            $product = Product::create([
                'category_id' => $parent->id,
                'subcategory_id' => $sub->id,
                'supplier_id' => $suppliers[$item['cat']]->id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'barcode' => isset($item['variants']) ? null : (string) $barcode++,
                'brand' => $item['brand'] ?? null,
                'base_unit' => $item['unit'],
                'bulk_unit' => $item['bulk'] ?? null,
                'units_per_bulk' => $item['per'] ?? null,
                'buying_price' => $buy,
                'retail_price' => $retail,
                'wholesale_price' => (int) round($retail * 0.93),
                'contractor_price' => (int) round($retail * 0.88),
                'tax_type' => 'vat_16',
                'reorder_level' => $item['reorder'] ?? 5,
                'track_serial_batch' => $item['track'] ?? false,
                'allow_fractional' => $item['fractional'] ?? false,
            ]);

            $variants = $item['variants'] ?? [];
            if ($variants === []) {
                $this->putStock($product, null, $main, $item['stock']);
                $this->putStock($product, null, $yard, $item['yard'] ?? 0);
                if (! empty($item['batch'])) {
                    $this->putBatch($product, null, $main, $item['batch'], $item['expires'], $item['stock']);
                }
                foreach ($item['serials'] ?? [] as $serial) {
                    SerialNumber::create([
                        'product_id' => $product->id,
                        'serial' => $serial,
                        'status' => 'in_stock',
                    ]);
                }

                continue;
            }

            foreach ($variants as $variant) {
                $vRetail = $variant['retail'];
                $vBuy = $variant['buy'] ?? (int) round($vRetail * 0.75);
                $row = ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variant['name'],
                    'sku' => $variant['sku'],
                    'barcode' => (string) $barcode++,
                    'buying_price' => $vBuy,
                    'retail_price' => $vRetail,
                    'wholesale_price' => (int) round($vRetail * 0.93),
                    'contractor_price' => (int) round($vRetail * 0.88),
                ]);
                $this->putStock($product, $row, $main, $variant['stock']);
                $this->putStock($product, $row, $yard, $variant['yard'] ?? 0);
                if (! empty($item['batch'])) {
                    $this->putBatch($product, $row, $main, $item['batch'].'-'.$variant['sku'], $item['expires'], $variant['stock']);
                }
            }
        }

        Customer::query()->update(['balance' => 0, 'loyalty_points' => 0]);
        Supplier::query()->update(['balance' => 0]);

        $this->command?->info('Shelf ready: '.Product::count().' items, '.DB::table('sales')->count().' sales.');
    }

    private function categories(): void
    {
        $tree = [
            'Building Materials' => ['code' => 'BLD', 'children' => ['Cement', 'Timber', 'Iron sheets']],
            'Plumbing' => ['code' => 'PLB', 'children' => ['Pipes', 'Fittings']],
            'Electrical' => ['code' => 'ELC', 'children' => ['Cables', 'Fittings']],
            'Paint' => ['code' => 'PNT', 'unit' => 'Litre', 'children' => ['Emulsion', 'Solvent']],
            'Tools' => ['code' => 'TLS', 'children' => ['Hand tools', 'Power tools']],
            'Fasteners' => ['code' => 'FST', 'unit' => 'Kilogram', 'children' => ['Nails', 'Screws']],
            'Roofing' => ['code' => 'RFG', 'children' => ['Sheets', 'Accessories']],
        ];

        foreach ($tree as $name => $meta) {
            $parent = Category::firstOrCreate(
                ['name' => $name, 'parent_id' => null],
                [
                    'code' => $meta['code'],
                    'default_unit' => $meta['unit'] ?? 'Piece',
                    'default_tax_type' => 'vat_16',
                ],
            );

            foreach ($meta['children'] as $child) {
                Category::firstOrCreate(
                    ['name' => $child, 'parent_id' => $parent->id],
                    [
                        'code' => $meta['code'],
                        'default_unit' => $parent->default_unit,
                        'default_tax_type' => 'vat_16',
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, Supplier>
     */
    private function suppliers(): array
    {
        $rows = [
            'Building Materials' => ['Bamburi Depot', '0700111001'],
            'Plumbing' => ['Pipe Traders', '0700111002'],
            'Electrical' => ['Electrical Wholesale', '0700111003'],
            'Paint' => ['Crown Stockist', '0700111004'],
            'Tools' => ['Tool House', '0700111005'],
            'Fasteners' => ['Fastener Mart', '0700111006'],
            'Roofing' => ['Mabati Suppliers', '0700111007'],
        ];

        $suppliers = [];
        foreach ($rows as $category => [$name, $phone]) {
            $suppliers[$category] = Supplier::firstOrCreate(
                ['name' => $name],
                ['phone' => $phone, 'payment_terms' => '30 days'],
            );
        }

        return $suppliers;
    }

    private function clearTradingHistory(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach ([
            'sale_return_lines', 'sale_returns', 'delivery_notes', 'payments', 'sale_lines', 'sales',
            'quotation_lines', 'quotations', 'customer_payments',
            'goods_receipts', 'purchase_order_lines', 'purchase_orders', 'supplier_payments',
            'stock_take_lines', 'stock_takes', 'stock_transfer_lines', 'stock_transfers',
            'stock_movements', 'batches', 'serial_numbers', 'quantity_prices', 'price_histories',
            'stocks', 'product_variants', 'products', 'audit_logs', 'shifts',
        ] as $table) {
            DB::table($table)->delete();
        }
        Schema::enableForeignKeyConstraints();
    }

    private function putStock(Product $product, ?ProductVariant $variant, Branch $branch, float $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        Stock::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'branch_id' => $branch->id,
            'quantity' => $quantity,
        ]);
    }

    private function putBatch(Product $product, ?ProductVariant $variant, Branch $branch, string $code, string $expires, float $quantity): void
    {
        Batch::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'branch_id' => $branch->id,
            'code' => $code,
            'expires_on' => $expires,
            'quantity' => $quantity,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            ['cat' => 'Building Materials', 'sub' => 'Cement', 'name' => 'Bamburi Nguvu 42.5', 'sku' => 'BLD-CEM-425', 'brand' => 'Bamburi', 'unit' => 'Bag', 'bulk' => 'Tonne', 'per' => 20, 'retail' => 950, 'buy' => 760, 'stock' => 200, 'yard' => 80, 'reorder' => 40],
            ['cat' => 'Building Materials', 'sub' => 'Cement', 'name' => 'Bamburi Powerplus 32.5', 'sku' => 'BLD-CEM-325', 'brand' => 'Bamburi', 'unit' => 'Bag', 'bulk' => 'Tonne', 'per' => 20, 'retail' => 820, 'buy' => 650, 'stock' => 120, 'yard' => 40, 'reorder' => 30],
            ['cat' => 'Building Materials', 'sub' => 'Cement', 'name' => 'River sand', 'sku' => 'BLD-SND-01', 'unit' => 'Tonne', 'retail' => 2800, 'buy' => 2100, 'stock' => 18, 'yard' => 12, 'reorder' => 5, 'fractional' => true],
            ['cat' => 'Building Materials', 'sub' => 'Cement', 'name' => 'Machine-cut ballast', 'sku' => 'BLD-BAL-01', 'unit' => 'Tonne', 'retail' => 3400, 'buy' => 2600, 'stock' => 15, 'yard' => 10, 'reorder' => 4, 'fractional' => true],
            ['cat' => 'Building Materials', 'sub' => 'Cement', 'name' => 'Tile adhesive 20kg', 'sku' => 'BLD-ADH-20', 'brand' => 'Sadolin', 'unit' => 'Bag', 'retail' => 1450, 'buy' => 1100, 'stock' => 40, 'reorder' => 10],
            ['cat' => 'Building Materials', 'sub' => 'Timber', 'name' => 'Cypress timber', 'sku' => 'BLD-TMB-CYP', 'unit' => 'Foot', 'fractional' => true, 'retail' => 35, 'reorder' => 80, 'variants' => [
                ['name' => '2x2', 'sku' => 'BLD-TMB-22', 'retail' => 35, 'buy' => 26, 'stock' => 800, 'yard' => 200],
                ['name' => '3x2', 'sku' => 'BLD-TMB-32', 'retail' => 55, 'buy' => 40, 'stock' => 500, 'yard' => 120],
                ['name' => '4x2', 'sku' => 'BLD-TMB-42', 'retail' => 85, 'buy' => 62, 'stock' => 360, 'yard' => 80],
                ['name' => '6x1', 'sku' => 'BLD-TMB-61', 'retail' => 70, 'buy' => 50, 'stock' => 240, 'yard' => 60],
            ]],
            ['cat' => 'Building Materials', 'sub' => 'Timber', 'name' => 'Plywood 18mm 8x4', 'sku' => 'BLD-PLY-18', 'unit' => 'Sheet', 'retail' => 4200, 'buy' => 3400, 'stock' => 25, 'yard' => 8, 'reorder' => 6],
            ['cat' => 'Building Materials', 'sub' => 'Timber', 'name' => 'MDF board 18mm', 'sku' => 'BLD-MDF-18', 'unit' => 'Sheet', 'retail' => 2800, 'buy' => 2200, 'stock' => 15, 'reorder' => 4],
            ['cat' => 'Building Materials', 'sub' => 'Iron sheets', 'name' => 'Twisted steel bar 12m', 'sku' => 'BLD-BAR-12', 'brand' => 'Devki', 'unit' => 'Bar', 'retail' => 1450, 'reorder' => 10, 'variants' => [
                ['name' => 'Y8', 'sku' => 'BLD-BAR-Y8', 'retail' => 680, 'buy' => 540, 'stock' => 40, 'yard' => 20],
                ['name' => 'Y10', 'sku' => 'BLD-BAR-Y10', 'retail' => 980, 'buy' => 780, 'stock' => 50, 'yard' => 20],
                ['name' => 'Y12', 'sku' => 'BLD-BAR-Y12', 'retail' => 1450, 'buy' => 1180, 'stock' => 60, 'yard' => 24],
                ['name' => 'Y16', 'sku' => 'BLD-BAR-Y16', 'retail' => 2400, 'buy' => 1980, 'stock' => 30, 'yard' => 10],
            ]],
            ['cat' => 'Building Materials', 'sub' => 'Iron sheets', 'name' => 'Binding wire', 'sku' => 'BLD-WIR-BND', 'unit' => 'Kilogram', 'bulk' => 'Roll', 'per' => 25, 'retail' => 220, 'buy' => 165, 'stock' => 180, 'yard' => 40, 'reorder' => 25, 'fractional' => true],
            ['cat' => 'Building Materials', 'sub' => 'Iron sheets', 'name' => 'BRC mesh A142', 'sku' => 'BLD-BRC-A142', 'unit' => 'Sheet', 'retail' => 1850, 'buy' => 1450, 'stock' => 30, 'yard' => 10, 'reorder' => 6],

            ['cat' => 'Plumbing', 'sub' => 'Pipes', 'name' => 'PVC pipe Class B', 'sku' => 'PLB-PVC-B', 'brand' => 'Kenpipe', 'unit' => 'Length', 'bulk' => 'Bundle', 'per' => 10, 'retail' => 600, 'reorder' => 20, 'variants' => [
                ['name' => '½"', 'sku' => 'PLB-PVC-050', 'retail' => 320, 'buy' => 240, 'stock' => 80, 'yard' => 20],
                ['name' => '¾"', 'sku' => 'PLB-PVC-075', 'retail' => 600, 'buy' => 450, 'stock' => 100, 'yard' => 30],
                ['name' => '1"', 'sku' => 'PLB-PVC-100', 'retail' => 980, 'buy' => 740, 'stock' => 60, 'yard' => 15],
                ['name' => '1½"', 'sku' => 'PLB-PVC-150', 'retail' => 1650, 'buy' => 1250, 'stock' => 40],
                ['name' => '2"', 'sku' => 'PLB-PVC-200', 'retail' => 2450, 'buy' => 1880, 'stock' => 24],
            ]],
            ['cat' => 'Plumbing', 'sub' => 'Pipes', 'name' => 'PPR pipe', 'sku' => 'PLB-PPR', 'brand' => 'Aquaplast', 'unit' => 'Length', 'retail' => 260, 'reorder' => 15, 'variants' => [
                ['name' => '20mm', 'sku' => 'PLB-PPR-20', 'retail' => 180, 'buy' => 130, 'stock' => 70],
                ['name' => '25mm', 'sku' => 'PLB-PPR-25', 'retail' => 260, 'buy' => 190, 'stock' => 70],
                ['name' => '32mm', 'sku' => 'PLB-PPR-32', 'retail' => 420, 'buy' => 310, 'stock' => 40],
            ]],
            ['cat' => 'Plumbing', 'sub' => 'Pipes', 'name' => 'GI pipe medium 6m', 'sku' => 'PLB-GI', 'unit' => 'Length', 'retail' => 1950, 'reorder' => 8, 'variants' => [
                ['name' => '½"', 'sku' => 'PLB-GI-050', 'retail' => 1450, 'buy' => 1120, 'stock' => 20, 'yard' => 8],
                ['name' => '¾"', 'sku' => 'PLB-GI-075', 'retail' => 1950, 'buy' => 1500, 'stock' => 16, 'yard' => 6],
                ['name' => '1"', 'sku' => 'PLB-GI-100', 'retail' => 2800, 'buy' => 2200, 'stock' => 12],
            ]],
            ['cat' => 'Plumbing', 'sub' => 'Fittings', 'name' => 'PVC elbow Class B', 'sku' => 'PLB-ELB', 'unit' => 'Piece', 'retail' => 55, 'reorder' => 20, 'variants' => [
                ['name' => '½"', 'sku' => 'PLB-ELB-050', 'retail' => 40, 'buy' => 22, 'stock' => 150],
                ['name' => '¾"', 'sku' => 'PLB-ELB-075', 'retail' => 55, 'buy' => 30, 'stock' => 150],
                ['name' => '1"', 'sku' => 'PLB-ELB-100', 'retail' => 90, 'buy' => 55, 'stock' => 80],
                ['name' => '1½"', 'sku' => 'PLB-ELB-150', 'retail' => 160, 'buy' => 100, 'stock' => 40],
            ]],
            ['cat' => 'Plumbing', 'sub' => 'Fittings', 'name' => 'PVC tee Class B', 'sku' => 'PLB-TEE', 'unit' => 'Piece', 'retail' => 70, 'reorder' => 15, 'variants' => [
                ['name' => '½"', 'sku' => 'PLB-TEE-050', 'retail' => 50, 'buy' => 28, 'stock' => 100],
                ['name' => '¾"', 'sku' => 'PLB-TEE-075', 'retail' => 70, 'buy' => 40, 'stock' => 100],
                ['name' => '1"', 'sku' => 'PLB-TEE-100', 'retail' => 120, 'buy' => 75, 'stock' => 60],
            ]],
            ['cat' => 'Plumbing', 'sub' => 'Fittings', 'name' => 'Brass ball valve', 'sku' => 'PLB-VLV', 'unit' => 'Piece', 'retail' => 650, 'reorder' => 6, 'variants' => [
                ['name' => '½"', 'sku' => 'PLB-VLV-050', 'retail' => 450, 'buy' => 320, 'stock' => 24],
                ['name' => '¾"', 'sku' => 'PLB-VLV-075', 'retail' => 650, 'buy' => 470, 'stock' => 18],
                ['name' => '1"', 'sku' => 'PLB-VLV-100', 'retail' => 980, 'buy' => 720, 'stock' => 12],
            ]],
            ['cat' => 'Plumbing', 'sub' => 'Fittings', 'name' => 'Chrome pillar tap', 'sku' => 'PLB-TAP-01', 'unit' => 'Piece', 'retail' => 850, 'buy' => 620, 'stock' => 24, 'reorder' => 6],
            ['cat' => 'Plumbing', 'sub' => 'Fittings', 'name' => 'PTFE thread tape', 'sku' => 'PLB-TAPE', 'unit' => 'Piece', 'bulk' => 'Box', 'per' => 100, 'retail' => 40, 'buy' => 18, 'stock' => 200, 'reorder' => 30],
            ['cat' => 'Plumbing', 'sub' => 'Fittings', 'name' => 'PVC solvent cement 500ml', 'sku' => 'PLB-SOL-500', 'brand' => 'Tangit', 'unit' => 'Tin', 'retail' => 650, 'buy' => 480, 'stock' => 30, 'reorder' => 8, 'track' => true, 'batch' => 'SOL-2026-03', 'expires' => '2028-03-01'],

            ['cat' => 'Electrical', 'sub' => 'Cables', 'name' => 'Twin and earth cable', 'sku' => 'ELC-TNE', 'brand' => 'East African Cables', 'unit' => 'Metre', 'bulk' => 'Roll', 'per' => 100, 'retail' => 75, 'reorder' => 50, 'fractional' => true, 'variants' => [
                ['name' => '1.5mm', 'sku' => 'ELC-TNE-15', 'retail' => 45, 'buy' => 32, 'stock' => 500, 'yard' => 100],
                ['name' => '2.5mm', 'sku' => 'ELC-TNE-25', 'retail' => 75, 'buy' => 55, 'stock' => 400, 'yard' => 100],
                ['name' => '4mm', 'sku' => 'ELC-TNE-40', 'retail' => 130, 'buy' => 98, 'stock' => 200],
                ['name' => '6mm', 'sku' => 'ELC-TNE-60', 'retail' => 190, 'buy' => 145, 'stock' => 120],
            ]],
            ['cat' => 'Electrical', 'sub' => 'Cables', 'name' => 'Flexible cable 3-core 1.5mm', 'sku' => 'ELC-FLX-15', 'unit' => 'Metre', 'bulk' => 'Roll', 'per' => 100, 'retail' => 55, 'buy' => 40, 'stock' => 250, 'reorder' => 40, 'fractional' => true],
            ['cat' => 'Electrical', 'sub' => 'Fittings', 'name' => 'LED bulb', 'sku' => 'ELC-LED', 'brand' => 'Philips', 'unit' => 'Piece', 'bulk' => 'Carton', 'per' => 20, 'retail' => 250, 'reorder' => 12, 'variants' => [
                ['name' => '5W', 'sku' => 'ELC-LED-05', 'retail' => 180, 'buy' => 110, 'stock' => 60],
                ['name' => '9W', 'sku' => 'ELC-LED-09', 'retail' => 250, 'buy' => 160, 'stock' => 80],
                ['name' => '12W', 'sku' => 'ELC-LED-12', 'retail' => 320, 'buy' => 210, 'stock' => 40],
                ['name' => '15W', 'sku' => 'ELC-LED-15', 'retail' => 420, 'buy' => 280, 'stock' => 24],
            ]],
            ['cat' => 'Electrical', 'sub' => 'Fittings', 'name' => 'Double socket', 'sku' => 'ELC-SCK-2', 'unit' => 'Piece', 'retail' => 280, 'buy' => 190, 'stock' => 40, 'reorder' => 10],
            ['cat' => 'Electrical', 'sub' => 'Fittings', 'name' => '1-gang switch', 'sku' => 'ELC-SW-1', 'unit' => 'Piece', 'retail' => 120, 'buy' => 70, 'stock' => 50, 'reorder' => 12],
            ['cat' => 'Electrical', 'sub' => 'Fittings', 'name' => 'Consumer unit 8-way', 'sku' => 'ELC-CU-8', 'unit' => 'Piece', 'retail' => 4500, 'buy' => 3400, 'stock' => 6, 'reorder' => 2],
            ['cat' => 'Electrical', 'sub' => 'Fittings', 'name' => 'PVC conduit 3m', 'sku' => 'ELC-CND', 'unit' => 'Length', 'retail' => 120, 'reorder' => 15, 'variants' => [
                ['name' => '20mm', 'sku' => 'ELC-CND-20', 'retail' => 85, 'buy' => 55, 'stock' => 80],
                ['name' => '25mm', 'sku' => 'ELC-CND-25', 'retail' => 120, 'buy' => 80, 'stock' => 60],
            ]],
            ['cat' => 'Electrical', 'sub' => 'Fittings', 'name' => 'Extension reel 20m', 'sku' => 'ELC-EXT-20', 'unit' => 'Piece', 'retail' => 2200, 'buy' => 1650, 'stock' => 8, 'reorder' => 2],

            ['cat' => 'Paint', 'sub' => 'Emulsion', 'name' => 'Silk vinyl emulsion', 'sku' => 'PNT-SLK', 'brand' => 'Crown', 'unit' => 'Tin', 'retail' => 1850, 'reorder' => 4, 'track' => true, 'batch' => 'SLK-2026-04', 'expires' => '2028-04-01', 'variants' => [
                ['name' => 'White 4L', 'sku' => 'PNT-SLK-W4', 'retail' => 1850, 'buy' => 1400, 'stock' => 24],
                ['name' => 'White 20L', 'sku' => 'PNT-SLK-W20', 'retail' => 7200, 'buy' => 5600, 'stock' => 10],
                ['name' => 'Magnolia 4L', 'sku' => 'PNT-SLK-M4', 'retail' => 1850, 'buy' => 1400, 'stock' => 16],
                ['name' => 'Cream 4L', 'sku' => 'PNT-SLK-C4', 'retail' => 1850, 'buy' => 1400, 'stock' => 12],
            ]],
            ['cat' => 'Paint', 'sub' => 'Emulsion', 'name' => 'Weathershield exterior 4L', 'sku' => 'PNT-WTH-4', 'brand' => 'Dulux', 'unit' => 'Tin', 'retail' => 2400, 'buy' => 1850, 'stock' => 14, 'reorder' => 4, 'track' => true, 'batch' => 'WTH-2026-02', 'expires' => '2028-02-01'],
            ['cat' => 'Paint', 'sub' => 'Solvent', 'name' => 'Gloss enamel 1L', 'sku' => 'PNT-GLS', 'brand' => 'Crown', 'unit' => 'Tin', 'retail' => 650, 'reorder' => 6, 'track' => true, 'batch' => 'GLS-2026-01', 'expires' => '2028-01-15', 'variants' => [
                ['name' => 'White', 'sku' => 'PNT-GLS-W', 'retail' => 650, 'buy' => 470, 'stock' => 20],
                ['name' => 'Black', 'sku' => 'PNT-GLS-K', 'retail' => 650, 'buy' => 470, 'stock' => 12],
                ['name' => 'Red', 'sku' => 'PNT-GLS-R', 'retail' => 680, 'buy' => 490, 'stock' => 10],
            ]],
            ['cat' => 'Paint', 'sub' => 'Solvent', 'name' => 'Turpentine 1L', 'sku' => 'PNT-TRP-1', 'unit' => 'Tin', 'retail' => 280, 'buy' => 190, 'stock' => 36, 'reorder' => 8, 'track' => true, 'batch' => 'TRP-2026-05', 'expires' => '2028-05-01'],
            ['cat' => 'Paint', 'sub' => 'Solvent', 'name' => 'Roller set 9 inch', 'sku' => 'PNT-ROL-9', 'unit' => 'Set', 'retail' => 450, 'buy' => 280, 'stock' => 18, 'reorder' => 4],
            ['cat' => 'Paint', 'sub' => 'Solvent', 'name' => 'Paint brush 4 inch', 'sku' => 'PNT-BR-4', 'unit' => 'Piece', 'retail' => 180, 'buy' => 95, 'stock' => 30, 'reorder' => 8],

            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Claw hammer 500g', 'sku' => 'TLS-HAM-500', 'brand' => 'Stanley', 'unit' => 'Piece', 'retail' => 850, 'buy' => 620, 'stock' => 15, 'reorder' => 4],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Spirit level 600mm', 'sku' => 'TLS-LVL-600', 'unit' => 'Piece', 'retail' => 1200, 'buy' => 860, 'stock' => 8, 'reorder' => 2],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Tape measure', 'sku' => 'TLS-TAP', 'brand' => 'Stanley', 'unit' => 'Piece', 'retail' => 450, 'reorder' => 4, 'variants' => [
                ['name' => '5m', 'sku' => 'TLS-TAP-5', 'retail' => 450, 'buy' => 300, 'stock' => 20],
                ['name' => '8m', 'sku' => 'TLS-TAP-8', 'retail' => 750, 'buy' => 520, 'stock' => 12],
            ]],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Wheelbarrow heavy duty', 'sku' => 'TLS-WHB', 'unit' => 'Piece', 'retail' => 6500, 'buy' => 4900, 'stock' => 4, 'yard' => 2, 'reorder' => 2],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Hacksaw frame', 'sku' => 'TLS-SAW-H', 'unit' => 'Piece', 'retail' => 550, 'buy' => 360, 'stock' => 12, 'reorder' => 3],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Digging spade', 'sku' => 'TLS-SPD', 'unit' => 'Piece', 'retail' => 980, 'buy' => 700, 'stock' => 10, 'reorder' => 2],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Jembe hoe', 'sku' => 'TLS-HOE', 'unit' => 'Piece', 'retail' => 750, 'buy' => 520, 'stock' => 14, 'reorder' => 3],
            ['cat' => 'Tools', 'sub' => 'Hand tools', 'name' => 'Brass padlock 50mm', 'sku' => 'TLS-LOCK-50', 'unit' => 'Piece', 'retail' => 650, 'buy' => 420, 'stock' => 20, 'reorder' => 5],
            ['cat' => 'Tools', 'sub' => 'Power tools', 'name' => 'Angle grinder 4½"', 'sku' => 'TLS-GRD-45', 'brand' => 'Bosch', 'unit' => 'Piece', 'retail' => 8500, 'buy' => 6400, 'stock' => 4, 'reorder' => 1, 'track' => true, 'serials' => ['GRD-24001', 'GRD-24002', 'GRD-24003', 'GRD-24004']],
            ['cat' => 'Tools', 'sub' => 'Power tools', 'name' => 'Hammer drill 13mm', 'sku' => 'TLS-DRL-13', 'brand' => 'Bosch', 'unit' => 'Piece', 'retail' => 7200, 'buy' => 5400, 'stock' => 3, 'reorder' => 1, 'track' => true, 'serials' => ['DRL-24011', 'DRL-24012', 'DRL-24013']],
            ['cat' => 'Tools', 'sub' => 'Power tools', 'name' => 'Circular saw 7"', 'sku' => 'TLS-SAW-7', 'brand' => 'Makita', 'unit' => 'Piece', 'retail' => 12500, 'buy' => 9600, 'stock' => 2, 'reorder' => 1, 'track' => true, 'serials' => ['SAW-24021', 'SAW-24022']],

            ['cat' => 'Fasteners', 'sub' => 'Nails', 'name' => 'Wire nails', 'sku' => 'FST-NIL', 'unit' => 'Kilogram', 'bulk' => 'Sack', 'per' => 25, 'retail' => 180, 'reorder' => 15, 'fractional' => true, 'variants' => [
                ['name' => '2"', 'sku' => 'FST-NIL-2', 'retail' => 180, 'buy' => 130, 'stock' => 80, 'yard' => 25],
                ['name' => '3"', 'sku' => 'FST-NIL-3', 'retail' => 170, 'buy' => 120, 'stock' => 90, 'yard' => 25],
                ['name' => '4"', 'sku' => 'FST-NIL-4', 'retail' => 160, 'buy' => 115, 'stock' => 70, 'yard' => 20],
            ]],
            ['cat' => 'Fasteners', 'sub' => 'Nails', 'name' => 'Roofing nails', 'sku' => 'FST-NIL-RF', 'unit' => 'Kilogram', 'bulk' => 'Sack', 'per' => 25, 'retail' => 220, 'buy' => 160, 'stock' => 60, 'yard' => 20, 'reorder' => 10, 'fractional' => true],
            ['cat' => 'Fasteners', 'sub' => 'Screws', 'name' => 'Chipboard screws box of 100', 'sku' => 'FST-SCR-CB', 'unit' => 'Box', 'retail' => 320, 'reorder' => 6, 'variants' => [
                ['name' => '1"', 'sku' => 'FST-SCR-1', 'retail' => 280, 'buy' => 190, 'stock' => 30],
                ['name' => '1½"', 'sku' => 'FST-SCR-15', 'retail' => 320, 'buy' => 220, 'stock' => 30],
                ['name' => '2"', 'sku' => 'FST-SCR-2', 'retail' => 380, 'buy' => 260, 'stock' => 24],
            ]],
            ['cat' => 'Fasteners', 'sub' => 'Screws', 'name' => 'Rawl bolt', 'sku' => 'FST-RAWL', 'unit' => 'Piece', 'bulk' => 'Box', 'per' => 50, 'retail' => 65, 'reorder' => 20, 'variants' => [
                ['name' => 'M8', 'sku' => 'FST-RAWL-M8', 'retail' => 45, 'buy' => 25, 'stock' => 80],
                ['name' => 'M10', 'sku' => 'FST-RAWL-M10', 'retail' => 65, 'buy' => 38, 'stock' => 60],
                ['name' => 'M12', 'sku' => 'FST-RAWL-M12', 'retail' => 95, 'buy' => 58, 'stock' => 40],
            ]],
            ['cat' => 'Fasteners', 'sub' => 'Screws', 'name' => 'Butt hinge 4 inch pair', 'sku' => 'FST-HNG-4', 'unit' => 'Pair', 'retail' => 180, 'buy' => 110, 'stock' => 40, 'reorder' => 8],

            ['cat' => 'Roofing', 'sub' => 'Sheets', 'name' => 'Box profile mabati', 'sku' => 'RFG-BOX', 'brand' => 'Mabati', 'unit' => 'Metre', 'bulk' => 'Sheet', 'per' => 3, 'retail' => 680, 'reorder' => 20, 'fractional' => true, 'variants' => [
                ['name' => 'Gauge 30', 'sku' => 'RFG-BOX-30', 'retail' => 520, 'buy' => 400, 'stock' => 180, 'yard' => 60],
                ['name' => 'Gauge 28', 'sku' => 'RFG-BOX-28', 'retail' => 680, 'buy' => 520, 'stock' => 120, 'yard' => 40],
                ['name' => 'Gauge 26', 'sku' => 'RFG-BOX-26', 'retail' => 890, 'buy' => 690, 'stock' => 80, 'yard' => 24],
            ]],
            ['cat' => 'Roofing', 'sub' => 'Sheets', 'name' => 'IT4 iron sheet 3m', 'sku' => 'RFG-IT4', 'unit' => 'Sheet', 'retail' => 1450, 'reorder' => 8, 'variants' => [
                ['name' => 'Gauge 30', 'sku' => 'RFG-IT4-30', 'retail' => 1450, 'buy' => 1120, 'stock' => 40, 'yard' => 16],
                ['name' => 'Gauge 28', 'sku' => 'RFG-IT4-28', 'retail' => 1850, 'buy' => 1420, 'stock' => 24, 'yard' => 8],
            ]],
            ['cat' => 'Roofing', 'sub' => 'Accessories', 'name' => 'Ridge cap gauge 30', 'sku' => 'RFG-RDG-30', 'unit' => 'Piece', 'retail' => 450, 'buy' => 320, 'stock' => 40, 'yard' => 12, 'reorder' => 8],
            ['cat' => 'Roofing', 'sub' => 'Accessories', 'name' => 'Box gutter 6m', 'sku' => 'RFG-GUT-6', 'unit' => 'Length', 'retail' => 1800, 'buy' => 1350, 'stock' => 12, 'yard' => 4, 'reorder' => 3],
            ['cat' => 'Roofing', 'sub' => 'Accessories', 'name' => 'Roofing screws box', 'sku' => 'RFG-SCR', 'unit' => 'Box', 'retail' => 650, 'buy' => 460, 'stock' => 35, 'reorder' => 8],
        ];
    }

    /**
     * Extra Nairobi hardware-shop prices, in KES, about 2026 retail.
     *
     * @return list<array<string, mixed>>
     */
    private function moreItems(): array
    {
        return require __DIR__.'/hardware_items.php';
    }
}
