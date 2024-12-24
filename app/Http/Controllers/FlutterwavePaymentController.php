<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Retainer;
use App\Models\InvoicePayment;
use App\Models\RetainerPayment;
use App\Models\Order;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Unirest\Request\Body;

class FlutterwavePaymentController extends Controller
{
    public $secret_key;
    public $public_key;
    public $is_enabled;


    public function paymentConfig()
    {
        if (Auth::check()) {
            $user = Auth::user();
        }

        // $creatorId = \Auth::user()->creatorId();

        $payment_setting = Utility::getCompanyPaymentSetting($user);


        $this->secret_key = isset($payment_setting['flutterwave_secret_key']) ? $payment_setting['flutterwave_secret_key'] : '';
        $this->public_key = isset($payment_setting['flutterwave_public_key']) ? $payment_setting['flutterwave_public_key'] : '';
        $this->is_enabled = isset($payment_setting['is_flutterwave_enabled']) ? $payment_setting['is_flutterwave_enabled'] : 'off';

        return $this;
    }

    public function invoicePayWithFlutterwave(Request $request)
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

    public function retainerPayWithFlutterwave(Request $request)
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

    public function getRetainerPaymentStatus(Request $request,  $retainer_id, $pay_id)
    {
        // dd($request->all(),$pay_id, $retainer_id);
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);
        $retainer   = Retainer::find($retainerID);

        if (Auth::check()) {
            $payment   = $this->paymentConfig();
            $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
            $this->secret_key = isset($payment_setting['flutterwave_secret_key']) ? $payment_setting['flutterwave_secret_key'] : '';
            $this->public_key = isset($payment_setting['flutterwave_public_key']) ? $payment_setting['flutterwave_public_key'] : '';
            $this->is_enabled = isset($payment_setting['is_flutterwave_enabled']) ? $payment_setting['is_flutterwave_enabled'] : 'off';
            $settings = Utility::settingsById($retainer->created_by);
            $objUser = $user;
        }

        $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
        $result    = array();

        if ($retainer) {
            // try {

            $data = array(
                'txref' => $pay_id,
                'SECKEY' => $this->secret_key,
                //secret key from pay button generated on rave dashboard
            );
            // make request to endpoint using unirest.
            $headers = array('Content-Type' => 'application/json');
            $body    = \Unirest\Request\Body::json($data);
            $url     = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify"; //please make sure to change this to production url when you go live

            // Make `POST` request and handle response with unirest
            $response = \Unirest\Request::post($url, $headers, $body);
            if (!empty($response)) {
                $response = json_decode($response->raw_body, true);
            }
            // if (isset($response['status']) && $response['status'] == 'success') {
            $paydata = $response['data'];

            $payments = RetainerPayment::create(
                [
                    'retainer_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    'amount' => $request->amount,
                    'payment_method' => 1,
                    'order_id' => $orderID,
                    'payment_type' => __('Flutterwave'),
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
                    'retainer_id' => $payments->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $request->amount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Flutterwave',
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

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $pay_id)
    {
        //   dd($request->all(),$pay_id, $invoice_id);

        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($invoice_id);
        $invoice   = Invoice::find($invoiceID);
        // $payment   = $this->paymentConfig();

        if (Auth::check()) {
            $payment   = $this->paymentConfig();
            $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
            $this->secret_key = isset($payment_setting['flutterwave_secret_key']) ? $payment_setting['flutterwave_secret_key'] : '';
            $this->public_key = isset($payment_setting['flutterwave_public_key']) ? $payment_setting['flutterwave_public_key'] : '';
            $this->is_enabled = isset($payment_setting['is_flutterwave_enabled']) ? $payment_setting['is_flutterwave_enabled'] : 'off';
            $settings = Utility::settingsById($invoice->created_by);
            $objUser = $user;
        }

        $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
        $result    = array();

        if ($invoice) {
            // try {


            $data = array(
                'txref' => $pay_id,
                'SECKEY' => $this->secret_key,
                //secret key from pay button generated on rave dashboard
            );

            // dd($data);
            // make request to endpoint using unirest.
            $headers = array('Content-Type' => 'application/json');
            $body    = \Unirest\Request\Body::json($data);
            $url     = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify"; //please make sure to change this to production url when you go live

            // Make `POST` request and handle response with unirest
            $response = \Unirest\Request::post($url, $headers, $body);

            if (!empty($response)) {
                $response = json_decode($response->raw_body, true);
            }


            // dd($response);
            // if (isset($response['status']) && $response['status'] == 'success') {
            $paydata = $response['data'];

            $payments = InvoicePayment::create(
                [
                    'invoice_id' => $invoice->id,
                    'date' => date('Y-m-d'),
                    'amount' => $request->amount,
                    'payment_method' => 1,
                    'order_id' => $orderID,
                    'payment_type' => __('Flutterwave'),
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
                    'type' => 'Flutterwave',
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
            // catch(\Exception $e)
            // {
            //     if (Auth::check()) {
            //         return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
            //     } else {
            //         return redirect()->back()->with('success', __('Transaction has been complted.'));
            //     }
            // }
        }
    }
}
