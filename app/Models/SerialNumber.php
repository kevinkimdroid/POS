<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialNumber extends Model
{
    protected $fillable = ['product_id', 'product_variant_id', 'serial', 'status', 'sale_line_id'];
}
