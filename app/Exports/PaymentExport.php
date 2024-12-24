<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentExport implements FromCollection, WithHeadings
{

    protected $date;

    function __construct($date) {
            $this->date = $date;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data = [];
        $data = Payment::where('created_by' , \Auth::user()->id);

        if($this->date!=null && $this->date!=0)
        {
            if (str_contains($this->date, ' to ')) { 
                $date_range = explode(' to ', $this->date);
                $data->whereBetween('date', $date_range);
            }elseif(!empty($this->date)){
            
                $data->where('date', $this->date);
            }

        }
        
        $data = $data->get();


        if (!empty($data)) {
            foreach ($data as $k => $Payment) {
            // dd($Payment);
            $account   = Payment::accounts($Payment->account_id);
            $vendor    = Payment::vendors($Payment->vender_id);
            $category  = Payment::categories($Payment->category_id);

            unset($Payment->created_by, $Payment->updated_at, $Payment->created_at, $Payment->payment_method, $Payment->add_receipt); 
            $data[$k]["account_id"]  = $account;
            $data[$k]["vender_id"]   = $vendor;  
            $data[$k]["category_id"] = $category;

            }
        }


        return $data;
    }

    public function headings(): array
    {
        return [
            "Payment Id",
            "Date",
            "Amount",
            "Account",
            "Vendor",
            "Description",
            "Category",
            "Reference",
        ];
    }
}
