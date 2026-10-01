<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturnLine extends Model
{
    protected $fillable = ['sale_return_id', 'sale_line_id', 'quantity', 'amount'];

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class);
    }
}
