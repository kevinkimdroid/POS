<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    protected $fillable = ['customer_id', 'user_id', 'amount', 'method', 'reference'];
}
