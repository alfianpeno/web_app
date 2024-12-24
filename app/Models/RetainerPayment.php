<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetainerPayment extends Model
{
    protected $fillable = [
        'retainer_id',
        'date',
        'amount',
        'account_id',
        'payment_method',
        'order_id',
        'currency',
        'txn_id',
        'payment_type',
        'receipt',
        'add_receipt',
        'reference',
        'description',
        
    ];
    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }
}
