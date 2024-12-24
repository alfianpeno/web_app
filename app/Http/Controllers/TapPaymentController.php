<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use App\Models\Utility;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Support\Facades\Auth;
use App\Package\Payment;

class TapPaymentController extends Controller
{
    public $secret_key;
    public $is_enabled;


    public function paymentConfig()
    {
        if (\Auth::user()->type == 'company') {
            $creatorId = \Auth::user()->creatorId();
            $payment_setting = Utility::getCompanyPaymentSetting($creatorId);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        }

        $this->secret_key = isset($payment_setting['company_tap_secret_key']) ? $payment_setting['company_tap_secret_key'] : '';
        $this->is_enabled = isset($payment_setting['is_tap_enabled']) ? $payment_setting['is_tap_enabled'] : 'off';

        return $this;
    }

    public function invoicePayWithTap(Request $request)
    {

        $invoice_id = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice = Invoice::find($invoice_id);
        $user = User::find($invoice->created_by);

        $company_payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $company_tap_secret_key = isset($company_payment_setting['company_tap_secret_key']) ? $company_payment_setting['company_tap_secret_key'] : '';
        $currency = isset($company_payment_setting['site_currency']) ? $company_payment_setting['site_currency'] : 'USD';
        $settings = Utility::settingsById($invoice->created_by);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $get_amount = $request->amount;
        $payment_id = $invoice->id;

        try {
            if ($invoice) {
                $TapPay = new Payment(['company_tap_secret_key'=> $company_tap_secret_key]);

                return $TapPay->charge([
                    'amount' => $get_amount,
                    'currency' => $currency,
                    'threeDSecure' => 'true',
                    'description' => 'test description',
                    'statement_descriptor' => 'sample',
                    'customer' => [
                       'first_name' => $user->name,
                       'email' => $user->email,
                    ],
                    'source' => [
                      'id' => 'src_card'
                    ],
                    'post' => [
                       'url' => null
                    ],
                    // 'merchant' => [
                    //    'id' => 'YOUR-MERCHANT-ID'  //Include this when you are going to live
                    // ],
                    'redirect' => [
                       'url' => route('invoice.tap.status', [
                        'invoice_id' => $invoice->id,
                        'amount' => $get_amount]
                        )
                    ]
                ],true);
            } else {
                return redirect()->back()->with('error', 'Invoice not found.');
            }
        } catch (\Throwable $e) {

            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceGetTapStatus(Request $request)
    {
        $invoice = Invoice::find($request->invoice_id);
        $user = User::find($invoice->created_by);
        $amount = $request->amount;

        $settings= Utility::settingsById($invoice->created_by);
        $company_payment_setting = Utility::getCompanyPaymentSetting($user->id);
        if ($invoice)
        {
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            try
            {
                    $invoice_payment                 = new InvoicePayment();
                    $invoice_payment->invoice_id     = $request->invoice_id;
                    $invoice_payment->date           = Date('Y-m-d');
                    $invoice_payment->amount         = $amount;
                    $invoice_payment->account_id         = 0;
                    $invoice_payment->payment_method         = 0;
                    $invoice_payment->order_id      =$orderID;
                    $invoice_payment->payment_type   = 'Tap';
                    $invoice_payment->receipt     = '';
                    $invoice_payment->reference     = '';
                    $invoice_payment->description     = 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id);
                    $invoice_payment->save();

                    if($invoice->getDue() <= 0)
                    {
                        $invoice->status = 4;
                        $invoice->save();
                    }
                    elseif(($invoice->getDue() - $invoice_payment->amount) == 0)
                    {
                        $invoice->status = 4;
                        $invoice->save();
                    }
                    else
                    {
                        $invoice->status = 3;
                        $invoice->save();
                    }
                    //for customer balance update
                    Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                    // //For Notification
                    // $setting  = Utility::settingsById($invoice->created_by);
                    // $customer = Customer::find($invoice->customer_id);
                    // $notificationArr = [
                    //         'payment_price' => $request->amount,
                    //         'invoice_payment_type' => 'Aamarpay',
                    //         'customer_name' => $customer->name,
                    //     ];
                    // //Slack Notification
                    // if(isset($settings['payment_notification']) && $settings['payment_notification'] ==1)
                    // {
                    //     Utility::send_slack_msg('new_invoice_payment', $notificationArr,$invoice->created_by);
                    // }
                    // //Telegram Notification
                    // if(isset($settings['telegram_payment_notification']) && $settings['telegram_payment_notification'] == 1)
                    // {
                    //     Utility::send_telegram_msg('new_invoice_payment', $notificationArr,$invoice->created_by);
                    // }
                    // //Twilio Notification
                    // if(isset($settings['twilio_payment_notification']) && $settings['twilio_payment_notification'] ==1)
                    // {
                    //     Utility::send_twilio_msg($customer->contact,'new_invoice_payment', $notificationArr,$invoice->created_by);
                    // }
                    // //webhook
                    // $module ='New Invoice Payment';
                    // $webhook=  Utility::webhookSetting($module,$invoice->created_by);
                    // if($webhook)
                    // {
                    //     $parameter = json_encode($invoice_payment);
                    //     $status = Utility::WebhookCall($webhook['url'],$parameter,$webhook['method']);
                    //     if($status == true)
                    //     {
                    //         return redirect()->route('invoice.link.copy', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                    //     }
                    //     else
                    //     {
                    //         return redirect()->back()->with('error', __('Webhook call failed.'));
                    //     }
                    // }
                    return redirect()->route('pay.invoice', \Crypt::encrypt($request->invoice_id))->with('success', __('Invoice paid Successfully!'));
            }
            catch (\Exception $e)
            {
                return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($request->invoice_id))->with('success',$e->getMessage());
            }
        } else {
            return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($request->invoice_id))->with('success', __('Invoice not found.'));
        }

    }

    public function retainerPayWithTap(Request $request)
    {

        $retainer_id = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer = Retainer::find($retainer_id);
        $user = User::find($retainer->created_by);

        $company_payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $company_tap_secret_key = isset($company_payment_setting['company_tap_secret_key']) ? $company_payment_setting['company_tap_secret_key'] : '';
        $currency = isset($company_payment_setting['site_currency']) ? $company_payment_setting['site_currency'] : 'USD';
        $settings = Utility::settingsById($retainer->created_by);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $get_amount = $request->amount;
        $payment_id = $retainer->id;

        try {
            if ($retainer) {
                $TapPay = new Payment(['company_tap_secret_key'=> $company_tap_secret_key]);

                return $TapPay->charge([
                    'amount' => $get_amount,
                    'currency' => $currency,
                    'threeDSecure' => 'true',
                    'description' => 'test description',
                    'statement_descriptor' => 'sample',
                    'customer' => [
                       'first_name' => $user->name,
                       'email' => $user->email,
                    ],
                    'source' => [
                      'id' => 'src_card'
                    ],
                    'post' => [
                       'url' => null
                    ],
                    // 'merchant' => [
                    //    'id' => 'YOUR-MERCHANT-ID'  //Include this when you are going to live
                    // ],
                    'redirect' => [
                       'url' => route('retainer.tap.status', [
                        'retainer_id' => $retainer->id,
                        'amount' => $get_amount]
                        )
                    ]
                ],true);
            } else {
                return redirect()->back()->with('error', 'Retainer not found.');
            }
        } catch (\Throwable $e) {

            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerGetTapStatus(Request $request)
    {
        $retainer = Retainer::find($request->retainer_id);
        $user = User::find($retainer->created_by);
        $amount = $request->amount;

        $settings= Utility::settingsById($retainer->created_by);
        $company_payment_setting = Utility::getCompanyPaymentSetting($user->id);
        if ($retainer)
        {
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            try
            {
                    $retainer_payment                 = new RetainerPayment();
                    $retainer_payment->retainer_id     = $request->retainer_id;
                    $retainer_payment->date           = Date('Y-m-d');
                    $retainer_payment->amount         = $amount;
                    $retainer_payment->account_id         = 0;
                    $retainer_payment->payment_method         = 0;
                    $retainer_payment->order_id      =$orderID;
                    $retainer_payment->payment_type   = 'Tap';
                    $retainer_payment->receipt     = '';
                    $retainer_payment->reference     = '';
                    $retainer_payment->description     = 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                    $retainer_payment->save();

                    if($retainer->getDue() <= 0)
                    {
                        $retainer->status = 4;
                        $retainer->save();
                    }
                    elseif(($retainer->getDue() - $retainer_payment->amount) == 0)
                    {
                        $retainer->status = 4;
                        $retainer->save();
                    }
                    else
                    {
                        $retainer->status = 3;
                        $retainer->save();
                    }
                    //for customer balance update
                    Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

                    // //For Notification
                    // $setting  = Utility::settingsById($retainer->created_by);
                    // $customer = Customer::find($retainer->customer_id);
                    // $notificationArr = [
                    //         'payment_price' => $request->amount,
                    //         'retainer_payment_type' => 'Aamarpay',
                    //         'customer_name' => $customer->name,
                    //     ];
                    // //Slack Notification
                    // if(isset($settings['payment_notification']) && $settings['payment_notification'] ==1)
                    // {
                    //     Utility::send_slack_msg('new_retainer_payment', $notificationArr,$retainer->created_by);
                    // }
                    // //Telegram Notification
                    // if(isset($settings['telegram_payment_notification']) && $settings['telegram_payment_notification'] == 1)
                    // {
                    //     Utility::send_telegram_msg('new_retainer_payment', $notificationArr,$retainer->created_by);
                    // }
                    // //Twilio Notification
                    // if(isset($settings['twilio_payment_notification']) && $settings['twilio_payment_notification'] ==1)
                    // {
                    //     Utility::send_twilio_msg($customer->contact,'new_retainer_payment', $notificationArr,$retainer->created_by);
                    // }
                    // //webhook
                    // $module ='New retainer Payment';
                    // $webhook=  Utility::webhookSetting($module,$retainer->created_by);
                    // if($webhook)
                    // {
                    //     $parameter = json_encode($retainer_payment);
                    //     $status = Utility::WebhookCall($webhook['url'],$parameter,$webhook['method']);
                    //     if($status == true)
                    //     {
                    //         return redirect()->route('retainer.link.copy', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
                    //     }
                    //     else
                    //     {
                    //         return redirect()->back()->with('error', __('Webhook call failed.'));
                    //     }
                    // }
                    return redirect()->route('pay.retainerpay', \Crypt::encrypt($request->retainer_id))->with('success', __('Retainer paid Successfully!'));
            }
            catch (\Exception $e)
            {
                return redirect()->route('pay.retainerpay', \Illuminate\Support\Facades\Crypt::encrypt($request->retainer_id))->with('success',$e->getMessage());
            }
        } else {
            return redirect()->route('pay.retainerpay', \Illuminate\Support\Facades\Crypt::encrypt($request->retainer_id))->with('success', __('Retainer not found.'));
        }

    }

}
