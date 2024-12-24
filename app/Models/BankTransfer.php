<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    protected $fillable = [
        'invoice_id',
        'retainer_id',
        'order_id',
        'amount',
        'status',
        'receipt',
        'type',
        'created_by',
    ];
}
