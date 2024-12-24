<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\Order;
use App\Models\Utility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PaytmWallet;

class PaytmPaymentController extends Controller
{
    public function paymentConfig()
    {
        // if (\Auth::user()->type == 'company') {
        // $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        // else {
        //     $payment_setting = Utility::getCompanyPaymentSetting();
        // }
        if (\Auth::user()->type == 'company') {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            config(
                [
                    'services.paytm-wallet.env' => isset($payment_setting['paytm_mode']) ? $payment_setting['paytm_mode'] : '',
                    'services.paytm-wallet.merchant_id' => isset($payment_setting['paytm_merchant_id']) ? $payment_setting['paytm_merchant_id'] : '',
                    'services.paytm-wallet.merchant_key' => isset($payment_setting['paytm_merchant_key']) ? $payment_setting['paytm_merchant_key'] : '',
                    'services.paytm-wallet.merchant_website' => 'WEBSTAGING',
                    'services.paytm-wallet.channel' => 'WEB',
                    'services.paytm-wallet.industry_type' => isset($payment_setting['paytm_industry_type']) ? $payment_setting['paytm_industry_type'] : '',
                ]
            );
            // dd($payment_setting,config());
            // dd($payment_setting['paytm_mode'],$payment_setting['paytm_merchant_id'],$payment_setting['paytm_merchant_key'],$payment_setting['paytm_industry_type']);
        }
    }

    public function retainerPayWithPaytm(Request $request)
    {

        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer   = Retainer::find($retainerID);

        if ($retainer) {

            if (Auth::check()) {
                $payment  = $this->paymentConfig();
                $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
                $settings = Utility::settingsById($retainer->created_by);
                config(
                    [
                        'services.paytm-wallet.env' => isset($payment_setting['paytm_mode']) ? $payment_setting['paytm_mode'] : '',
                        'services.paytm-wallet.merchant_id' => isset($payment_setting['paytm_merchant_id']) ? $payment_setting['paytm_merchant_id'] : '',
                        'services.paytm-wallet.merchant_key' => isset($payment_setting['paytm_merchant_key']) ? $payment_setting['paytm_merchant_key'] : '',
                        'services.paytm-wallet.merchant_website' => 'WEBSTAGING',
                        'services.paytm-wallet.channel' => 'WEB',
                        'services.paytm-wallet.industry_type' => isset($payment_setting['paytm_industry_type']) ? $payment_setting['paytm_industry_type'] : '',
                    ]
                );
            }

            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));

