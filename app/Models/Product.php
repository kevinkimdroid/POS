<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'subcategory_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'brand',
        'base_unit',
        'bulk_unit',
        'units_per_bulk',
        'buying_price',
        'retail_price',
        'wholesale_price',
        'contractor_price',
        'tax_type',
        'reorder_level',
        'track_serial_batch',
        'allow_fractional',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            'units_per_bulk' => 'decimal:3',
            'buying_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'contractor_price' => 'decimal:2',
            'reorder_level' => 'decimal:3',
            'track_serial_batch' => 'boolean',
            'allow_fractional' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function quantityPrices(): HasMany
    {
        return $this->hasMany(QuantityPrice::class);
    }

    public function priceFor(string $level): float
    {
        return (float) match ($level) {
            'wholesale' => $this->wholesale_price,
            'contractor' => $this->contractor_price,
            default => $this->retail_price,
        };
    }

    public static function taxLabel(string $taxType): string
    {
        return match ($taxType) {
            'zero' => 'Zero-rated',
            'exempt' => 'Exempt',
            default => 'VAT 16%',
        };
    }
}
