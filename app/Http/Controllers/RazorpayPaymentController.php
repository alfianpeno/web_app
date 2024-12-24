<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\Order;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RazorpayPaymentController extends Controller
{
    //
    public $secret_key;
    public $public_key;
    public $is_enabled;


    public function paymentConfig()
    {
        if (\Auth::user()->type == 'company') {
            $creatorId = \Auth::user()->creatorId();
            $payment_setting = Utility::getCompanyPaymentSetting($creatorId);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        }

        $this->secret_key = isset($payment_setting['razorpay_secret_key']) ? $payment_setting['razorpay_secret_key'] : '';
        $this->public_key = isset($payment_setting['razorpay_public_key']) ? $payment_setting['razorpay_public_key'] : '';
        $this->is_enabled = isset($payment_setting['is_razorpay_enabled']) ? $payment_setting['is_razorpay_enabled'] : 'off';

        return $this;
    }

    public function retainerPayWithRazorpay(Request $request)
    {

        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer   = Retainer::find($retainerID);

        $setting = Utility::settingsById($retainer->created_by);

        if ($retainer) {
            $price = $request->amount;
            if ($price > 0) {
                $res_data['email']       = $retainer->customer->email;
                $res_data['total_price'] = $price;
                $res_data['currency']    = $setting['site_currency'];
                $res_data['flag']        = 1;

                return $res_data;
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('customer.retainer')->with('error', __('Retainer is deleted.'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $pay_id)
    {
        // dd($request->all(),$pay_id, $retainer_id);
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);
        $retainer   = Retainer::find($retainerID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment   = $this->paymentConfig();
            $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
            $this->secret_key = isset($payment_setting['razorpay_secret_key']) ? $payment_setting['razorpay_secret_key'] : '';
            $this->public_key = isset($payment_setting['razorpay_public_key']) ? $payment_setting['razorpay_public_key'] : '';
            $this->is_enabled = isset($payment_setting['is_razorpay_enabled']) ? $payment_setting['is_razorpay_enabled'] : 'off';
            $settings = Utility::settingsById($retainer->created_by);
            $objUser = $user;
        }
        $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
        $result    = array();

        if ($retainer) {
            // try {
            $orderID = time();
            $ch      = curl_init('https://api.razorpay.com/v1/payments/' . $pay_id . '');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_USERPWD, $this->public_key . ':' . $this->secret_key); // Input your Razorpay Key Id and Secret Id here
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            // check that payment is authorized by razorpay or not

            // if ($response->status == 'authorized') {

            $payments = RetainerPayment::create(
                [
                    'retainer_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    // 'amount' => isset($response->amount) ? $response->amount / 100 : 0,
                    'amount' => $pay_id,
                    'payment_method' => 1,
                    'order_id' => $orderID,
                    'payment_type' => __('Razorpay'),
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

            // Twilio Notification
            $setting  = Utility::settingsById($objUser->creatorId());

            $customer = Customer::find($retainer->customer_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'invoice_id' => $payments->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $request->amount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Razorpay',
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
            // }
            // else
            // {
            //     if (Auth::check()) {
            //         return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been ' . $status));
            //     } else {
            //         return redirect()->back()->with('success', __('Transaction succesfull'));
            //     }
            // }
            // }
            // catch(\Exception $e)
            // {

            //     if (Auth::check()) {
            //         return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
            //     } else {
            //         return redirect()->back()->with('success', __('Transaction has been complted.'));
            //     }
            // }
        }
    }

    public function invoicePayWithRazorpay(Request $request)
    {

        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice   = Invoice::find($invoiceID);
        $setting = Utility::settingsById($invoice->created_by);

        if ($invoice) {
            $price = $request->amount;
            if ($price > 0) {
                $res_data['email']       = $invoice->customer->email;
                $res_data['total_price'] = $price;
                $res_data['currency']    = $setting['site_currency'];
                $res_data['flag']        = 1;

                return $res_data;
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('invoice.index')->with('error', __('Invoice is deleted.'));
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $pay_id)
    {
        // dd($request->all(),$pay_id, $invoice_id);
        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($invoice_id);
        $invoice   = Invoice::find($invoiceID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment   = $this->paymentConfig();
            $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
            $this->secret_key = isset($payment_setting['razorpay_secret_key']) ? $payment_setting['razorpay_secret_key'] : '';
            $this->public_key = isset($payment_setting['razorpay_public_key']) ? $payment_setting['razorpay_public_key'] : '';
            $this->is_enabled = isset($payment_setting['is_razorpay_enabled']) ? $payment_setting['is_razorpay_enabled'] : 'off';
            $settings = Utility::settingsById($invoice->created_by);
            $objUser = $user;
        }
        $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
        $result    = array();

        if ($invoice) {
            // try {
            $orderID = time();
            $ch      = curl_init('https://api.razorpay.com/v1/payments/' . $pay_id . '');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_USERPWD, $this->public_key . ':' . $this->secret_key); // Input your Razorpay Key Id and Secret Id here
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            // check that payment is authorized by razorpay or not

            // if ($response->status == 'authorized') {

            $payments = InvoicePayment::create(
                [
                    'invoice_id' => $invoice->id,
                    'date' => date('Y-m-d'),
                    'amount' => $pay_id,
                    'payment_method' => 1,
                    'order_id' => $orderID,
                    'payment_type' => __('Razorpay'),
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

            //Twilio Notification

            $setting  = Utility::settingsById($objUser->creatorId());
            $customer = Customer::find($invoice->customer_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'invoice_id' => $payments->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $request->amount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Razorpay',
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
            // }
            // else
            // {
            //     if (Auth::check()) {
            //         return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been ' . $status));
            //     } else {
            //         return redirect()->back()->with('success', __('Transaction succesfull'));
            //     }
            // }
            // }
            //             catch(\Exception $e)
            //             {
            // //dd($e);
            //                 if (Auth::check()) {
            //                     return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
            //                 } else {
            //                     return redirect()->back()->with('success', __('Transaction has been complted.'));
            //                 }
            //             }
        }
    }
}
