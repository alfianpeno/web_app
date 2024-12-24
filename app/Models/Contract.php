<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'customer',
        'subject',
        'value',
        'type',
        'start_date',
        'end_date',
        'edit_status',
        'description',
        'customer_signature',
        'company_signature',
        'created_by',
    ];

    public function clients()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer');
    }
    public function types()
    {
        return $this->hasOne('App\Models\ContractType', 'id', 'type');
    }
    public static function editstatus()
    {

        $editstatus = [
            'accept' => 'Accept',
            'decline' => 'Decline',
           
        ];
        return $editstatus;
    }
    public static function getContractSummary($contracts)
    {
        $total = 0;

        foreach($contracts as $contract)
        {
            $total += $contract->value;
        }

        return \Auth::user()->priceFormat($total);
    }
    public function files()
    {
        return $this->hasMany('App\Models\ContractAttachment', 'contract_id' , 'id');
    }

    public function comment()
    {
        return $this->hasMany('App\Models\ContractComment', 'contract_id', 'id');
    }

    public function note()
    {
        return $this->hasMany('App\Models\ContractNote', 'contract_id', 'id');
    }

    public function ContractAttachment()
    {
        return $this->belongsTo('App\Models\ContractAttachment', 'id', 'contract_id');
    }

    public function ContractComment()
    {
        return $this->belongsTo('App\Models\ContractComment', 'id', 'contract_id');
    }

    public function ContractNote()
    {
        return $this->belongsTo('App\Models\ContractNote', 'id', 'contract_id');
    }
}
