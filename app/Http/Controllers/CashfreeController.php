<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Utility;
use App\Models\UserCoupon;
use App\Models\InvoicePayment;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\RetainerPayment;
use App\Models\User;
use App\Models\Order;
use App\Models\Retainer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CashfreeController extends Controller
{
    public function paymentConfig()
    {
        if (\Auth::check()) {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            config(
                [
                    'services.cashfree.key' => isset($payment_setting['cashfree_api_key']) ? $payment_setting['cashfree_api_key'] : '',
                    'services.cashfree.secret' => isset($payment_setting['cashfree_secret_key']) ? $payment_setting['cashfree_secret_key'] : '',
                ]
            );
        }
    }

    public function invoicepayWithCashfree(Request $request, $invoice_id)
    {
        try {
            $invoice = Invoice::find($invoice_id);
            $customers = Customer::find($invoice->customer_id);
            $comapnysetting = Utility::getCompanyPaymentSetting($invoice->created_by);
            config(
                [
                    'services.cashfree.key' => isset($comapnysetting['cashfree_api_key']) ? $comapnysetting['cashfree_api_key'] : '',
                    'services.cashfree.secret' => isset($comapnysetting['cashfree_secret_key']) ? $comapnysetting['cashfree_secret_key'] : '',
                ]
            );
            $get_amount = $request->amount;
            $setting = Utility::settingsById($invoice->created_by);
            $request->validate(['amount' => 'required|numeric|min:0']);

            $url = config('services.cashfree.url');

            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

            $headers = array(
                "Content-Type: application/json",
                "x-api-version: 2022-01-01",
                "x-client-id: " . config('services.cashfree.key'),
                "x-client-secret: " . config('services.cashfree.secret')
            );

            $data = json_encode([
                'order_id' => $order_id,
                'order_amount' => $get_amount,
                "order_currency" => $setting['site_currency'],
                "order_name" => $customers['name'],
                "customer_details" => [
                    "customer_id" => 'customer_' . $customers['id'],
                    "customer_name" => $customers['name'],
                    "customer_email" => $customers['email'],
                    "customer_phone" => '1234567890',
                ],

                "order_meta" => [
                    "return_url" => route('invoice.cashfreePayment.success') . '?order_id={order_id}&invoice=' . $invoice_id . '&amount=' . $get_amount
                ]
            ]);

            try {

                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                $resp = curl_exec($curl);
                curl_close($curl);
                return redirect()->to(json_decode($resp)->payment_link);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', 'Currency Not Supported.Contact To Your Site Admin');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceCashfreePaymentSuccess(Request $request)
    {
        $invoice = Invoice::find($request->invoice);

        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
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
        $getAmount = $request->amount;

        $comapnysetting = Utility::getCompanyPaymentSetting($invoice->created_by);
        config(
            [
                'services.cashfree.key' => isset($comapnysetting['cashfree_api_key']) ? $comapnysetting['cashfree_api_key'] : '',
                'services.cashfree.secret' => isset($comapnysetting['cashfree_secret_key']) ? $comapnysetting['cashfree_secret_key'] : '',
            ]
        );
        try {

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', config('services.cashfree.url') . '/' . $request->get('order_id') . '/settlements', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-version' => '2022-09-01',
                    "x-client-id" => config('services.cashfree.key'),
                    "x-client-secret" => config('services.cashfree.secret')
                ],
            ]);
            $respons = json_decode($response->getBody());
            if ($respons->order_id && $respons->cf_payment_id != NULL) {

                $response = $client->request('GET', config('services.cashfree.url') . '/' . $respons->order_id . '/payments/' . $respons->cf_payment_id . '', [
                    'headers' => [
                        'accept' => 'application/json',
                        'x-api-version' => '2022-09-01',
                        'x-client-id' => config('services.cashfree.key'),
                        'x-client-secret' => config('services.cashfree.secret'),
                    ],
                ]);
                $info = json_decode($response->getBody());
                $setting = Utility::settingsById($invoice->created_by);
                if ($info->payment_status == "SUCCESS") {

                    $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                    $payments = InvoicePayment::create(
                        [

                            'invoice_id' => $invoice->id,
                            'date' => date('Y-m-d'),
                            'amount' => $getAmount,
                            'account_id' => 0,
                            'payment_method' => 0,
                            'order_id' => $order_id,
                            'currency' => $setting['site_currency'],
                            'txn_id' => '',
                            'payment_type' => __('Cashfree'),
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
                    $invoicePayment->type        = 'Cashfree';
                    $invoicePayment->created_by  = Auth::check() ? Auth::user()->id : $invoice->customer_id;
                    $invoicePayment->payment_id  = $invoicePayment->id;
                    $invoicePayment->category    = 'Invoice';
                    $invoicePayment->amount      = $getAmount;
                    $invoicePayment->date        = date('Y-m-d');
                    $invoicePayment->created_by  = Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
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
                            'payment_amount' => $getAmount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'Cashfree',
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
                    return redirect()->back()->with('success', __('Transaction has been success'));
                } else {
                    return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
                }
            } else {
                return redirect()->route('invoice.show')->with('error', 'Payment Failed.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __($e));
        }
    }

    public function retainerpayWithCashfree(Request $request, $retainer_id)
    {
        try {
            $retainer = Retainer::find($retainer_id);
            $customers = Customer::find($retainer->customer_id);
            $comapnysetting = Utility::getCompanyPaymentSetting($retainer->created_by);
            config(
                [
                    'services.cashfree.key' => isset($comapnysetting['cashfree_api_key']) ? $comapnysetting['cashfree_api_key'] : '',
                    'services.cashfree.secret' => isset($comapnysetting['cashfree_secret_key']) ? $comapnysetting['cashfree_secret_key'] : '',
                ]
            );
            $get_amount = $request->amount;

            $request->validate(['amount' => 'required|numeric|min:0']);

            $url = config('services.cashfree.url');

            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $setting = Utility::settingsById($retainer->created_by);

            $headers = array(
                "Content-Type: application/json",
                "x-api-version: 2022-01-01",
                "x-client-id: " . config('services.cashfree.key'),
                "x-client-secret: " . config('services.cashfree.secret')
            );

            $data = json_encode([
                'order_id' => $order_id,
                'order_amount' => $get_amount,
                "order_currency" => $setting['site_currency'],
                "order_name" => $customers['name'],
                "customer_details" => [
                    "customer_id" => 'customer_' . $customers['id'],
                    "customer_name" => $customers['name'],
                    "customer_email" => $customers['email'],
                    "customer_phone" => '1234567890',
                ],

                "order_meta" => [
                    "return_url" => route('retainer.cashfreePayment.success') . '?order_id={order_id}&retainer=' . $retainer_id . '&amount=' . $get_amount
                ]
            ]);

            try {

                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

                $resp = curl_exec($curl);
                curl_close($curl);
                return redirect()->to(json_decode($resp)->payment_link);
            } catch (\Throwable $th) {
                return redirect()->back()->with('error', 'Currency Not Supported.Contact To Your Site Admin');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerCashfreePaymentSuccess(Request $request)
    {
        $retainer = Retainer::find($request->retainer);

        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            //            $this->non_auth_setApiContext($invoice->created_by);
            $objUser = $user;
        }
        $getAmount = $request->amount;
        $setting = Utility::settingsById($retainer->created_by);
        $comapnysetting = Utility::getCompanyPaymentSetting($retainer->created_by);
        config(
            [
                'services.cashfree.key' => isset($comapnysetting['cashfree_api_key']) ? $comapnysetting['cashfree_api_key'] : '',
                'services.cashfree.secret' => isset($comapnysetting['cashfree_secret_key']) ? $comapnysetting['cashfree_secret_key'] : '',
            ]
        );
        try {

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', config('services.cashfree.url') . '/' . $request->get('order_id') . '/settlements', [
                'headers' => [
                    'accept' => 'application/json',
                    'x-api-version' => '2022-09-01',
                    "x-client-id" => config('services.cashfree.key'),
                    "x-client-secret" => config('services.cashfree.secret')
                ],
            ]);
            $respons = json_decode($response->getBody());
            if ($respons->order_id && $respons->cf_payment_id != NULL) {

                $response = $client->request('GET', config('services.cashfree.url') . '/' . $respons->order_id . '/payments/' . $respons->cf_payment_id . '', [
                    'headers' => [
                        'accept' => 'application/json',
                        'x-api-version' => '2022-09-01',
                        'x-client-id' => config('services.cashfree.key'),
                        'x-client-secret' => config('services.cashfree.secret'),
                    ],
                ]);
                $info = json_decode($response->getBody());

                if ($info->payment_status == "SUCCESS") {

                    $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                    $payments = RetainerPayment::create(
                        [

                            'retainer_id' => $retainer->id,
                            'date' => date('Y-m-d'),
                            'amount' => $getAmount,
                            'account_id' => 0,
                            'payment_method' => 0,
                            'order_id' => $order_id,
                            'currency' => $setting['site_currency'],
                            'txn_id' => $getAmount,
                            'payment_type' => __('Cashfree'),
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
                    $retainerPayment->type        = 'Cashfree';
                    $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
                    $retainerPayment->payment_id  = $retainerPayment->id;
                    $retainerPayment->category    = 'Retainer';
                    $retainerPayment->amount      = $getAmount;
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
                            'payment_amount' => $getAmount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'Cashfree',
                            'user_name' => $objUser->name,
                        ];

                        Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $retainer->created_by);
                    }

                    // webhook\
                    $module = 'New Payment';

                    $webhook =  Utility::webhookSetting($module, $retainer->created_by);

                    if ($webhook) {

                        $parameter = json_encode($retainer);

                        // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                        $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                    }
                    return redirect()->back()->with('success', __('Transaction has been success'));
                } else {
                    return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
                }
            } else {
                return redirect()->route('invoice.show')->with('error', 'Payment Failed.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __($e));
        }
    }
}
