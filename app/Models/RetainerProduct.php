<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetainerProduct extends Model
{
    protected $fillable = [
        'product_id',
        'retainer_id',
        'quantity',
        'tax',
        'discount',
        'total',
    ];

    public function product()
    {
        return $this->hasOne('App\Models\ProductService', 'id', 'product_id');
    }
    
}
