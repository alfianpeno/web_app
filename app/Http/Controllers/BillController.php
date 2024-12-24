<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\BillProduct;
use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Mail\BillPaymentCreate;
use App\Models\Mail\BillSend;
use App\Models\Mail\VenderBillSend;
use App\Models\ProductService;
use App\Models\ProductServiceCategory;
use App\Models\StockReport;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Utility;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BillExport;
use App\Models\BillAccount;
use App\Models\ChartOfAccount;
use App\Models\DebitNote;
use App\Models\TransactionLines;

class BillController extends Controller
{

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage bill')) {

            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vendor', '');

            $status = Bill::$statues;

            $query = Bill::where('created_by', '=', \Auth::user()->creatorId());
            if (!empty($request->vender)) {
                $query->where('vender_id', '=', $request->vender);
            }


            if (str_contains($request->bill_date, ' to ')) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif (!empty($request->bill_date)) {

                $query->where('bill_date', $request->bill_date);
            }

            // if (!empty($request->bill_date)) {
            //     $date_range = explode(' to ', $request->bill_date);
            //     $query->whereBetween('bill_date', $date_range);
            // }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $bills = $query->get();

            return view('bill.index', compact('bills', 'vender', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }


    public function create($vendorId)
    {

        if (\Auth::user()->can('create bill')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
            $category     = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $bill_number = \Auth::user()->billNumberFormat($this->billNumber());
            $venders     = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('Select Vendor', '');

            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services->prepend('Select Item', '');

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();

            return view('bill.create', compact('venders', 'bill_number', 'product_services', 'category', 'customFields', 'vendorId', 'chartAccounts', 'subAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'vender_id' => 'required',
                    'bill_date' => 'required',
                    'due_date' => 'required',
                    'category_id' => 'required',
                    'items' => 'required',

                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $bill            = new Bill();
            $bill->bill_id   = $this->billNumber();
            $bill->vender_id = $request->vender_id;
            $bill->bill_date      = $request->bill_date;
            $bill->status         = 0;
            $bill->due_date       = $request->due_date;
            $bill->category_id    = $request->category_id;
            $bill->order_number   = !empty($request->order_number) ? $request->order_number : 0;
            $bill->discount_apply = isset($request->discount_apply) ? 1 : 0;
            $bill->created_by     = \Auth::user()->creatorId();

            $bill->save();
            Utility::starting_number($bill->bill_id + 1, 'bill');
            CustomField::saveData($bill, $request->customField);
            $products = $request->items;

            $total_amount = 0;

            for ($i = 0; $i < count($products); $i++) {
                $billProduct              = new BillProduct();
                $billProduct->bill_id     = $bill->id;
                $billProduct->product_id  = $products[$i]['item'];
                $billProduct->quantity    = $products[$i]['quantity'];
                $billProduct->tax         = $products[$i]['tax'];
                // $billProduct->discount = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                // $billProduct->discount = (isset($request->discount_apply)) ? isset($products[$i]['discount']) ? $products[$i]['discount'] : 0: 0;
                $billProduct->discount    = $products[$i]['discount'];
                $billProduct->price       = $products[$i]['price'];
                $billProduct->description = $products[$i]['description'];
                $billProduct->save();

                $billTotal = 0;
                if (!empty($products[$i]['chart_account_id'])) {
                    $billAccount                    = new BillAccount();
                    $billAccount->chart_account_id  = $products[$i]['chart_account_id'];
                    $billAccount->price             = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
                    $billAccount->description       = $products[$i]['description'];
                    $billAccount->type              = 'Bill';
                    $billAccount->ref_id            = $bill->id;
                    $billAccount->save();
                    $billTotal = $billAccount->price;
                }

                Utility::total_quantity('plus', $billProduct->quantity, $billProduct->product_id);

                //Product Stock Report
                if (!empty($products[$i]['item'])) {
                    $type = 'bill';
                    $type_id = $bill->id;
                    $description = $products[$i]['quantity'] . '  ' . __('quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);
                    Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
                    $total_amount += ($billProduct->quantity * $billProduct->price) + $billTotal;
                }
            }

            if (!empty($request->chart_account_id)) {

                $billaccount = ProductServiceCategory::find($request->category_id);
                $chart_account = ChartOfAccount::find($billaccount->chart_account_id);
                $billAccount                    = new BillAccount();
                $billAccount->chart_account_id  = $chart_account['id'];
                $billAccount->price             = $total_amount;
                $billAccount->description       = $request->description;
                $billAccount->type              = 'Bill Category';
                $billAccount->ref_id            = $bill->id;
                $billAccount->save();
            }


            //Twilio Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $billId    = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);
            $vendor = Vender::find($request->vender_id);
            if (isset($setting['bill_notification']) && $setting['bill_notification'] == 1) {
                $uArr = [
                    'bill_name' => $vendor->name,
                    'bill_number'  => \Auth::user()->billNumberFormat($bill->bill_id),
                    'bill_url'  =>  $bill->url,
                ];
                Utility::send_twilio_msg($vendor->contact, 'new_bill', $uArr);
            }

            // webhook
            $module = 'New Bill';
            $webhook =  Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($bill);
                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created.'));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('bill.index', $bill->id)->with('success', __('Bill successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function venderNumber()
    {
        $latest = Vender::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->customer_id + 1;
    }

    public function show($ids)
    {

        if (\Auth::user()->can('show bill')) {
            try {
                $id       = Crypt::decrypt($ids);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __('Bill Not Found.'));
            }

            $id   = Crypt::decrypt($ids);
            $bill = Bill::with('debitNote', 'payments.bankAccount', 'items.product.unit')->find($id);

            if (!empty($bill) && $bill->created_by == \Auth::user()->creatorId()) {
                $billPayment = BillPayment::where('bill_id', $bill->id)->first();
                $vendor      = $bill->vender;

                $item      = $bill->items;
                $accounts  = $bill->accounts;
                $items     = [];
                if (!empty($item) && count($item) > 0) {
                    foreach ($item as $k => $val) {
                        if (!empty($accounts[$k])) {
                            $val['chart_account_id'] = $accounts[$k]['chart_account_id'];
                            $val['account_id'] = $accounts[$k]['id'];
                            $val['amount'] = $accounts[$k]['price'];
                        }
                        $items[] = $val;
                    }
                } else {

                    foreach ($accounts as $k => $val) {
                        $val1['chart_account_id'] = $accounts[$k]['chart_account_id'];
                        $val1['account_id'] = $accounts[$k]['id'];
                        $val1['amount'] = $accounts[$k]['price'];
                        $items[] = $val1;
                    }
                }

                $bill->customField = CustomField::getData($bill, 'bill');
                $customFields      = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();

                return view('bill.view', compact('bill', 'vendor', 'items', 'billPayment', 'customFields'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit($ids)
    {
        if (\Auth::user()->can('edit bill')) {
            $id       = Crypt::decrypt($ids);
            $bill     = Bill::find($id);
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');

            $bill_number      = \Auth::user()->billNumberFormat($bill->bill_id);
            $venders          = Vender::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $bill->customField = CustomField::getData($bill, 'bill');
            $customFields      = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();

            $chartAccounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))
                ->where('created_by', \Auth::user()->creatorId())->get()
                ->pluck('code_name', 'id');
            $chartAccounts->prepend('Select Account', '');

            $subAccounts = ChartOfAccount::select('chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account');
            $subAccounts->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', 'chart_of_account_parents.id');
            $subAccounts->where('chart_of_accounts.parent', '!=', 0);
            $subAccounts->where('chart_of_accounts.created_by', \Auth::user()->creatorId());
            $subAccounts = $subAccounts->get()->toArray();

            //for item and account show in repeater
            $item      = $bill->items;
            $accounts  = $bill->accounts;
            $items     = [];
            if (!empty($item) && count($item) > 0) {
                foreach ($item as $k => $val) {
                    if (!empty($accounts[$k])) {
                        $val['chart_account_id'] = $accounts[$k]['chart_account_id'];
                        $val['account_id'] = $accounts[$k]['id'];
                        $val['amount'] = $accounts[$k]['price'];
                    }
                    $items[] = $val;
                }
            } else {
                foreach ($accounts as $k => $val) {
                    $val1['chart_account_id'] = $accounts[$k]['chart_account_id'];
                    $val1['account_id'] = $accounts[$k]['id'];
                    $val1['amount'] = $accounts[$k]['price'];
                    $items[] = $val1;
                }
            }


            return view('bill.edit', compact('venders', 'product_services', 'bill', 'bill_number', 'category', 'customFields', 'chartAccounts', 'items', 'subAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function update(Request $request, Bill $bill)
    {

        if (\Auth::user()->can('edit bill')) {

            if ($bill->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'vender_id' => 'required',
                        'bill_date' => 'required',
                        'due_date' => 'required',
                        'items' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('bill.index')->with('error', $messages->first());
                }
                $bill->vender_id      = $request->vender_id;
                $bill->bill_date      = $request->bill_date;
                $bill->due_date       = $request->due_date;
                $bill->order_number   = $request->order_number;
                //                $bill->discount_apply = isset($request->discount_apply) ? 1 : 0;
                $bill->category_id    = $request->category_id;
                $bill->save();
                CustomField::saveData($bill, $request->customField);
                $products = $request->items;

                $total_amount = 0;

                for ($i = 0; $i < count($products); $i++) {
                    $billProduct = BillProduct::find($products[$i]['id']);

                    if ($billProduct == null) {
                        $billProduct             = new BillProduct();
                        $billProduct->bill_id    = $bill->id;

                        Utility::total_quantity('plus', $products[$i]['quantity'], $products[$i]['items']);

                        $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + ($products[$i]['itemTaxPrice']) - ($products[$i]['discount']);

                        Utility::updateUserBalance('vendor', $request->vender_id, $updatePrice, 'debit');
                    } else {

                        Utility::total_quantity('minus', $billProduct->quantity, $billProduct->product_id);
                    }

                    if (isset($products[$i]['item'])) {
                        $billProduct->product_id = $products[$i]['item'];
                    }

                    $billProduct->quantity    = $products[$i]['quantity'];
                    $billProduct->tax         = $products[$i]['tax'];
                    //                    $billProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                    $billProduct->price       = $products[$i]['price'];
                    $billProduct->description = $products[$i]['description'];
                    $billProduct->save();


                    $billTotal = 0;
                    if (!empty($products[$i]['chart_account_id'])) {
                        $billAccount = BillAccount::find($products[$i]['account_id']);

                        if ($billAccount == null) {
                            $billAccount                    = new BillAccount();
                            $billAccount->chart_account_id = $products[$i]['chart_account_id'];
                        } else {
                            $billAccount->chart_account_id = $products[$i]['chart_account_id'];
                        }
                        $billAccount->price             = $products[$i]['amount'] ? $products[$i]['amount'] : 0;
                        $billAccount->description       = $products[$i]['description'];
                        $billAccount->type              = 'Bill';
                        $billAccount->ref_id            = $bill->id;
                        $billAccount->save();
                        $billTotal = $billAccount->price;
                    }

                    if ($products[$i]['id'] > 0) {
                        Utility::total_quantity('plus', $products[$i]['quantity'], $billProduct->product_id);
                    }

                    //Product Stock Report
                    $type = 'bill';
                    $type_id = $bill->id;
                    StockReport::where('type', '=', 'bill')->where('type_id', '=', $bill->id)->delete();
                    $description = $products[$i]['quantity'] . '  ' . __(' quantity purchase in bill') . ' ' . \Auth::user()->billNumberFormat($bill->bill_id);

                    if (isset($products[$i]['item'])) {
                        Utility::addProductStock($products[$i]['item'], $products[$i]['quantity'], $type, $description, $type_id);
                    }

                    $total_amount += ($billProduct->quantity * $billProduct->price) + $billTotal;
                }

                if (!empty($request->chart_account_id)) {
                    $billaccount = ProductServiceCategory::find($request->category_id);
                    $chart_account = ChartOfAccount::find($billaccount->chart_account_id);
                    $billAccount                    = new BillAccount();
                    $billAccount->chart_account_id  = $chart_account['id'];
                    $billAccount->price             = $total_amount;
                    $billAccount->description       = $request->description;
                    $billAccount->type              = 'Bill Category';
                    $billAccount->ref_id            = $bill->id;
                    $billAccount->save();
                }

                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();

                $bill_products = BillProduct::where('bill_id', $bill->id)->get();
                foreach ($bill_products as $bill_product) {
                    $product = ProductService::find($bill_product->product_id);
                    if ($product && !is_null($product->expense_chartaccount_id)) {
                        $totalTaxPrice = 0;
                        if ($bill_product->tax != null) {
                            $taxes = \App\Models\Utility::tax($bill_product->tax);
                            foreach ($taxes as $tax) {
                                $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
                                $totalTaxPrice += $taxPrice;
                            }
                        }
                        $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

                        $data = [
                            'account_id' => $product->expense_chartaccount_id,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $itemAmount,
                            'reference' => 'Bill',
                            'reference_id' => $bill->id,
                            'reference_sub_id' => $product->id,
                            'date' => $bill->bill_date,
                        ];
                        Utility::addTransactionLines($data);
                    }
                }

                foreach ($bill_products as $bill_product) {
                    $product = ProductService::find($bill_product->product_id);

                    // Check if $product is not null and expense_chartaccount_id is not null before accessing its properties
                    if ($product && !is_null($product->expense_chartaccount_id)) {
                        $totalTaxPrice = 0;

                        if ($bill_product->tax != null) {
                            $taxes = \App\Models\Utility::tax($bill_product->tax);

                            foreach ($taxes as $tax) {
                                $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
                                $totalTaxPrice += $taxPrice;
                            }
                        }

                        $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

                        $data = [
                            'account_id' => $product->expense_chartaccount_id,
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $itemAmount,
                            'reference' => 'Bill',
                            'reference_id' => $bill->id,
                            'reference_sub_id' => $product->id,
                            'date' => $bill->bill_date,
                        ];

                        Utility::addTransactionLines($data);
                    } else {
                        // Handle the case where the product is not found or does not have expense_chartaccount_id (optional)
                        // You may want to log an error, throw an exception, or handle it in some way.
                    }
                }


                $bill_accounts = BillAccount::where('ref_id', $bill->id)->get();
                foreach ($bill_accounts as $bill_product) {
                    $data = [
                        'account_id' => $bill_product->chart_account_id,
                        'transaction_type' => 'Debit',
                        'transaction_amount' => $bill_product->price,
                        'reference' => 'Bill Account',
                        'reference_id' => $bill_product->ref_id,
                        'reference_sub_id' => $bill_product->id,
                        'date' => $bill->bill_date,
                    ];
                    Utility::addTransactionLines($data);
                }

                return redirect()->route('bill.index')->with('success', __('Bill successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Bill $bill)
    {
        if (\Auth::user()->can('delete bill')) {
            if ($bill->created_by == \Auth::user()->creatorId()) {
                $billpayments = $bill->payments;

                foreach ($billpayments as $key => $value) {
                    Utility::bankAccountBalance($value->account_id, $value->amount, 'credit');
                    $transaction = Transaction::where('payment_id', $value->id)->first();
                    $transaction->delete();

                    $billpayment = BillPayment::find($value->id)->first();
                    $billpayment->delete();
                }
                $bill->delete();

                if ($bill->vender_id != 0 && $bill->status != 0) {
                    Utility::updateUserBalance('vendor', $bill->vender_id, $bill->getDue(), 'credit');
                }
                BillProduct::where('bill_id', '=', $bill->id)->delete();

                DebitNote::where('bill', '=', $bill->id)->delete();

                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Account')->delete();
                TransactionLines::where('reference_id', $bill->id)->where('reference', 'Bill Payment')->delete();

                return redirect()->route('bill.index')->with('success', __('Bill successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function billNumber()
    {
          // $latest = Utility::getValByName('bill_starting_number');
          $latest = Bill::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
          if (!$latest) {
              return 1;
          }
          return $latest->bill_id + 1;
          // return $latest;
    }

    public function product(Request $request)
    {
        $data['product']     = $product = ProductService::find($request->product_id);
        $data['unit']        = !empty($product->unit()->name) ? $product->unit()->name : 0;
        $data['taxRate']     = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;
        $data['taxes']       = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;
        $salePrice           = $product->purchase_price;
        $quantity            = 1;
        $taxPrice            = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }

    public function productDestroy(Request $request)
    {

        if (\Auth::user()->can('delete bill product')) {
            $billProduct = BillProduct::find($request->id);
            $bill = Bill::find($billProduct->bill_id);

            Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');

            $productService = ProductService::find($billProduct->product_id);

            TransactionLines::where('reference_sub_id', $productService->id)->where('reference', 'Bill')->delete();

            BillProduct::where('id', '=', $request->id)->delete();
            BillAccount::where('id', '=', $request->account_id)->delete();

            return redirect()->back()->with('success', __('Bill product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {
        if (\Auth::user()->can('send bill')) {
            $bill            = Bill::where('id', $id)->first();
            $bill->send_date = date('Y-m-d');
            $bill->status    = 1;
            $bill->save();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

            $billId    = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);

            // Utility::userBalance('vendor', $vender->id, $bill->getTotal(), 'credit');

            Utility::updateUserBalance('vendor', $vender->id, $bill->getTotal(), 'debit');

            $uArr = [
                'bill_name' => $bill->name,
                'bill_number' => $bill->bill,
                'bill_url' => $bill->url,
            ];

            $bill_products = BillProduct::where('bill_id', $bill->id)->get();
            foreach ($bill_products as $bill_product) {
                $product = ProductService::find($bill_product->product_id);
                $totalTaxPrice = 0;
                if ($bill_product->tax != null) {
                    $taxes = \App\Models\Utility::tax($bill_product->tax);
                    foreach ($taxes as $tax) {
                        $taxPrice = \App\Models\Utility::taxRate($tax->rate, $bill_product->price, $bill_product->quantity, $bill_product->discount);
                        $totalTaxPrice += $taxPrice;
                    }
                }

                $itemAmount = ($bill_product->price * $bill_product->quantity) - ($bill_product->discount) + $totalTaxPrice;

                $data = [
                    'account_id' => $product->expense_chartaccount_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $itemAmount,
                    'reference' => 'Bill',
                    'reference_id' => $bill->id,
                    'reference_sub_id' => $product->id,
                    'date' => $bill->bill_date,
                ];
                Utility::addTransactionLines($data);
            }

            $bill_accounts = BillAccount::where('ref_id', $bill->id)->get();
            foreach ($bill_accounts as $bill_product) {
                $data = [
                    'account_id' => $bill_product->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $bill_product->price,
                    'reference' => 'Bill Account',
                    'reference_id' => $bill_product->ref_id,
                    'reference_sub_id' => $bill_product->id,
                    'date' => $bill->bill_date,
                ];
                Utility::addTransactionLines($data);
            }
            try {
                $resp = Utility::sendEmailTemplate('bill_sent', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Bill successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {

        if (\Auth::user()->can('send bill')) {
            $bill = Bill::where('id', $id)->first();

            $vender = Vender::where('id', $bill->vender_id)->first();

            $bill->name = !empty($vender) ? $vender->name : '';
            $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

            $billId    = Crypt::encrypt($bill->id);
            $bill->url = route('bill.pdf', $billId);

            $uArr = [
                'bill_name' => $bill->name,
                'bill_number' => $bill->bill,
                'bill_url' => $bill->url,
            ];
            try {
                $resp = Utility::sendEmailTemplate('bill_sent', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Bill successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function payment($bill_id)
    {
        if (\Auth::user()->can('create payment bill')) {
            $bill    = Bill::where('id', $bill_id)->first();
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('bill.payment', compact('venders', 'categories', 'accounts', 'bill'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $bill_id)
    {
        if (\Auth::user()->can('create payment bill')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',

                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $billPayment                 = new BillPayment();
            $billPayment->bill_id        = $bill_id;
            $billPayment->date           = $request->date;
            $billPayment->amount         = $request->amount;
            $billPayment->account_id     = $request->account_id;
            $billPayment->payment_method = 0;
            $billPayment->reference      = $request->reference;
            $billPayment->description    = $request->description;
            if (!empty($request->add_receipt)) {
                $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                // $request->add_receipt->storeAs('uploads/payment', $fileName);
                $billPayment->add_receipt = $fileName;

                $dir        = 'uploads/payment';
                $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                // $request->add_receipt  = $path['url'];
                if ($path['flag'] == 1) {
                    $url = $path['url'];
                } else {
                    return redirect()->back()->with('error', __($path['msg']));
                }

                $billPayment->save();
            }
            $billPayment->save();

            $bill  = Bill::where('id', $bill_id)->first();
            $due   = $bill->getDue();
            $total = $bill->getTotal();

            if ($bill->status == 0) {
                $bill->send_date = date('Y-m-d');
                $bill->save();
            }

            if ($due <= 0) {
                $bill->status = 4;
                $bill->save();
            } else {
                $bill->status = 3;
                $bill->save();
            }
            $billPayment->user_id    = $bill->vender_id;
            $billPayment->user_type  = 'Vender';
            $billPayment->type       = 'Partial';
            $billPayment->created_by = \Auth::user()->id;
            $billPayment->payment_id = $billPayment->id;
            $billPayment->category   = 'Bill';
            $billPayment->account    = $request->account_id;
            Transaction::addTransaction($billPayment);

            $vender = Vender::where('id', $bill->vender_id)->first();

            $payment         = new BillPayment();
            $payment->name   = $vender['name'];
            $payment->method = '-';
            $payment->date   = \Auth::user()->dateFormat($request->date);
            $payment->amount = \Auth::user()->priceFormat($request->amount);
            $payment->bill   = 'bill ' . \Auth::user()->billNumberFormat($billPayment->bill_id);

            Utility::updateUserBalance('vendor', $bill->vender_id, $request->amount, 'credit');

            Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

            $billPayments = BillPayment::where('bill_id', $bill->id)->get();
            foreach ($billPayments as $billPayment) {
                $accountId = BankAccount::find($billPayment->account_id);

                $data = [
                    'account_id' => $accountId->chart_account_id,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $billPayment->amount,
                    'reference' => 'Bill Payment',
                    'reference_id' => $bill->id,
                    'reference_sub_id' => $billPayment->id,
                    'date' => $billPayment->date,
                ];
                Utility::addTransactionLines($data);
            }

            $uArr = [
                'payment_name' => $payment->name,
                'payment_bill' => $payment->bill,
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->date,
                'payment_method' => $payment->method

            ];
            try {
                $resp = Utility::sendEmailTemplate('new_bill_payment', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        }
    }

    public function paymentDestroy(Request $request, $bill_id, $payment_id)
    {
        if (\Auth::user()->can('delete payment bill')) {
            $payment = BillPayment::find($payment_id);
            BillPayment::where('id', '=', $payment_id)->delete();

            $bill = Bill::where('id', $bill_id)->first();

            $due   = $bill->getDue();
            $total = $bill->getTotal();

            if ($due > 0 && $total != $due) {
                $bill->status = 3;
            } else {
                $bill->status = 2;
            }
            TransactionLines::where('reference_sub_id', $payment_id)->where('reference', 'Bill Payment')->delete();

            Utility::updateUserBalance('vendor', $bill->vender_id, $payment->amount, 'debit');

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');


            $bill->save();
            $type = 'Partial';
            $user = 'Vender';
            Transaction::destroyTransaction($payment_id, $type, $user);

            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function venderBill(Request $request)
    {
        if (\Auth::user()->can('manage vender bill')) {

            $status = Bill::$statues;

            $query = Bill::where('vender_id', '=', \Auth::user()->vender_id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (!empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (str_contains($request->bill_date, ' to ')) {
                $date_range = explode(' to ', $request->bill_date);
                $query->whereBetween('bill_date', $date_range);
            } elseif (!empty($request->bill_date)) {

                $query->where('bill_date', $request->bill_date);
            }

            // if (!empty($request->bill_date)) {
            //     $date_range = explode(' to ', $request->bill_date);
            //     $query->whereBetween('bill_date', $date_range);
            // }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $bills = $query->get();

            return view('bill.index', compact('bills', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function venderBillShow($id)
    {
        if (\Auth::user()->can('show bill')) {
            $bill_id = Crypt::decrypt($id);
            $bill    = Bill::where('id', $bill_id)->first();

            if ($bill->created_by == \Auth::user()->creatorId()) {
                $vendor = $bill->vender;
                $items = $bill->items;
                $items     = [];
                return view('bill.view', compact('bill', 'vendor',  'items'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function vender(Request $request)
    {
        $vender = Vender::where('id', '=', $request->id)->first();

        return view('bill.vender_detail', compact('vender'));
    }


    public function venderBillSend($bill_id)
    {
        return view('vender.bill_send', compact('bill_id'));
    }

    public function venderBillSendMail(Request $request, $bill_id)
    {

        $validator = \Validator::make(
            $request->all(),
            [
                'email' => 'required|email',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $email = $request->email;
        $bill  = Bill::where('id', $bill_id)->first();

        $vender     = Vender::where('id', $bill->vender_id)->first();
        $bill->name = !empty($vender) ? $vender->name : '';
        $bill->bill = \Auth::user()->billNumberFormat($bill->bill_id);

        $billId    = Crypt::encrypt($bill->id);
        $bill->url = route('bill.pdf', $billId);

        $uArr = [
            'bill_name' => $bill->name,
            'bill_number' => $bill->bill,
            'bill_url' => $bill->url,
        ];
        try {
            $resp = Utility::sendEmailTemplate('vendor_bill_sent', [$vender->id => $vender->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Bill successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function shippingDisplay(Request $request, $id)
    {
        $bill = Bill::find($id);

        if ($request->is_display == 'true') {
            $bill->shipping_display = 1;
        } else {
            $bill->shipping_display = 0;
        }
        $bill->save();

        return redirect()->back()->with('success', __('Shipping address status successfully changed.'));
    }

    public function duplicate($bill_id)
    {
        if (\Auth::user()->can('duplicate bill')) {
            $bill = Bill::where('id', $bill_id)->first();

            $duplicateBill                   = new Bill();
            $duplicateBill->bill_id          = $this->billNumber();
            $duplicateBill->vender_id        = $bill['vender_id'];
            $duplicateBill->bill_date        = date('Y-m-d');
            $duplicateBill->due_date         = $bill['due_date'];
            $duplicateBill->send_date        = null;
            $duplicateBill->category_id      = $bill['category_id'];
            $duplicateBill->order_number     = $bill['order_number'];
            $duplicateBill->status           = 0;
            $duplicateBill->shipping_display = $bill['shipping_display'];
            $duplicateBill->created_by       = $bill['created_by'];
            $duplicateBill->save();

            if ($duplicateBill) {
                $billProduct = BillProduct::where('bill_id', $bill_id)->get();
                foreach ($billProduct as $product) {
                    $duplicateProduct             = new BillProduct();
                    $duplicateProduct->bill_id    = $duplicateBill->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity   = $product->quantity;
                    $duplicateProduct->tax        = $product->tax;
                    $duplicateProduct->discount   = $product->discount;
                    $duplicateProduct->price      = $product->price;
                    $duplicateProduct->save();
                }
            }


            return redirect()->back()->with('success', __('Bill duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function previewBill($template, $color)
    {
        $objUser  = \Auth::user();
        $settings = Utility::settings();
        $bill     = new Bill();

        $vendor                   = new \stdClass();
        $vendor->email            = '<Email>';
        $vendor->shipping_name    = '<Vendor Name>';
        $vendor->shipping_country = '<Country>';
        $vendor->shipping_state   = '<State>';
        $vendor->shipping_city    = '<City>';
        $vendor->shipping_phone   = '<Vendor Phone Number>';
        $vendor->shipping_zip     = '<Zip>';
        $vendor->shipping_address = '<Address>';
        $vendor->billing_name     = '<Vendor Name>';
        $vendor->billing_country  = '<Country>';
        $vendor->billing_state    = '<State>';
        $vendor->billing_city     = '<City>';
        $vendor->billing_phone    = '<Vendor Phone Number>';
        $vendor->billing_zip      = '<Zip>';
        $vendor->billing_address  = '<Address>';
        $vendor->sku         = 'Test123';

        $totalTaxPrice = 0;
        $taxesData     = [];
        $items         = [];
        for ($i = 1; $i <= 3; $i++) {
            $item           = new \stdClass();
            $item->name     = 'Item ' . $i;
            $item->quantity = 1;
            $item->tax      = 5;
            $item->discount = 50;
            $item->price    = 100;

            $taxes = [
                'Tax 1',
                'Tax 2',
            ];

            $itemTaxes = [];
            foreach ($taxes as $k => $tax) {
                $taxPrice         = 10;
                $totalTaxPrice    += $taxPrice;
                $itemTax['name']  = 'Tax ' . $k;
                $itemTax['rate']  = '10 %';
                $itemTax['price'] = '$10';
                $itemTax['tax_price'] = 10;
                $itemTaxes[]      = $itemTax;

                // $taxPrice         = 10;
                // $totalTaxPrice    += $taxPrice;
                // $itemTax['name']  = 'Tax ' . $k;
                // $itemTax['rate']  = '10 %';
                // $itemTax['price'] = '$10';
                // $itemTaxes[]      = $itemTax;
                if (array_key_exists('Tax ' . $k, $taxesData)) {
                    $taxesData['Tax ' . $k] = $taxesData['Tax 1'] + $taxPrice;
                } else {
                    $taxesData['Tax ' . $k] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $bill->bill_id    = 1;
        $bill->issue_date = date('Y-m-d H:i:s');
        $bill->due_date   = date('Y-m-d H:i:s');
        $bill->itemData   = $items;

        $bill->totalTaxPrice = 60;
        $bill->totalQuantity = 3;
        $bill->totalRate     = 300;
        $bill->totalDiscount = 10;
        $bill->taxesData     = $taxesData;
        $bill->customField   = [];
        $customFields        = [];

        $preview      = 1;
        $color        = '#' . $color;
        $font_color   = Utility::getFontColor($color);
        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $bill_logo = Utility::getValByName('bill_logo');
        if (isset($bill_logo) && !empty($bill_logo)) {

            $img = Utility::get_file($bill_logo);

            $img = Utility::get_file('bill_logo/') . $bill_logo;


            // $img = asset(\Storage::url('bill_logo/') . $bill_logo);
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        return view('bill.templates.' . $template, compact('bill', 'preview', 'color', 'img', 'settings', 'vendor', 'font_color', 'customFields'));
    }

    public function bill($bill_id)
    {
        $settings = Utility::settings();
        $billId   = Crypt::decrypt($bill_id);

        $bill  = Bill::where('id', $billId)->first();
        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $bill->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $vendor = $bill->vender;

        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];
        $items         = [];

        foreach ($bill->items as $product) {

            $item              = new \stdClass();
            $item->name        = !empty($product->product) ? $product->product->name : '';
            $item->quantity    = $product->quantity;
            $item->tax         = $product->tax;
            $item->discount    = $product->discount;
            $item->price       = $product->price;
            $item->description = $product->description;

            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;

            $taxes     = Utility::tax($product->tax);
            $itemTaxes = [];
            if (!empty($item->tax)) {
                foreach ($taxes as $tax) {
                    $taxPrice      = Utility::taxRate($tax->rate, $item->price, $item->quantity, $item->discount);
                    $totalTaxPrice += $taxPrice;

                    $itemTax['name']  = $tax->name;
                    $itemTax['rate']  = $tax->rate . '%';
                    $itemTax['price'] = Utility::priceFormat($settings, $taxPrice);
                    $itemTax['tax_price'] = $taxPrice;
                    $itemTaxes[]      = $itemTax;


                    if (array_key_exists($tax->name, $taxesData)) {
                        $taxesData[$tax->name] = $taxesData[$tax->name] + $taxPrice;
                    } else {
                        $taxesData[$tax->name] = $taxPrice;
                    }
                }

                $item->itemTax = $itemTaxes;
            } else {
                $item->itemTax = [];
            }
            $items[] = $item;
        }

        $bill->itemData      = $items;
        $bill->totalTaxPrice = $totalTaxPrice;
        $bill->totalQuantity = $totalQuantity;
        $bill->totalRate     = $totalRate;
        $bill->totalDiscount = $totalDiscount;
        $bill->taxesData     = $taxesData;
        $bill->customField   = CustomField::getData($bill, 'bill');
        $customFields        = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'bill')->get();
        }

        //Set your logo
        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($bill->created_by);
        $bill_logo = $settings_data['bill_logo'];
        if (isset($bill_logo) && !empty($bill_logo)) {
            // $img = Utility::get_file('bill_logo') . $bill_logo;
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));

            // $img = asset(\Storage::url('bill_logo/') . $bill_logo);
            // $img = Utility::get_file($bill_logo);
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        if ($bill) {
            $color      = '#' . $settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            return view('bill.templates.' . $settings['bill_template'], compact('bill', 'color', 'settings', 'vendor', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveBillTemplateSettings(Request $request)
    {
        $user = \Auth::user();
        $post = $request->all();
        unset($post['_token']);

        if ($request->bill_logo) {
            $request->validate(
                [
                    'bill_logo' => 'image',
                ]
            );



            $dir = 'bill_logo/';
            $bill_logo         = $user->id . '_bill_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];

            $path = Utility::upload_file($request, 'bill_logo', $bill_logo, $dir, $validation);
            if ($path['flag'] == 1) {
                $retainer_logo = $path['url'];
            } else {
                return redirect()->back()->with('error', __($path['msg']));
            }



            // $path                 = $request->file('bill_logo')->storeAs('/bill_logo', $bill_logo);
            $post['bill_logo'] = $bill_logo;
        }

        if (isset($post['bill_template']) && (!isset($post['bill_color']) || empty($post['bill_color']))) {
            $post['bill_color'] = "ffffff";
        }

        foreach ($post as $key => $data) {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`) values (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $data,
                    $key,
                    \Auth::user()->creatorId(),
                ]
            );
        }

        return redirect()->back()->with('success', __('Bill Setting updated successfully'));
    }

    public function items(Request $request)
    {
        $items = BillProduct::where('bill_id', $request->bill_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }

    public function paybill($bill_id)
    {

        if (!empty($bill_id)) {
            $id = \Illuminate\Support\Facades\Crypt::decrypt($bill_id);

            $bill = bill::where('id', $id)->first();

            if (!is_null($bill)) {

                $settings = Utility::settings();

                $items         = [];
                $totalTaxPrice = 0;
                $totalQuantity = 0;
                $totalRate     = 0;
                $totalDiscount = 0;
                $taxesData     = [];

                foreach ($bill->items as $item) {
                    $totalQuantity += $item->quantity;
                    $totalRate     += $item->price;
                    $totalDiscount += $item->discount;
                    $taxes         = Utility::tax($item->tax);

                    $itemTaxes = [];
                    foreach ($taxes as $tax) {
                        if (!empty($tax)) {
                            $taxPrice            = Utility::taxRate($tax->rate, $item->price, $item->quantity);
                            $totalTaxPrice       += $taxPrice;
                            $itemTax['tax_name'] = $tax->tax_name;
                            $itemTax['tax']      = $tax->tax . '%';
                            $itemTax['price']    = Utility::priceFormat($settings, $taxPrice);
                            $itemTaxes[]         = $itemTax;

                            if (array_key_exists($tax->name, $taxesData)) {
                                $taxesData[$itemTax['tax_name']] = $taxesData[$tax->tax_name] + $taxPrice;
                            } else {
                                $taxesData[$tax->tax_name] = $taxPrice;
                            }
                        } else {
                            $taxPrice            = Utility::taxRate(0, $item->price, $item->quantity);
                            $totalTaxPrice       += $taxPrice;
                            $itemTax['tax_name'] = 'No Tax';
                            $itemTax['tax']      = '';
                            $itemTax['price']    = Utility::priceFormat($settings, $taxPrice);
                            $itemTaxes[]         = $itemTax;

                            if (array_key_exists('No Tax', $taxesData)) {
                                $taxesData[$tax->tax_name] = $taxesData['No Tax'] + $taxPrice;
                            } else {
                                $taxesData['No Tax'] = $taxPrice;
                            }
                        }
                    }
                    $item->itemTax = $itemTaxes;
                    $items[]       = $item;
                }
                $bill->items         = $items;
                $bill->totalTaxPrice = $totalTaxPrice;
                $bill->totalQuantity = $totalQuantity;
                $bill->totalRate     = $totalRate;
                $bill->totalDiscount = $totalDiscount;
                $bill->taxesData     = $taxesData;
                $ownerId = $bill->created_by;
                $company_setting = Utility::settingById($ownerId);
                $payment_setting = Utility::bill_payment_settings($ownerId);

                $users = User::where('id', $bill->created_by)->first();

                if (!is_null($users)) {
                    \App::setLocale($users->lang);
                } else {
                    $users = User::where('type', 'owner')->first();
                    \App::setLocale($users->lang);
                }


                $bill    = bill::where('id', $id)->first();
                $customer = $bill->customer;
                $iteams   = $bill->items;
                $company_payment_setting = Utility::getCompanyPaymentSetting($bill->created_by);

                return view('bill.billpay', compact('bill', 'iteams', 'company_setting', 'users', 'payment_setting'));
            } else {
                return abort('404', 'The Link You Followed Has Expired');
            }
        } else {
            return abort('404', 'The Link You Followed Has Expired');
        }
    }

    public function pdffrombill($id)
    {
        $settings = Utility::settings();

        $billId = Crypt::decrypt($id);
        $bill   = bill::where('id', $billId)->first();

        $data  = \DB::table('settings');
        $data  = $data->where('created_by', '=', $bill->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $user         = new User();
        $user->name   = $bill->name;
        $user->email  = $bill->contacts;
        $user->mobile = $bill->contacts;

        $user->bill_address = $bill->billing_address;
        $user->bill_zip     = $bill->billing_postalcode;
        $user->bill_city    = $bill->billing_city;
        $user->bill_country = $bill->billing_country;
        $user->bill_state   = $bill->billing_state;

        $user->address = $bill->shipping_address;
        $user->zip     = $bill->shipping_postalcode;
        $user->city    = $bill->shipping_city;
        $user->country = $bill->shipping_country;
        $user->state   = $bill->shipping_state;


        $items         = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];

        foreach ($bill->items as $product) {
            $item           = new \stdClass();
            $item->name     = $product->item;
            $item->quantity = $product->quantity;
            $item->tax      = !empty($product->taxs) ? $product->taxs->rate : '';
            $item->discount = $product->discount;
            $item->price    = $product->price;

            $totalQuantity += $item->quantity;
            $totalRate     += $item->price;
            $totalDiscount += $item->discount;

            $taxes     = \Utility::tax($product->tax);
            $itemTaxes = [];
            foreach ($taxes as $tax) {
                $taxPrice      = \Utility::taxRate($tax->rate, $item->price, $item->quantity);
                $totalTaxPrice += $taxPrice;

                $itemTax['name']  = $tax->tax_name;
                $itemTax['rate']  = $tax->rate . '%';
                $itemTax['price'] = \App\Models\Utility::priceFormat($settings, $taxPrice);
                $itemTaxes[]      = $itemTax;


                if (array_key_exists($tax->tax_name, $taxesData)) {
                    $taxesData[$tax->tax_name] = $taxesData[$tax->tax_name] + $taxPrice;
                } else {
                    $taxesData[$tax->tax_name] = $taxPrice;
                }
            }
            $item->itemTax = $itemTaxes;
            $items[]       = $item;
        }

        $bill->items         = $items;
        $bill->totalTaxPrice = $totalTaxPrice;
        $bill->totalQuantity = $totalQuantity;
        $bill->totalRate     = $totalRate;
        $bill->totalDiscount = $totalDiscount;
        $bill->taxesData     = $taxesData;

        //Set your logo
        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($bill->created_by);
        $bill_logo = $settings_data['bill_logo'];
        if (isset($bill_logo) && !empty($bill_logo)) {
            $img = asset(\Storage::url('bill_logo/') . $bill_logo);
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }

        if ($bill) {
            $color      = '#' . $settings['bill_color'];
            $font_color = Utility::getFontColor($color);

            return view('bill.templates.' . $settings['bill_template'], compact('bill', 'user', 'color', 'settings', 'img', 'font_color'));
        } else {
            return redirect()->route('pay.billpay', \Illuminate\Support\Facades\Crypt::encrypt($billId))->with('error', __('Permission denied.'));
        }
    }

    public function export()
    {
        $name = 'customer_' . date('Y-m-d i:h:s');
        $data = Excel::download(new BillExport(), $name . '.xlsx');

        return $data;
    }
}
