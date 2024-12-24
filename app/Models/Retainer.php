<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retainer extends Model
{
    protected $fillable = [
        'retainer_id',
        'customer_id',
        'issue_date',
        'status',
        'category_id',
        'is_convert',
        'converted_invoice_id',
        'created_by',
    ];

    

    public static $statues = [
        'Draft',
        'Sent',
        'Unpaid',
        'Partialy Paid',
        'Paid',
    ];


    public function customer()
    {
        return $this->hasOne('App\Models\Customer', 'id', 'customer_id');
    }

    public function category()
    {
        return $this->hasOne('App\Models\ProductServiceCategory', 'id', 'category_id');
    }

    public function taxes()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax');
    }

    public function tax()
    {
        return $this->hasOne('App\Models\Tax', 'id', 'tax_id');
    }

    public function payments()
    {
        return $this->hasMany('App\Models\RetainerPayment', 'retainer_id', 'id');
    }

    public function bankpayment()
    {
        return $this->hasMany('App\Models\BankTransfer', 'retainer_id', 'id')->where('type','=','retainer')->where('status','!=','Approved');
    }

    public function getDue()
    {
        $due = 0;
        foreach ($this->payments as $payment) {
            $due += $payment->amount;
        }

        return ($this->getTotal() - $due);
    }

    public function getSubTotal()
    {
        $subTotal = 0;
        foreach ($this->items as $product) {
            $subTotal += ($product->price * $product->quantity);
        }

        return $subTotal;
    }

    public function getTotalTax()
    {
        $totalTax = 0;
        foreach($this->items as $product)
        {
            $taxes = Utility::totalTaxRate($product->tax);


            $totalTax += ($taxes / 100) * ($product->price * $product->quantity - $product->discount) ;
        }

        return $totalTax;
    }


    public function getTotalDiscount()
    {
        $totalDiscount = 0;
        foreach ($this->items as $product) {
            $totalDiscount += $product->discount;
        }

        return $totalDiscount;
    }

    public function getTotal()
    {

        return ($this->getSubTotal() -$this->getTotalDiscount()) + $this->getTotalTax();
        //        return ($this->getSubTotal() + $this->getTotalTax()) - $this->getTotalDiscount();
    }

    public static function customers($customer)
    {

        $categoryArr  = explode(',', $customer);
        $unitRate = 0;
        foreach ($categoryArr as $customer) {
            if ($customer == 0) {
                $unitRate = '';
            } else {
                $customer        = Customer::find($customer);
                $unitRate        = $customer->name;
            }
        }

        return $unitRate;
    }

    public static function RetainerCategory($category)
    {
        $categoryArr  = explode(',', $category);
        $categoryRate = 0;
        foreach ($categoryArr as $category) {
            $category    = ProductServiceCategory::find($category);
            $categoryRate        = $category->name;
        }

        return $categoryRate;
    }

    public function items()
    {
        return $this->hasMany('App\Models\RetainerProduct', 'retainer_id', 'id');
    }
    public static function change_status($retainer_id, $status)
    {

        $retainer         = Retainer::find($retainer_id);
        $retainer->status = $status;
        $retainer->update();
    }

    public function RetainerPayment()
    {
        return $this->belongsTo('App\Models\RetainerPayment', 'id', 'retainer_id');
    }

}