            $price = $request->amount;
            // $call_back = route('customer.invoice.paytm',[$request->invoice_id,$price,'&_token='.csrf_token()]);
            if ($price > 0) {
                $call_back = route('retainer.paytm', [$request->retainer_id, $price, '&_token=' . csrf_token()]);


                $payment   = PaytmWallet::with('receive');


                $payment->prepare(
                    [
                        'order' => date('Y-m-d') . '-' . strtotime(date('Y-m-d H:i:s')),
                        'user' => $retainer->customer->id,
                        'mobile_number' => $request->mobile,
                        'email' => $retainer->customer->email,
                        'amount' => $price,
                        'Retainer' => $retainer->id,
                        'callback_url' => $call_back,
                    ]
                );



                return $payment->receive();
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return Utility::error_res(__('Invoice is deleted.'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {

        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);
        $retainer   = Retainer::find($retainerID);

        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment  = $this->paymentConfig();
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
            $settings = Utility::settingsById($retainer->created_by);
            $objUser = $user;
            config(
                [
                    'services.paytm-wallet.env' => isset($payment_setting['paytm_mode']) ? $payment_setting['paytm_mode'] : '',
                    'services.paytm-wallet.merchant_id' => isset($payment_setting['paytm_merchant_id']) ? $payment_setting['paytm_merchant_id'] : '',
                    'services.paytm-wallet.merchant_key' => isset($payment_setting['paytm_merchant_key']) ? $payment_setting['paytm_merchant_key'] : '',
                    'services.paytm-wallet.merchant_website' => 'WEBSTAGING',
                    'services.paytm-wallet.channel' => 'WEB',
                    'services.paytm-wallet.industry_type' => isset($payment_setting['paytm_industry_type']) ? $payment_setting['paytm_industry_type'] : '',
                ]
            );
        }

        $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
        if ($retainer) {
            try {


                $payments = RetainerPayment::create(
                    [
                        'retainer_id' => $retainer->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'payment_method' => 1,
                        'order_id' => $orderID,
                        'payment_type' => __('Paytm'),
                        'receipt' => '',
                        'description' => __('Retainer') . ' ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
                    ]
                );

                $retainer = Retainer::find($retainer->id);

                if ($retainer->getDue() <= 0.0) {
                    Retainer::change_status($retainer->id, 4);
                } elseif ($retainer->getDue() > 0) {
                    Retainer::change_status($retainer->id, 3);
                } else {
                    Retainer::change_status($retainer->id, 2);
                }

                Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                //  Twilio Notification

                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($retainer->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'retainer_id' => $payments->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Paytm',
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
                    return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } catch (\Exception $e) {
                if (Auth::check()) {
                    return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('success', __('Transaction has been complted.'));
                }
            }
        }
    }

    public function invoicePayWithPaytm(Request $request)
    {

        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice   = Invoice::find($invoiceID);



        if ($invoice) {

            if (Auth::check()) {
                $payment  = $this->paymentConfig();
                $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
                $settings = Utility::settingsById($invoice->created_by);
                config(
                    [
                        'services.paytm-wallet.env' => isset($payment_setting['paytm_mode']) ? $payment_setting['paytm_mode'] : '',
                        'services.paytm-wallet.merchant_id' => isset($payment_setting['paytm_merchant_id']) ? $payment_setting['paytm_merchant_id'] : '',
                        'services.paytm-wallet.merchant_key' => isset($payment_setting['paytm_merchant_key']) ? $payment_setting['paytm_merchant_key'] : '',
                        'services.paytm-wallet.merchant_website' => 'WEBSTAGING',
                        'services.paytm-wallet.channel' => 'WEB',
                        'services.paytm-wallet.industry_type' => isset($payment_setting['paytm_industry_type']) ? $payment_setting['paytm_industry_type'] : '',
                    ]
                );
            }

            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));


            $price = $request->amount;
            // dd($price);
            if ($price > 0) {
                $call_back = route('customer.invoice.paytm', [$request->invoice_id, $price, '&_token=' . csrf_token()]);

                $payment   = PaytmWallet::with('receive');

                $payment->prepare(
                    [
                        'order' => date('Y-m-d') . '-' . strtotime(date('Y-m-d H:i:s')),
                        'user' => $invoice->customer->id,
                        'mobile_number' => $request->mobile,
                        'email' => $invoice->customer->email,
                        'amount' => $price,
                        'invoice' => $invoice->id,
                        'callback_url' => $call_back,
                    ]
                );


                return $payment->receive();
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return Utility::error_res(__('Invoice is deleted.'));
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $amount)
    {


        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($invoice_id);
        $invoice   = Invoice::find($invoiceID);

        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment  = $this->paymentConfig();
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
            $settings = Utility::settingsById($invoice->created_by);
            $objUser = $user;
            config(
                [
                    'services.paytm-wallet.env' => isset($payment_setting['paytm_mode']) ? $payment_setting['paytm_mode'] : '',
                    'services.paytm-wallet.merchant_id' => isset($payment_setting['paytm_merchant_id']) ? $payment_setting['paytm_merchant_id'] : '',
                    'services.paytm-wallet.merchant_key' => isset($payment_setting['paytm_merchant_key']) ? $payment_setting['paytm_merchant_key'] : '',
                    'services.paytm-wallet.merchant_website' => 'WEBSTAGING',
                    'services.paytm-wallet.channel' => 'WEB',
                    'services.paytm-wallet.industry_type' => isset($payment_setting['paytm_industry_type']) ? $payment_setting['paytm_industry_type'] : '',
                ]
            );
        }
        $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));


        if ($invoice) {
            try {

                $transaction = PaytmWallet::with('receive');


                $payments = InvoicePayment::create(
                    [
                        'invoice_id' => $invoice->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'payment_method' => 1,
                        'order_id' => $orderID,
                        'payment_type' => __('Paytm'),
                        'receipt' => '',
                        'description' => __('Invoice') . ' ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
                    ]
                );

                $invoice = Invoice::find($invoice->id);

                if ($invoice->getDue() <= 0.0) {
                    Invoice::change_status($invoice->id, 4);
                } elseif ($invoice->getDue() > 0) {
                    Invoice::change_status($invoice->id, 3);
                } else {
                    Invoice::change_status($invoice->id, 2);
                }

                Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                //  Twilio Notification

                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($invoice->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'invoice_id' => $payments->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Paytm',
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
                    return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } catch (\Exception $e) {
                // dd($e);
                if (Auth::check()) {
                    return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('success', __('Transaction has been complted.'));
                }
            }
        }
    }
}
