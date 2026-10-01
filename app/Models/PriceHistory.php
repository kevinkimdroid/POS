<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceHistory extends Model
{
    protected $fillable = ['product_id', 'buying_price', 'landed_cost', 'source'];
}
