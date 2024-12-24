<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\RetainerPayment;
use App\Models\Retainer;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class PaytrController extends Controller
{


    public function invoicepayWithPaytr(Request $request, $invoice_id)
    {

        $invoice = Invoice::find($invoice_id);
        $customer = User::find($invoice->created_by);
        $admin = Utility::getCompanyPaymentSetting($invoice->created_by);
        $companyPaymentSetting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $paytr_merchant_id = $companyPaymentSetting['paytr_merchant_id'];
        $paytr_merchant_key = $companyPaymentSetting['paytr_merchant_key'];
        $paytr_merchant_salt = $companyPaymentSetting['paytr_merchant_salt'];

        $get_amount = $request->amount;
        $request->validate(['amount' => 'required|numeric|min:0']);

        try {

            $merchant_id    = $paytr_merchant_id;
            $merchant_key   = $paytr_merchant_key;
            $merchant_salt  = $paytr_merchant_salt;

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

            $store_id = $customer->current_store;

            $store = Customer::where('id', $store_id)->get()->first();
            $email = $customer->email;
            $payment_amount = $get_amount;
            $merchant_oid = $orderID;
            $user_name = $customer->name;
            $user_address = !empty($store->address) ? $store->address : 'no address';
            $user_phone = !empty($store->whatsapp_number) ? $store->whatsapp : '0000000000';


            $user_basket = base64_encode(json_encode(array(
                array("Invoice", $payment_amount, 1),
            )));

            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }

            $user_ip = $ip;
            $timeout_limit = "30";
            $debug_on = 1;
            $test_mode = 0;
            $no_installment = 0;
            $max_installment = 0;
            $currency = $admin['currency'] ? $admin['currency'] : 'USD';

            $payment_amount = $payment_amount * 100;
            $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
            $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

            $request['orderID'] = $orderID;
            $request['invoice_id'] = $invoice->id;
            $request['price'] = $get_amount;
            $request['payment_status'] = 'failed';
            $payment_failed = $request->all();
            $request['payment_status'] = 'success';
            $payment_success = $request->all();

            $post_vals = array(
                'merchant_id' => $merchant_id,
                'user_ip' => $user_ip,
                'merchant_oid' => $merchant_oid,
                'email' => $email,
                'payment_amount' => $payment_amount,
                'paytr_token' => $paytr_token,
                'user_basket' => $user_basket,
                'debug_on' => $debug_on,
                'no_installment' => $no_installment,
                'max_installment' => $max_installment,
                'user_name' => $user_name,
                'user_address' => $user_address,
                'user_phone' => $user_phone,
                'merchant_ok_url' => route('invoice.pay.paytr.success', $payment_success),
                'merchant_fail_url' => route('invoice.pay.paytr.success', $payment_failed),
                'timeout_limit' => $timeout_limit,
                'currency' => $currency,
                'test_mode' => $test_mode
            );


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);


            $result = @curl_exec($ch);


            if (curl_errno($ch)) {
                die("PAYTR IFRAME connection error. err:" . curl_error($ch));
            }

            curl_close($ch);

            $result = json_decode($result, 1);

            if ($result['status'] == 'success') {
                $token = $result['token'];
            } else {
                return redirect()->route('invoice.show')->with('error', $result['reason']);
            }
            return view('paytr_payment.index', compact('token'));
        } catch (\Throwable $th) {
            // dd($th);
            // return redirect()->route('invoice.show')->with('error', $th->getMessage());
            return redirect()->back()->with('success', __('Payment successfully added.'));
        }
    }

    public function invoicePaytrsuccess(Request $request)
    {
        if ($request->payment_status == "success") {
            $invoice = Invoice::find($request->invoice_id);
            $setting = Utility::settingsById($invoice->created_by);

            if (Auth::check()) {
                $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
                $objUser     = Auth::user();
                $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
                //            $this->setApiContext();
            } else {
                $user = User::where('id', $invoice->created_by)->first();
                $settings = Utility::settingById($invoice->created_by);
                $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
                //            $this->non_auth_setApiContext($invoice->created_by);
                $objUser = $user;
            }
            $amount = $request->amount;

            try {
                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
                $payments = InvoicePayment::create(
                    [

                        'invoice_id' => $invoice->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'account_id' => 0,
                        'payment_method' => 0,
                        'order_id' => $order_id,
                        'currency' => $setting['site_currency'],
                        'txn_id' => $order_id,
                        'payment_type' => __('PayTR'),
                        'receipt' => '',
                        'reference' => '',
                        'description' => 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
                    ]
                );

                if ($invoice->getDue() <= 0) {
                    $invoice->status = 4;
                    $invoice->save();
                } elseif (($invoice->getDue() - $payments->amount) == 0) {
                    $invoice->status = 4;
                    $invoice->save();
                } elseif ($invoice->getDue() > 0) {
                    $invoice->status = 3;
                    $invoice->save();
                } else {
                    $invoice->status = 2;
                    $invoice->save();
                }

                $invoicePayment              = new \App\Models\Transaction();
                $invoicePayment->user_id     = $invoice->customer_id;
                $invoicePayment->user_type   = 'Customer';
                $invoicePayment->type        = 'PayTR';
                $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->id : $invoice->customer_id;
                $invoicePayment->payment_id  = $invoicePayment->id;
                $invoicePayment->category    = 'Invoice';
                $invoicePayment->amount      = $amount;
                $invoicePayment->date        = date('Y-m-d');
                $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
                $invoicePayment->payment_id  = $payments->id;
                $invoicePayment->description = 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id);
                $invoicePayment->account     = 0;

                \App\Models\Transaction::addTransaction($invoicePayment);

                Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                //Twilio Notification
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($invoice->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'invoice_id' => $payments->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Paypal',
                        'user_name' => $objUser->name,
                    ];

                    Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $invoice->created_by);
                }

                // webhook
                $module = 'New Payment';

                $webhook =  Utility::webhookSetting($module, $invoice->created_by);

                if ($webhook) {

                    $parameter = json_encode($invoice);

                    // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                    // if ($status == true) {
                    //     return redirect()->route('payment.index')->with('success', __('Payment successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                    // } else {
                    //     return redirect()->back()->with('error', __('Webhook call failed.'));
                    // }
                }

                if (Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } catch (\Exception $e) {
                if (Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                    return redirect()->back()->with('success', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('success', __('Transaction has been complted.'));
                }
            }
        }
    }

    public function retainerpayWithPaytr(Request $request, $retainer_id)
    {
        $retainer = Retainer::find($retainer_id);
        $customer = Customer::find($retainer->created_by);

        $companyPaymentSetting = Utility::getCompanyPaymentSetting($retainer->created_by);
        $paytr_merchant_id = $companyPaymentSetting['paytr_merchant_id'];
        $paytr_merchant_key = $companyPaymentSetting['paytr_merchant_key'];
        $paytr_merchant_salt = $companyPaymentSetting['paytr_merchant_salt'];

        $get_amount = $request->amount;
        $request->validate(['amount' => 'required|numeric|min:0']);

        try {

            $merchant_id    = $paytr_merchant_id;
            $merchant_key   = $paytr_merchant_key;
            $merchant_salt  = $paytr_merchant_salt;

            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

            $store_id = $customer->current_store;
            $setting = Utility::settingsById($retainer->created_by);

            $store = Customer::where('id', $store_id)->get()->first();
            $email = $customer->email;
            $payment_amount = $get_amount;
            $merchant_oid = $orderID;
            $user_name = $customer->name;
            $user_address = !empty($store->address) ? $store->address : 'no address';
            $user_phone = !empty($store->whatsapp_number) ? $store->whatsapp : '0000000000';


            $user_basket = base64_encode(json_encode(array(
                array("Invoice", $payment_amount, 1),
            )));

            if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }

            $user_ip = $ip;
            $timeout_limit = "30";
            $debug_on = 1;
            $test_mode = 0;
            $no_installment = 0;
            $max_installment = 0;
            $currency = $setting['site_currency'];
            $payment_amount = $payment_amount * 100;
            $hash_str = $merchant_id . $user_ip . $merchant_oid . $email . $payment_amount . $user_basket . $no_installment . $max_installment . $currency . $test_mode;
            $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant_salt, $merchant_key, true));

            $request['orderID'] = $orderID;
            $request['retainer_id'] = $retainer->id;
            $request['price'] = $get_amount;
            $request['payment_status'] = 'failed';
            $payment_failed = $request->all();
            $request['payment_status'] = 'success';
            $payment_success = $request->all();

            $post_vals = array(
                'merchant_id' => $merchant_id,
                'user_ip' => $user_ip,
                'merchant_oid' => $merchant_oid,
                'email' => $email,
                'payment_amount' => $payment_amount,
                'paytr_token' => $paytr_token,
                'user_basket' => $user_basket,
                'debug_on' => $debug_on,
                'no_installment' => $no_installment,
                'max_installment' => $max_installment,
                'user_name' => $user_name,
                'user_address' => $user_address,
                'user_phone' => $user_phone,
                'merchant_ok_url' => route('retainer.pay.paytr.success', $payment_success),
                'merchant_fail_url' => route('retainer.pay.paytr.success', $payment_failed),
                'timeout_limit' => $timeout_limit,
                'currency' => $currency,
                'test_mode' => $test_mode
            );


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);


            $result = @curl_exec($ch);


            if (curl_errno($ch)) {
                die("PAYTR IFRAME connection error. err:" . curl_error($ch));
            }

            curl_close($ch);

            $result = json_decode($result, 1);

            if ($result['status'] == 'success') {
                $token = $result['token'];
            } else {
                return redirect()->route('invoice.show')->with('error', $result['reason']);
            }
            return view('paytr_payment.index', compact('token'));
        } catch (\Throwable $th) {
            return redirect()->route('invoice.show')->with('error', $th->getMessage());
        }
    }

    public function retainerPaytrsuccess(Request $request)
    {
        if ($request->payment_status == "success") {
            $retainer = Retainer::find($request->retainer_id);
            if (Auth::check()) {
                $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
                $objUser     = Auth::user();
                $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            } else {
                $user = User::where('id', $retainer->created_by)->first();
                $settings = Utility::settingById($retainer->created_by);
                $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
                $objUser = $user;
            }
            $amount = $request->amount;
            $setting = Utility::settingsById($retainer->created_by);
            try {
                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
                $payments = RetainerPayment::create(
                    [

                        'retainer_id' => $retainer->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'account_id' => 0,
                        'payment_method' => 0,
                        'order_id' => $order_id,
                        'currency' => $setting['site_currency'],
                        'txn_id' => $order_id,
                        'payment_type' => __('PayTR'),
                        'receipt' => '',
                        'reference' => '',
                        'description' => 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
                    ]
                );

                if ($retainer->getDue() <= 0) {
                    $retainer->status = 4;
                    $retainer->save();
                } elseif (($retainer->getDue() - $payments->amount) == 0) {
                    $retainer->status = 4;
                    $retainer->save();
                } else {
                    $retainer->status = 3;
                    $retainer->save();
                }

                $retainerPayment              = new \App\Models\Transaction();
                $retainerPayment->user_id     = $retainer->customer_id;
                $retainerPayment->user_type   = 'Customer';
                $retainerPayment->type        = 'PayTR';
                $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
                $retainerPayment->payment_id  = $retainerPayment->id;
                $retainerPayment->category    = 'Retainer';
                $retainerPayment->amount      = $amount;
                $retainerPayment->date        = date('Y-m-d');
                $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
                $retainerPayment->payment_id  = $payments->id;
                $retainerPayment->description = 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                $retainerPayment->account     = 0;

                \App\Models\Transaction::addTransaction($retainerPayment);

                Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                //Twilio Notification
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($retainer->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'retainer_id' => $payments->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'PayTR',
                        'user_name' => $objUser->name,
                    ];

                    Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $retainer->created_by);
                }

                // webhook
                $module = 'New Payment';

                $webhook =  Utility::webhookSetting($module, $retainer->created_by);

                if ($webhook) {

                    $parameter = json_encode($retainer);

                    // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                    $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                    // if ($status == true) {
                    //     return redirect()->route('payment.index')->with('success', __('Payment successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                    // } else {
                    //     return redirect()->back()->with('error', __('Webhook call failed.'));
                    // }
                }

                if (Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } catch (\Exception $e) {
                if (Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                    return redirect()->back()->with('success', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('success', __('Transaction has been complted.'));
                }
            }
        }
    }
}
