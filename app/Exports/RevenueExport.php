<?php

namespace App\Exports;

use App\Models\Revenue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RevenueExport implements FromCollection, WithHeadings
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
        $data = Revenue::where('created_by' , \Auth::user()->id);

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
            foreach ($data as $k => $Revenue) {
            // dd($Revenue);

            $account   = Revenue::accounts($Revenue->account_id);
            $customer  = Revenue::customers($Revenue->customer_id);
            $category  = Revenue::categories($Revenue->category_id);
            // dd($category);

            unset($Revenue->created_by, $Revenue->updated_at, $Revenue->created_at, $Revenue->payment_method, $Revenue->add_receipt); 
            $data[$k]["account_id"]   = $account;
            $data[$k]["customer_id"]  = $customer;  
            $data[$k]["category_id"]  = $category;  

            }

        }

        return $data;
    }

    public function headings(): array
    {
        return [
            "Revenue Id",
            "Date",
            "Amount",
            "Account",
            "Customer",
            "Category",
            "Reference",    
            "Description",
        ];
    }
}
