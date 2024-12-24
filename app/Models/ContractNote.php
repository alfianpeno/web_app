<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractNote extends Model
{
    protected $fillable = [
        'contract_id',
        'note',
        'created_by',
        'type',
    ];

}
