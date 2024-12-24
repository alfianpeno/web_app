<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionLines extends Model
{
    protected $fillable = [
        'id',
        'account_id',
        'reference',
        'reference_id',
        'reference_sub_id',
        'date',
        'credit',
        'debit',
        'created_by',
    ];
}
