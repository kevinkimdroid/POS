<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuantityPrice extends Model
{
    protected $fillable = ['product_id', 'product_variant_id', 'price_level', 'min_quantity', 'price'];
}
