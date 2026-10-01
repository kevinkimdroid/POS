<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'buying_price',
        'retail_price',
        'wholesale_price',
        'contractor_price',
    ];

    protected function casts(): array
    {
        return [
            'buying_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'contractor_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function priceFor(string $level): float
    {
        return (float) match ($level) {
            'wholesale' => $this->wholesale_price,
            'contractor' => $this->contractor_price,
            default => $this->retail_price,
        };
    }
}
