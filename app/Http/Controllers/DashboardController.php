<?php

namespace App\Http\Controllers;

use App\Models\BalanceSheet;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\Goal;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProductServiceCategory;
use App\Models\ProductServiceUnit;
use App\Models\Projects;
use App\Models\Revenue;
use App\Models\Tax;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if(Auth::check())
        {
            if(\Auth::user()->can('show dashboard'))
            {
                $data['latestIncome']  = Revenue::where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->get();
                $data['latestExpense'] = Payment::where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->get();


                $incomeCategory = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'income')->get();
                $inColor        = array();
                $inCategory     = array();
                $inAmount       = array();
                for($i = 0; $i < count($incomeCategory); $i++)
                {
                    $inColor[]    = $incomeCategory[$i]->color;
                    $inCategory[] = $incomeCategory[$i]->name;
                    $inAmount[]   = $incomeCategory[$i]->incomeCategoryRevenueAmount();
                }


                $data['incomeCategoryColor'] = $inColor;
                $data['incomeCategory']      = $inCategory;
                $data['incomeCatAmount']     = $inAmount;


                $expenseCategory = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'expense')->get();
                $exColor         = array();
                $exCategory      = array();
                $exAmount        = array();
                for($i = 0; $i < count($expenseCategory); $i++)
                {
                    $exColor[]    = $expenseCategory[$i]->color;
                    $exCategory[] = $expenseCategory[$i]->name;
                    $exAmount[]   = $expenseCategory[$i]->expenseCategoryAmount();
                }

                $data['expenseCategoryColor'] = $exColor;
                $data['expenseCategory']      = $exCategory;
                $data['expenseCatAmount']     = $exAmount;

                $data['incExpBarChartData']  = \Auth::user()->getincExpBarChartData();
                $data['incExpLineChartData'] = \Auth::user()->getIncExpLineChartDate();

                $data['currentYear']  = date('Y');
                $data['currentMonth'] = date('M');

                $constant['taxes']         = Tax::where('created_by', \Auth::user()->creatorId())->count();
                $constant['category']      = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->count();
                $constant['units']         = ProductServiceUnit::where('created_by', \Auth::user()->creatorId())->count();
                $constant['bankAccount']   = BankAccount::where('created_by', \Auth::user()->creatorId())->count();
                $data['constant']          = $constant;

                $data['bankAccountDetail'] = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get();
                $data['recentInvoice']     = Invoice::where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->get();
                $data['weeklyInvoice']     = \Auth::user()->weeklyInvoice();
                $data['monthlyInvoice']    = \Auth::user()->monthlyInvoice();
                $data['recentBill']        = Bill::where('created_by', '=', \Auth::user()->creatorId())->orderBy('id', 'desc')->limit(5)->get();
                $data['weeklyBill']        = \Auth::user()->weeklyBill();
                $data['monthlyBill']       = \Auth::user()->monthlyBill();
                $data['goals']             = Goal::where('created_by', '=', \Auth::user()->creatorId())->where('is_display', 1)->get();

            }
            else
            {
                $data = [];
            }

            return view('dashboard.index', $data);
        }
        else
        {
            if(!file_exists(storage_path() . "/installed"))
            {
                header('location:install');
                die;
            }
            else
            {
                $settings = Utility::settings();
                if ($settings['display_landing_page'] == 'on' && \Schema::hasTable('landing_page_settings')) {
                    return view('landingpage::layouts.landingpage');
                } else {
                    return redirect('login');
                }

            }
        }
    }

}

