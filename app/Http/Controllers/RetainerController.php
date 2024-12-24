<?php

namespace App\Http\Controllers;


use App\Models\Customer;
use App\Models\Retainer;
use App\Models\CustomField;
use App\Models\Utility;
use App\Models\User;
use App\Models\ProductServiceCategory;
use App\Models\ProductService;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\InvoicePayment;
use App\Models\Transaction;
use App\Models\RetainerPayment;
use App\Models\RetainerProduct;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Exports\RetainerExport;



class RetainerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if (\Auth::user()->can('manage retainer')) {

            $customer = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customer->prepend('Select Customer', '');

            $status = Retainer::$statues;

            $query = Retainer::where('created_by', '=', \Auth::user()->creatorId());

            if (!empty($request->customer)) {
                $query->where('customer_id', '=', $request->customer);
            }

            if (str_contains($request->issue_date, ' to ')) {
                $date_range = explode(' to ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            } elseif (!empty($request->issue_date)) {
                $query->where('issue_date', $request->issue_date);
            }

            // if (!empty($request->issue_date)) {
            //     $date_range = explode(' to ', $request->issue_date);
            //     $query->whereBetween('issue_date', $date_range);
            // }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }

            $retainers = $query->get();
            return view('retainer.index', compact('retainers', 'customer', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($customerId)
    {

        if (\Auth::user()->can('create retainer')) {
            $customFields    = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'retainer')->get();
            $retainer_number = \Auth::user()->retainerNumberFormat($this->retainerNumber());
            $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $customers->prepend('Select Customer', '');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $product_services->prepend('--', '');

            return view('retainer.create', compact('customers', 'retainer_number', 'product_services', 'category', 'customFields', 'customerId'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (\Auth::user()->can('create retainer')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'customer_id' => 'required',
                    'issue_date' => 'required',
                    'category_id' => 'required',
                    'items' => 'required',

                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $status = Retainer::$statues;

            $retainer                 = new Retainer();
            $retainer->retainer_id    = $this->retainerNumber();
            $retainer->customer_id    = $request->customer_id;
            $retainer->status         = 0;
            $retainer->issue_date     = $request->issue_date;
            $retainer->category_id    = $request->category_id;
            $retainer->discount_apply = isset($request->discount_apply) ? 1 : 0;
            $retainer->created_by     = \Auth::user()->creatorId();
            $retainer->save();
            Utility::starting_number($retainer->retainer_id + 1, 'retainer');

            CustomField::saveData($retainer, $request->customField);
            $products = $request->items;

            for ($i = 0; $i < count($products); $i++) {
                $RetainerProduct              = new RetainerProduct();
                $RetainerProduct->retainer_id = $retainer->id;
                $RetainerProduct->product_id  = $products[$i]['item'];
                $RetainerProduct->quantity    = $products[$i]['quantity'];
                $RetainerProduct->tax         = $products[$i]['tax'];
                //              $RetainerProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                // $RetainerProduct->discount = (isset($request->discount_apply)) ? isset($products[$i]['discount']) ? $products[$i]['discount'] : 0 :0;
                $RetainerProduct->discount    = $products[$i]['discount'];
                $RetainerProduct->price       = $products[$i]['price'];
                $RetainerProduct->description = $products[$i]['description'];
                $RetainerProduct->save();
            }

            return redirect()->route('retainer.index')->with('success', __('Retainer successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($ids)
    {

        if (\Auth::user()->can('show retainer')) {
            $id       = Crypt::decrypt($ids);
            $retainer = Retainer::find($id);
            $users = User::where('id', $retainer->created_by)->first();
            // dd($retainer);
            if ($retainer->created_by == \Auth::user()->creatorId()) {
                $customer = $retainer->customer;
                $iteams   = $retainer->items;
                $status   = Retainer::$statues;

                $retainer->customField = CustomField::getData($retainer, 'retainer');
                $customFields          = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'retainer')->get();

                return view('retainer.view', compact('retainer', 'customer', 'iteams', 'status', 'customFields', 'users'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($ids)
    {

        if (\Auth::user()->can('edit retainer')) {
            $id              = Crypt::decrypt($ids);
            $retainer        = Retainer::find($id);
            $retainer_number = \Auth::user()->retainerNumberFormat($retainer->retainer_id);
            $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $category = ProductServiceCategory::where('created_by', \Auth::user()->creatorId())->where('type', 'income')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');
            $product_services = ProductService::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $retainer->customField = CustomField::getData($retainer, 'retainer');
            $customFields          = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'retainer')->get();

            $items = [];

            foreach ($retainer->items as $retainerItem) {
                $itemAmount               = $retainerItem->quantity * $retainerItem->price;
                $retainerItem->itemAmount = $itemAmount;
                $retainerItem->taxes      = Utility::tax($retainerItem->tax);
                $items[]                  = $retainerItem;
            }
            return view('retainer.edit', compact('customers', 'product_services', 'retainer', 'retainer_number', 'category', 'customFields', 'items'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function retainerNumber()
    {
        $latest = Utility::getValByName('retainer_starting_number');
        // $latest = Retainer::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        // return $latest->retainer_id + 1;
        return $latest;
    }

    public function product(Request $request)
    {

        $data['product'] = $product = ProductService::find($request->product_id);

        $data['unit']    = (!empty($product->unit)) ? $product->unit->name : 0;
        $data['taxRate'] = $taxRate = !empty($product->tax_id) ? $product->taxRate($product->tax_id) : 0;

        $data['taxes'] = !empty($product->tax_id) ? $product->tax($product->tax_id) : 0;

        $salePrice           = $product->sale_price;
        $quantity            = 1;
        $taxPrice            = ($taxRate / 100) * ($salePrice * $quantity);
        $data['totalAmount'] = ($salePrice * $quantity);

        return json_encode($data);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Retainer $retainer)
    {
        //
        // dd($request->all());
        if (\Auth::user()->can('edit retainer')) {
            if ($retainer->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'customer_id' => 'required',
                        'issue_date' => 'required',
                        'category_id' => 'required',
                        'items' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->route('retainer.index')->with('error', $messages->first());
                }
                $retainer->customer_id    = $request->customer_id;
                $retainer->issue_date     = $request->issue_date;
                $retainer->category_id    = $request->category_id;
                $retainer->discount_apply = isset($request->discount_apply) ? 1 : 0;
                $retainer->save();
                CustomField::saveData($retainer, $request->customField);
                $products = $request->items;


                for ($i = 0; $i < count($products); $i++) {
                    $retainerProduct = RetainerProduct::find($products[$i]['id']);

                    if ($retainerProduct == null) {
                        $retainerProduct              = new RetainerProduct();
                        $retainerProduct->retainer_id = $retainer->id;

                        Utility::total_quantity('minus', $products[$i]['quantity'], $products[$i]['item']);

                        $updatePrice = ($products[$i]['price'] * $products[$i]['quantity']) + ($products[$i]['itemTaxPrice']) - ($products[$i]['discount']);
                        Utility::updateUserBalance('customer', $request->customer_id, $updatePrice, 'credit');
                    } else {

                        Utility::total_quantity('minus', $retainerProduct->quantity, $retainerProduct->product_id);
                    }

                    if (isset($products[$i]['item'])) {
                        $retainerProduct->product_id = $products[$i]['item'];
                    }
                    $retainerProduct->quantity    = $products[$i]['quantity'];
                    $retainerProduct->tax         = $products[$i]['tax'];
                    // $retainerProduct->discount    = isset($products[$i]['discount']) ? $products[$i]['discount'] : 0;
                    $retainerProduct->discount    = $products[$i]['discount'];
                    $retainerProduct->price       = $products[$i]['price'];
                    $retainerProduct->description = $products[$i]['description'];
                    $retainerProduct->save();
                }
                return redirect()->route('retainer.index')->with('success', __('Retainer successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Retainer $retainer)
    {
        if (\Auth::user()->can('delete retainer')) {
            if ($retainer->created_by == \Auth::user()->creatorId()) {
                foreach ($retainer->payments as $retainer) {
                    Utility::bankAccountBalance($retainer->account_id, $retainer->amount, 'debit');

                    $retainerpayment = RetainerPayment::find($retainer->id);
                    $retainer->delete();
                    $retainerpayment->delete();
                }

                if ($retainer->customer_id != 0 && $retainer->status != 0) {
                    Utility::updateUserBalance('customer', $retainer->customer_id, $retainer->getDue(), 'debit');
                }

                RetainerProduct::where('retainer_id', '=', $retainer->id)->delete();
                $retainer->delete();

                return redirect()->route('retainer.index')->with('success', __('Retainer successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function duplicate($retainer_id)
    {
        if (\Auth::user()->can('duplicate retainer')) {
            $retainer                       = Retainer::where('id', $retainer_id)->first();
            $duplicateRetainer              = new Retainer();
            $duplicateRetainer->retainer_id = $this->retainerNumber();
            $duplicateRetainer->customer_id = $retainer['customer_id'];
            $duplicateRetainer->issue_date  = date('Y-m-d');
            $duplicateRetainer->send_date   = null;
            $duplicateRetainer->category_id = $retainer['category_id'];
            $duplicateRetainer->status      = 0;
            $duplicateRetainer->created_by  = $retainer['created_by'];
            $duplicateRetainer->save();

            if ($duplicateRetainer) {
                $retainerProduct = RetainerProduct::where('retainer_id', $retainer_id)->get();
                foreach ($retainerProduct as $product) {
                    $duplicateProduct              = new RetainerProduct();
                    $duplicateProduct->retainer_id = $duplicateRetainer->id;
                    $duplicateProduct->product_id  = $product->product_id;
                    $duplicateProduct->quantity    = $product->quantity;
                    $duplicateProduct->tax         = $product->tax;
                    $duplicateProduct->discount    = $product->discount;
                    $duplicateProduct->price       = $product->price;
                    $duplicateProduct->save();
                }
            }

            return redirect()->back()->with('success', __('Retainer duplicate successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function sent($id)
    {

        if (\Auth::user()->can('send retainer')) {
            $retainer            = Retainer::where('id', $id)->first();
            $retainer->send_date = date('Y-m-d');
            $retainer->status    = 1;
            $retainer->save();

            $customer           = Customer::where('id', $retainer->customer_id)->first();
            $retainer->name     = !empty($customer) ? $customer->name : '';
            $retainer->retainer = \Auth::user()->retainerNumberFormat($retainer->retainer_id);

            $retainerId    = Crypt::encrypt($retainer->id);
            $retainer->url = route('retainer.pdf', $retainerId);

            Utility::updateUserBalance('customer', $customer->id, $retainer->getTotal(), 'credit');

            $uArr = [
                'retainer_name' => $retainer->name,
                'retainer_number' => $retainer->retainer,
                'retainer_url' => $retainer->url,
            ];

            try {
                $resp = Utility::sendEmailTemplate('retainer_sent', [$customer->id => $customer->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Retainer successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function statusChange(Request $request, $id)
    {

        $status           = $request->status;
        $retainer         = Retainer::find($id);
        $retainer->status = $status;

        $retainer->save();

        return redirect()->back()->with('success', __('Retainer status changed successfully.'));
    }


    public function payretainer($retainer_id)
    {
        try {
            if (!empty($retainer_id)) {
                $id = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);

                $retainer = Retainer::where('id','=', $id)->first();

                if (!is_null($retainer)) {

                    $settings = Utility::settings();

                    $items         = [];
                    $totalTaxPrice = 0;
                    $totalQuantity = 0;
                    $totalRate     = 0;
                    $totalDiscount = 0;
                    $taxesData     = [];

                    foreach ($retainer->items as $item) {
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
                    $retainer->items         = $items;
                    $retainer->totalTaxPrice = $totalTaxPrice;
                    $retainer->totalQuantity = $totalQuantity;
                    $retainer->totalRate     = $totalRate;
                    $retainer->totalDiscount = $totalDiscount;
                    $retainer->taxesData     = $taxesData;

                    $ownerId = $retainer->created_by;


                    $company_setting = Utility::settingById($ownerId);

                    $payment_setting = Utility::invoice_payment_settings($ownerId);
                    // dd($payment_setting);

                    $users = User::where('id', $retainer->created_by)->first();

                    if (!is_null($users)) {
                        \App::setLocale($users->lang);
                    } else {
                        $users = User::where('type', 'owner')->first();
                        \App::setLocale($users->lang);
                    }

                    $retainer    = Retainer::where('id', $id)->first();
                    $customer = $retainer->customer;
                    $iteams   = $retainer->items;
                    $company_payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);

                    return view('retainer.retainerpay', compact('retainer', 'iteams', 'company_setting', 'users', 'company_payment_setting'));
                } else {
                    return abort('404', 'The Link You Followed Has Expired');
                }
            } else {
                return abort('404', 'The Link You Followed Has Expired');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Not Found');
        }
    }

    public function retainer($retainer_id)
    {

        $settings   = Utility::settings();
        $retainerId = Crypt::decrypt($retainer_id);
        $retainer   = Retainer::where('id', $retainerId)->first();

        $data  = DB::table('settings');
        $data  = $data->where('created_by', '=', $retainer->created_by);
        $data1 = $data->get();

        foreach ($data1 as $row) {
            $settings[$row->name] = $row->value;
        }

        $customer = $retainer->customer;

        $items         = [];
        $totalTaxPrice = 0;
        $totalQuantity = 0;
        $totalRate     = 0;
        $totalDiscount = 0;
        $taxesData     = [];
        foreach ($retainer->items as $product) {

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

            $taxes = Utility::tax($product->tax);

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
        $retainer->itemData      = $items;
        $retainer->totalTaxPrice = $totalTaxPrice;
        $retainer->totalQuantity = $totalQuantity;
        $retainer->totalRate     = $totalRate;
        $retainer->totalDiscount = $totalDiscount;
        $retainer->taxesData     = $taxesData;

        $retainer->customField = CustomField::getData($retainer, 'retainer');
        $customFields          = [];
        if (!empty(\Auth::user())) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'retainer')->get();
        }


        //Set your logo
        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $settings_data = \App\Models\Utility::settingsById($retainer->created_by);
        $retainer_logo = $settings_data['retainer_logo'];
        if (isset($retainer_logo) && !empty($retainer_logo)) {
            // $img = asset(\Storage::url('retainer_logo/') . $retainer_logo);
            $img = Utility::get_file('retainer_logo/') . $retainer_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        if ($retainer) {
            $color      = '#' . $settings['retainer_color'];
            $font_color = Utility::getFontColor($color);

            return view('retainer.templates.' . $settings['retainer_template'], compact('retainer', 'color', 'settings', 'customer', 'img', 'font_color', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function resent($id)
    {
        if (\Auth::user()->can('send retainer')) {
            $retainer = Retainer::where('id', $id)->first();

            $customer           = Customer::where('id', $retainer->customer_id)->first();
            $retainer->name     = !empty($customer) ? $customer->name : '';
            $retainer->retainer = \Auth::user()->retainerNumberFormat($retainer->retainer_id);

            $retainerId    = Crypt::encrypt($retainer->id);
            $retainer->url = route('retainer.pdf', $retainerId);

            $uArr = [
                'retainer_name' => $retainer->name,
                'retainer_number' => $retainer->retainer,
                'retainer_url' => $retainer->url,
            ];

            try {
                $resp = Utility::sendEmailTemplate('retainer_sent', [$customer->id => $customer->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Retainer successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function saveRetainerTemplateSettings(Request $request)
    {
        $user = \Auth::user();
        $post = $request->all();
        unset($post['_token']);

        if ($request->retainer_logo) {
            $request->validate(
                [
                    'retainer_logo' => 'image',
                ]
            );


            // $path = $request->file('retainer_logo')->storeAs('/retainer_logo', $retainer_logo);



            $dir = 'retainer_logo/';
            $retainer_logo         = $user->id . '_retainer_logo.png';
            $validation = [
                'mimes:' . 'png',
                'max:' . '20480',
            ];

            $path = Utility::upload_file($request, 'retainer_logo', $retainer_logo, $dir, $validation);
            if ($path['flag'] == 1) {
                $proposal_logo = $path['url'];
            } else {
                return redirect()->back()->with('error', __($path['msg']));
            }

            $post['retainer_logo'] = $retainer_logo;
        }

        if (isset($post['retainer_template']) && (!isset($post['retainer_color']) || empty($post['retainer_color']))) {
            $post['retainer_color'] = "ffffff";
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

        return redirect()->back()->with('success', __('Retainer Setting updated successfully'));
    }

    public function previewRetainer($template, $color)
    {
        $objUser  = \Auth::user();
        $settings = Utility::settings();
        $retainer = new Retainer();

        $customer                   = new \stdClass();
        $customer->email            = '<Email>';
        $customer->shipping_name    = '<Customer Name>';
        $customer->shipping_country = '<Country>';
        $customer->shipping_state   = '<State>';
        $customer->shipping_city    = '<City>';
        $customer->shipping_phone   = '<Customer Phone Number>';
        $customer->shipping_zip     = '<Zip>';
        $customer->shipping_address = '<Address>';
        $customer->billing_name     = '<Customer Name>';
        $customer->billing_country  = '<Country>';
        $customer->billing_state    = '<State>';
        $customer->billing_city     = '<City>';
        $customer->billing_phone    = '<Customer Phone Number>';
        $customer->billing_zip      = '<Zip>';
        $customer->billing_address  = '<Address>';
        $customer->sku         = 'Test123';

        $totalTaxPrice = 0;
        $taxesData     = [];

        $items = [];
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

        $retainer->retainer_id = 1;
        $retainer->issue_date  = date('Y-m-d H:i:s');
        $retainer->due_date    = date('Y-m-d H:i:s');
        $retainer->itemData    = $items;

        $retainer->totalTaxPrice = 60;
        $retainer->totalQuantity = 3;
        $retainer->totalRate     = 300;
        $retainer->totalDiscount = 10;
        $retainer->taxesData     = $taxesData;
        $retainer->customField   = [];
        $customFields            = [];

        $preview    = 1;
        $color      = '#' . $color;
        $font_color = Utility::getFontColor($color);

        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $retainer_logo = Utility::getValByName('retainer_logo');
        if (isset($retainer_logo) && !empty($retainer_logo)) {
            // $img = Utility::get_file($retainer_logo);
            $img = Utility::get_file('retainer_logo/') . $retainer_logo;
        } else {
            $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));
        }


        return view('retainer.templates.' . $template, compact('retainer', 'preview', 'color', 'img', 'settings', 'customer', 'font_color', 'customFields'));
    }

    public function payment($retainer_id)
    {
        if (\Auth::user()->can('create payment invoice')) {
            $retainer = Retainer::where('id', $retainer_id)->first();

            $customers  = Customer::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('retainer.payment', compact('customers', 'categories', 'accounts', 'retainer'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function createPayment(Request $request, $retainer_id)
    {

        if (\Auth::user()->can('create payment invoice')) {
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

            $retainerPayment                 = new RetainerPayment();
            $retainerPayment->retainer_id     = $retainer_id;
            $retainerPayment->date           = $request->date;
            $retainerPayment->amount         = $request->amount;
            $retainerPayment->account_id     = $request->account_id;
            $retainerPayment->payment_method = 0;
            $retainerPayment->reference      = $request->reference;
            $retainerPayment->description    = $request->description;
            if (!empty($request->add_receipt)) {
                $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                // $request->add_receipt->storeAs('uploads/retainerpayment', $fileName);
                $retainerPayment->add_receipt = $fileName;

                $dir        = 'uploads/retainerpayment';
                $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                // $request->add_receipt  = $path['url'];
                if ($path['flag'] == 1) {
                    $url = $path['url'];
                } else {
                    return redirect()->back()->with('error', __($path['msg']));
                }

                $retainerPayment->save();
            }
            $retainerPayment->save();

            $retainer = Retainer::where('id', $retainer_id)->first();
            $due     = $retainer->getDue();
            $total   = $retainer->getTotal();
            if ($retainer->status == 0) {
                $retainer->send_date = date('Y-m-d');
                $retainer->save();
            }

            if ($due <= 0) {
                $retainer->status = 4;
                $retainer->save();
            } else {
                $retainer->status = 3;
                $retainer->save();
            }
            $retainerPayment->user_id    = $retainer->customer_id;
            $retainerPayment->user_type  = 'Customer';
            $retainerPayment->type       = 'Partial';
            $retainerPayment->created_by = \Auth::user()->id;
            $retainerPayment->payment_id = $retainerPayment->id;
            $retainerPayment->category   = 'Retainer';
            $retainerPayment->account    = $request->account_id;

            Transaction::addTransaction($retainerPayment);

            $customer = Customer::where('id', $retainer->customer_id)->first();

            $payment            = new RetainerPayment();
            $payment->name      = $customer['name'];
            $payment->date      = \Auth::user()->dateFormat($request->date);
            $payment->amount    = \Auth::user()->priceFormat($request->amount);
            $payment->retainer  = 'retainer ' . \Auth::user()->retainerNumberFormat($retainer->retainer_id);
            $payment->dueAmount = \Auth::user()->priceFormat($retainer->getDue());

            Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

            Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

            $uArr = [
                'payment_name' => $payment->name,
                'payment_amount' => $payment->amount,
                'retainer_number' => $payment->retainer,
                'payment_date' => $payment->date,
                'payment_dueAmount' => $payment->dueAmount,
            ];
            try {
                $resp = Utility::sendEmailTemplate('new_retainer_payment', [$customer->id => $customer->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->back()->with('success', __('Payment successfully added.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        }
    }


    public function paymentReminder($retainer_id)
    {

        $retainer            = Retainer::find($retainer_id);
        $customer            = Customer::where('id', $retainer->customer_id)->first();
        $retainer->dueAmount = \Auth::user()->priceFormat($retainer->getDue());
        $retainer->name      = $customer['name'];
        $retainer->date      = \Auth::user()->dateFormat($retainer->send_date);
        $retainer->retainer  = \Auth::user()->retainerNumberFormat($retainer->retainer_id);

        $uArr = [
            'payment_name' => $retainer->name,
            'invoice_number' => $retainer->retainer,
            'payment_dueAmount' => $retainer->dueAmount,
            'payment_date' => $retainer->date,

        ];
        try {
            $resp = Utility::sendEmailTemplate('payment_reminder', [$customer->id => $customer->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        // //Twilio Notification
        // $setting  = Utility::settings(\Auth::user()->creatorId());
        // $customer = Customer::find($retainer->customer_id);
        // if(isset($setting['payment_notification']) && $setting['payment_notification'] ==1)
        // {
        //     $uArr = [
        //         'retainer_name' => $customer->name,
        //         'retainer_number'  => \Auth::user()->retainerNumberFormat($retainer->retainer_id);
        //         'retainer_url'  =>  $bill->url,
        //     ];
        //     Utility::send_twilio_msg($vendor->contact,'new_retainer', $uArr);

        //     $msg = __("New Payment Reminder of ").' '. \Auth::user()->retainerNumberFormat($retainer->retainer_id).' '. __("created by").' ' .\Auth::user()->name.'.';

        //     Utility::send_twilio_msg($customer->contact,$msg);
        // }

        // webhook
        $module = 'New Reminder';
        $webhook =  Utility::webhookSetting($module);
        if ($webhook) {
            $parameter = json_encode($retainer);
            // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
            $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

            if ($status == true) {
                return redirect()->back()->with('success', __('Payment reminder successfully send.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
            } else {
                return redirect()->back()->with('error', __('Webhook call failed.'));
            }
        }

        return redirect()->back()->with('success', __('Payment reminder successfully send.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function paymentDestroy(Request $request, $retainer_id, $payment_id)
    {

        if (\Auth::user()->can('delete payment invoice')) {
            $payment = RetainerPayment::find($payment_id);

            RetainerPayment::where('id', '=', $payment_id)->delete();

            $retainer = Retainer::where('id', $retainer_id)->first();
            $due     = $retainer->getDue();
            $total   = $retainer->getTotal();

            if ($due > 0 && $total != $due) {
                $retainer->status = 3;
            } else {
                $retainer->status = 2;
            }

            $retainer->save();
            $type = 'Partial';
            $user = 'Customer';
            Transaction::destroyTransaction($payment_id, $type, $user);

            Utility::updateUserBalance('customer', $retainer->customer_id, $payment->amount, 'credit');

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'debit');

            return redirect()->back()->with('success', __('Payment successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function invoiceNumber()
    {
        $latest = Invoice::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->invoice_id + 1;
    }


    public function convert($retainer_id)
    {

        if (\Auth::user()->can('convert invoice retainer')) {
            $retainer             = Retainer::where('id', $retainer_id)->first();
            $retainer->is_convert = 1;
            $retainer->save();

            $convertInvoice              = new Invoice();
            $convertInvoice->invoice_id  = $this->invoiceNumber();
            $convertInvoice->customer_id = $retainer['customer_id'];
            $convertInvoice->issue_date  = date('Y-m-d');
            $convertInvoice->due_date    = date('Y-m-d');
            $convertInvoice->send_date   = null;
            $convertInvoice->category_id = $retainer['category_id'];
            $convertInvoice->status      = 0;
            $convertInvoice->created_by  = $retainer['created_by'];
            $convertInvoice->save();

            $retainer->converted_invoice_id = $convertInvoice->id;
            $retainer->save();

            if ($convertInvoice) {
                $retainerProduct = RetainerProduct::where('retainer_id', $retainer_id)->get();
                foreach ($retainerProduct as $product) {
                    $duplicateProduct             = new InvoiceProduct();
                    $duplicateProduct->invoice_id = $convertInvoice->id;
                    $duplicateProduct->product_id = $product->product_id;
                    $duplicateProduct->quantity   = $product->quantity;
                    $duplicateProduct->tax        = $product->tax;
                    $duplicateProduct->discount   = $product->discount;
                    $duplicateProduct->price      = $product->price;
                    $duplicateProduct->save();


                    Utility::total_quantity('minus', $duplicateProduct->quantity, $duplicateProduct->product_id);

                    //Product Stock Report
                    $type = 'invoice';
                    $type_id = $convertInvoice->id;
                    $description = $duplicateProduct->quantity . '  ' . __(' quantity sold in invoice') . ' ' . \Auth::user()->invoiceNumberFormat($convertInvoice->invoice_id);
                    Utility::addProductStock($product->product_id, $duplicateProduct->quantity, $type, $description, $type_id);
                }
            }
            if ($convertInvoice) {
                $retainerPayment = RetainerPayment::where('retainer_id', $retainer_id)->get();
                foreach ($retainerPayment as $payment) {
                    $duplicatePayment                   = new InvoicePayment();
                    $duplicatePayment->invoice_id       = $convertInvoice->id;
                    $duplicatePayment->date             = $payment->date;
                    $duplicatePayment->amount           = $payment->amount;
                    $duplicatePayment->account_id       = $payment->account_id;
                    $duplicatePayment->payment_method   = $payment->payment_method;
                    $duplicatePayment->receipt          = $payment->receipt;
                    $duplicatePayment->payment_type     = $payment->payment_type;
                    $duplicatePayment->reference        = $payment->reference;
                    $duplicatePayment->description      = 'Payment by Retainer' . \Auth::user()->retainerNumberFormat($retainer->retainer_id);
                    $duplicatePayment->save();
                }
            }


            return redirect()->back()->with('success', __('Retainer to invoice convert successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerRetainer(Request $request)
    {

        if (\Auth::user()->can('manage customer proposal')) {

            $status = Retainer::$statues;

            $query = Retainer::where('customer_id', '=', \Auth::user()->id)->where('status', '!=', '0')->where('created_by', \Auth::user()->creatorId());

            if (str_contains($request->issue_date, ' to ')) {
                $date_range = explode(' to ', $request->issue_date);
                $query->whereBetween('issue_date', $date_range);
            } elseif (!empty($request->issue_date)) {

                $query->where('issue_date', $request->issue_date);
            }

            // if (!empty($request->issue_date)) {
            //     $date_range = explode(' to ', $request->issue_date);
            //     $query->whereBetween('issue_date', $date_range);
            // }

            if (!empty($request->status)) {
                $query->where('status', '=', $request->status);
            }
            $retainers = $query->get();

            return view('retainer.index', compact('retainers', 'status'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }



    public function customerRetainerShow($id)
    {
        // if (\Auth::user()->can('show retainer')) {
        $retainer_id = Crypt::decrypt($id);
        $retainer    = Retainer::where('id', $retainer_id)->first();

        // dd($retainer->created_by);
        if ($retainer->created_by == \Auth::user()->creatorId()) {
            $customer = $retainer->customer;
            $iteams   = $retainer->items;

            $company_payment_setting = Utility::getCompanyPaymentSetting($id);
            return view('retainer.view', compact('retainer', 'customer', 'iteams', 'company_payment_setting'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
        // } else {
        //     return redirect()->back()->with('error', __('Permission denied.'));
        // }
    }

    public function customerRetainerSend($retainer_id)
    {
        return view('customer.retainer_send', compact('retainer_id'));
    }

    public function customerRetainerSendMail(Request $request, $retainer_id)
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

        $email   = $request->email;
        $retainer = Retainer::where('id', $retainer_id)->first();

        $customer         = Customer::where('id', $retainer->customer_id)->first();
        $retainer->name    = !empty($customer) ? $customer->name : '';
        $retainer->retainer = \Auth::user()->retainerNumberFormat($retainer->retainer_id);

        $retainerId    = Crypt::encrypt($retainer->id);
        $retainer->url = route('retainer.pdf', $retainerId);

        $uArr = [
            'retainer_name' => $retainer->name,
            'retainer_number' => $retainer->retainer,
            'retainer_url' => $retainer->url,
        ];

        try {
            $resp = Utility::sendEmailTemplate('customer_retainer_sent', [$customer->id => $customer->email], $uArr);
        } catch (\Exception $e) {
            $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
        }

        return redirect()->back()->with('success', __('Retainer successfully sent.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
    }

    public function export()
    {
        $name = 'customer_' . date('Y-m-d i:h:s');
        $data = Excel::download(new RetainerExport(), $name . '.xlsx');

        return $data;
    }

    public function productDestroy(Request $request)
    {
        if (\Auth::user()->can('delete retainer product')) {

            $retainerProduct = RetainerProduct::find($request->id);
            $retainer = Retainer::find($retainerProduct->retainer_id);

            Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

            RetainerProduct::where('id', '=', $request->id)->delete();

            return redirect()->back()->with('success', __('Retainer product successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function items(Request $request)
    {
        $items = RetainerProduct::where('retainer_id', $request->retainer_id)->where('product_id', $request->product_id)->first();

        return json_encode($items);
    }
}
