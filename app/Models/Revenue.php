<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    protected $fillable = [
        'date',
        'amount',
        'account_id',
        'customer_id',
        'category_id',
        'recurring',
        'payment_method',
        'reference',
        'description',
        'created_by',
    ];

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }

    public function bankAccount()
    {
        return $this->hasOne('App\Models\BankAccount', 'id', 'account_id');
    }

    public static function accounts($account)
    {
        $categoryArr  = explode(',', $account);
        $unitRate = 0;
        foreach ($categoryArr as $account) {
            if ($account == 0) {
                $unitRate = '';
            } else {
                $account        = BankAccount::find($account);
                // $unitRate       = ($account->bank_name ?? '');
                $unitRate    = ($account->bank_name.'  '.$account->holder_name);

            }
        }

        return $unitRate;
    }

    public static function customers($customer)
    {
        $categoryArr  = explode(',', $customer);
        $unitRate = 0;
        foreach ($categoryArr as $customer) {
            if ($customer == 0) {
                $unitRate = '';
            } else {
                $customer       = Customer::find($customer);
                $unitRate       = ($customer->name);
            }
        }

        return $unitRate;
    }

    public static function categories($category)
    {
        $categoryArr  = explode(',', $category);
        $unitRate = 0;
        foreach ($categoryArr as $category) {
            if ($category == 0) {
                $unitRate = '';
            } else {
                $category        = ProductServiceCategory::find($category);
                $unitRate       = ($category->name);
            }
        }

        return $unitRate;
    }
}
