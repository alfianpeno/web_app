<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\UserCoupon;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\RetainerPayment;
use App\Models\ProductCoupon;
use App\Models\Retainer;
use App\Models\Store;
use GuzzleHttp\Client;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Utility;
use App\Models\Shipping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PhpParser\Node\Stmt\TryCatch;

class BenefitPaymentController extends Controller
{

    public function invoicePayWithbenefit(Request $request, $invoice_id)
    {

        $invoice                 = Invoice::find($invoice_id);
        $customers = Customer::find($invoice->customer_id);
        $admin_payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $admin = Utility::getCompanyPaymentSetting($invoice->created_by);
        $secret_key = $admin_payment_setting['benefit_secret_key'];

        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
        }


        $get_amount = $request->amount;
        $request->validate(['amount' => 'required|numeric|min:0']);


        try {
            if ($get_amount > $invoice->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {

                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $name = Utility::invoiceNumberFormat($settings, $invoice->invoice_id);


                $customerData =
                    [
                        "amount" => $get_amount,
                        "currency" => $admin['currency'] ? $admin['currency'] : 'USD',
                        "customer_initiated" => true,
                        "threeDSecure" => true,
                        "save_card" => false,
                        "description" => $invoice['invoice_id'],
                        "metadata" => ["udf1" => "Metadata 1"],
                        "reference" => ["transaction" => "txn_01", "order" => "ord_01"],
                        "receipt" => ["email" => true, "sms" => true],
                        "customer" => ["first_name" => $customers['name'], "middle_name" => "", "last_name" => "", "email" => $customers['email'], "phone" => ["country_code" => 965, "number" => 51234567]],
                        "source" => ["id" => "src_bh.benefit"],
                        "post" => ["url" => "https://webhook.site/fd8b0712-d70a-4280-8d6f-9f14407b3bbd"],
                        "redirect" => ["url" => route('invoice.benefit', ['invoice' => $invoice_id, 'amount' => $get_amount])],

                    ];

                    $responseData = json_encode($customerData);

                    $client = new Client();

                try {
                    $response = $client->request('POST', 'https://api.tap.company/v2/charges', [
                        'body' => $responseData,
                        'headers' => [
                            'Authorization' => 'Bearer ' . $secret_key,
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                        ],
                    ]);

                } catch (\Throwable $th) {
                    return redirect()->back()->with('error', 'Currency Not Supported.Contact To Your Site Admin');
                }

                $data = $response->getBody();
                $res = json_decode($data);

                return redirect($res->transaction->url);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function benefitGetPaymentStatus(Request $request, $invoice_id, $amount)
    {
        $invoice    = Invoice::find($invoice_id);

        $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
        $admin_payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);

        $secret_key = $admin_payment_setting['benefit_secret_key'];


        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $objUser = $user;
        }
        try {

            $post = $request->all();

            $client = new Client();

            $response = $client->request('GET', 'https://api.tap.company/v2/charges/' . $post['tap_id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret_key,
                    'accept' => 'application/json',
                ],
            ]);

            $json = $response->getBody();
            $data = json_decode($json);

            $status_code = $data->gateway->response->code;

            if ($status_code == 00) {

                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
                $payments = InvoicePayment::create(
                    [
                        'invoice_id' => $invoice->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'account_id' => 0,
                        'payment_method' => 0,
                        'order_id' => $order_id,
                        'currency' => Utility::getValByName('site_currency'),
                        'txn_id' => $order_id,
                        'payment_type' => __('Benefit'),
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
                $invoicePayment->type        = 'Benefit';
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
                        'type' => 'Benefit',
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
                }
                if (Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } else {
                if (Auth::user()) {
                    // return redirect()->route('invoice.show', $invoice_id)->with('error', __('Transaction fail!'));
                    return redirect()->back()->with('error', __('Transaction fail!'));
                } else {
                    return redirect()->back()->with('error', __('Transaction fail!'));
                }
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', __($e));
        }
    }

    public function retainerpaywithbenefit(Request $request, $retainer_id)
    {

        $retainer                 = Retainer::find($retainer_id);
        $customers = Customer::find($retainer->customer_id);
        $setting = Utility::settingsById($retainer->created_by);
        $admin_payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
        $secret_key = $admin_payment_setting['benefit_secret_key'];


        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
        }


        $get_amount = $request->amount;

        $request->validate(['amount' => 'required|numeric|min:0']);

        try {
            if ($get_amount > $retainer->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {

                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                $name = Utility::invoiceNumberFormat($settings, $retainer->invoice_id);

                $customerData =
                    [
                        "amount" => $get_amount,
                        "currency" => $setting['site_currency'],
                        "customer_initiated" => true,
                        "threeDSecure" => true,
                        "save_card" => false,
                        "description" => $retainer['retainer_id'],
                        "metadata" => ["udf1" => "Metadata 1"],
                        "reference" => ["transaction" => "txn_01", "order" => "ord_01"],
                        "receipt" => ["email" => true, "sms" => true],
                        'payment_type' => __('Benefit'),
                        "customer" => ["first_name" => $customers['name'], "middle_name" => "", "last_name" => "", "email" => $customers['email'], "phone" => ["country_code" => 965, "number" => 51234567]],
                        "source" => ["id" => "src_bh.benefit"],
                        "post" => ["url" => "https://webhook.site/fd8b0712-d70a-4280-8d6f-9f14407b3bbd"],
                        "redirect" => ["url" => route('retainer.benefit', ['retainer' => $retainer_id, 'amount' => $get_amount])],

                    ];

                $responseData = json_encode($customerData);
                $client = new Client();
                try {
                    $response = $client->request('POST', 'https://api.tap.company/v2/charges', [
                        'body' => $responseData,
                        'headers' => [
                            'Authorization' => 'Bearer ' . $secret_key,
                            'accept' => 'application/json',
                            'content-type' => 'application/json',
                        ],
                    ]);
                } catch (\Throwable $th) {
                    return redirect()->back()->with('error', 'Currency Not Supported.Contact To Your Site Admin');
                }

                $data = $response->getBody();
                $res = json_decode($data);
                return redirect($res->transaction->url);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {
        $retainer    = Retainer::find($retainer_id);

        $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
        $admin_payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);

        $secret_key = $admin_payment_setting['benefit_secret_key'];
        $setting = Utility::settingsById($retainer->created_by);

        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $objUser = $user;
        }
        try {

            $post = $request->all();

            $client = new Client();

            $response = $client->request('GET', 'https://api.tap.company/v2/charges/' . $post['tap_id'], [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret_key,
                    'accept' => 'application/json',
                ],
            ]);

            $json = $response->getBody();
            $data = json_decode($json);
            $status_code = $data->gateway->response->code;

            if ($status_code == 00) {

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
                        'payment_type' => __('Benefit'),
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
                $retainerPayment->type        = 'Benefit';
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

                // Twilio Notification
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($retainer->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'retainer_id' => $payments->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Benefit',
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
                }
                if (Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } else {
                if (Auth::user()) {
                    // return redirect()->route('invoice.show', $invoice_id)->with('error', __('Transaction fail!'));
                    return redirect()->back()->with('error', __('Transaction fail!'));
                } else {
                    return redirect()->back()->with('error', __('Transaction fail!'));
                }
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', __($e));
        }
    }
}
