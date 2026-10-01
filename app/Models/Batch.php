<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = ['product_id', 'product_variant_id', 'branch_id', 'code', 'expires_on', 'quantity'];

    protected function casts(): array
    {
        return ['expires_on' => 'date', 'quantity' => 'decimal:3'];
    }
}
