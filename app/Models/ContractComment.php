<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractComment extends Model
{
    protected $fillable = [
        'contract_id',
        'comment',
        'created_by',
        'type',
    ];
    
    public function client()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'created_by');
    }
}
