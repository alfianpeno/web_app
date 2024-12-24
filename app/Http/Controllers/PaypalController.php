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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;

use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaypalController extends Controller
{
    protected $invoiceData;

    public function paymentConfig()
    {
        if (\Auth::check()) {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting(!empty($this->invoiceData) ? $this->invoiceData->created_by : 0);
        }


        if ($payment_setting['paypal_mode'] == 'live') {
            config([
                'paypal.live.client_id' => isset($payment_setting['paypal_client_id']) ? $payment_setting['paypal_client_id'] : '',
                'paypal.live.client_secret' => isset($payment_setting['paypal_secret_key']) ? $payment_setting['paypal_secret_key'] : '',
                'paypal.mode' => isset($payment_setting['paypal_mode']) ? $payment_setting['paypal_mode'] : '',
            ]);
        } else {
            config([
                'paypal.sandbox.client_id' => isset($payment_setting['paypal_client_id']) ? $payment_setting['paypal_client_id'] : '',
                'paypal.sandbox.client_secret' => isset($payment_setting['paypal_secret_key']) ? $payment_setting['paypal_secret_key'] : '',
                'paypal.mode' => isset($payment_setting['paypal_mode']) ? $payment_setting['paypal_mode'] : '',
            ]);
        }
    }


    public function customerPayWithPaypal(Request $request, $invoice_id)
    {
        $invoice                 = Invoice::find($invoice_id);
        $this->invoiceData       = $invoice;

        $this->paymentConfig();
        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
        }


        $get_amount = $request->amount;

        $request->validate(['amount' => 'required|numeric|min:0']);

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        if ($invoice) {
            if ($get_amount > $invoice->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {


                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

                $name = Utility::invoiceNumberFormat($settings, $invoice->invoice_id);

                $setting = Utility::settingsById($invoice->created_by);

                $paypalToken = $provider->getAccessToken();
                $response = $provider->createOrder([
                    "intent" => "CAPTURE",
                    "application_context" => [
                        "return_url" => route('customer.get.payment.status', [$invoice->id, $get_amount]),
                        "cancel_url" =>  route('customer.get.payment.status', [$invoice->id, $get_amount]),
                    ],
                    "purchase_units" => [
                        0 => [
                            "amount" => [
                                "currency_code" => $setting['site_currency'],
                                "value" => $get_amount
                            ]
                        ]
                    ]
                ]);


                if (isset($response['id']) && $response['id'] != null) {
                    // redirect to approve href
                    foreach ($response['links'] as $links) {
                        if ($links['rel'] == 'approve') {
                            return redirect()->away($links['href']);
                        }
                    }
                    return redirect()
                        ->route('invoice.show', \Crypt::encrypt($invoice->id))
                        ->with('error', 'Something went wrong.');
                } else {
                    return redirect()
                        ->route('invoice.show', \Crypt::encrypt($invoice->id))
                        ->with('error', $response['message'] ?? 'Something went wrong.');
                }

                return redirect()->route('customer.invoice.show', \Crypt::encrypt($invoice_id))->back()->with('error', __('Unknown error occurred'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerretainerPayWithPaypal(Request $request, $retainer_id)
    {

        $retainer = Retainer::find($retainer_id);

        if (\Auth::check()) {
            $objUser = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($retainer->created_by);
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($retainer->created_by);
            $objUser = $user;
        }

        config(
            [
                'paypal.sandbox.client_id' => isset($payment_setting['paypal_client_id']) ? $payment_setting['paypal_client_id'] : '',
                'paypal.sandbox.client_secret' => isset($payment_setting['paypal_secret_key']) ? $payment_setting['paypal_secret_key'] : '',
                'paypal.mode' => isset($payment_setting['paypal_mode']) ? $payment_setting['paypal_mode'] : '',
            ]
        );

        // $this->paymentconfig();

        $retainer = Retainer::find($retainer_id);

        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
        }


        $get_amount = $request->amount;


        $request->validate(['amount' => 'required|numeric|min:0']);
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));

        if ($retainer) {

            if ($get_amount > $retainer->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {


                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $name = Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                $paypalToken = $provider->getAccessToken();

                $setting = Utility::settingsById($retainer->created_by);



                $response = $provider->createOrder([
                    "intent" => "CAPTURE",
                    "application_context" => [
                        "return_url" => route('customer.get.retainer.payment.status', [$retainer->id, $get_amount]),
                        "cancel_url" =>  route('customer.get.retainer.payment.status', [$retainer->id, $get_amount]),
                    ],
                    "purchase_units" => [
                        0 => [
                            "amount" => [
                                "currency_code" => $setting['site_currency'],
                                "value" => $get_amount
                            ]
                        ]
                    ]
                ]);


                if (isset($response['id']) && $response['id'] != null) {
                    // redirect to approve href
                    foreach ($response['links'] as $links) {
                        if ($links['rel'] == 'approve') {
                            return redirect()->away($links['href']);
                        }
                    }
                    return redirect()
                        ->route('retainer.show', \Crypt::encrypt($retainer->id))
                        ->with('error', 'Something went wrong.');
                } else {
                    return redirect()
                        ->route('retainer.show', \Crypt::encrypt($retainer->id))
                        ->with('error', $response['message'] ?? 'Something went wrong.');
                }


                //Twilio Notification
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($retainer->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'invoice_id' => $retainer->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $get_amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Paypal',
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


                return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer_id))->back()->with('error', __('Unknown error occurred'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function customerGetRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {
        // dd($request->all(), $retainer_id , $amount);
        $retainer = Retainer::find($retainer_id);
        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
            // $this->setApiContext();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $setting = Utility::settingById($retainer->created_by);
            $settings = Utility::settingById($retainer->created_by);



            $payment_id = Session::get('paypal_payment_id');

            Session::forget('paypal_payment_id');

            if (empty($request->PayerID || empty($request->token))) {
                return redirect()->back()->with('error', __('Payment failed'));
            }

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
                    'txn_id' => $payment_id,
                    'payment_type' => __('PAYPAL'),
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
            $retainerPayment->type        = 'PAYPAL';
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

            if (Auth::check()) {
                return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        }
    }


    public function customerGetPaymentStatus(Request $request, $invoice_id, $amount)
    {
        // dd($request->all());
        $invoice = Invoice::find($invoice_id);

        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($invoice->created_by);
            //            $this->non_auth_setApiContext($invoice->created_by);
            $objUser = $user;
        }

        config(
            [
                'paypal.sandbox.client_id' => isset($payment_setting['paypal_client_id']) ? $payment_setting['paypal_client_id'] : '',
                'paypal.sandbox.client_secret' => isset($payment_setting['paypal_secret_key']) ? $payment_setting['paypal_secret_key'] : '',
                'paypal.mode' => isset($payment_setting['paypal_mode']) ? $payment_setting['paypal_mode'] : '',
            ]
        );


        $payment_id = Session::get('PayerID');
        $provider = new PayPalClient;
        $response = $provider->showAuthorizedPaymentDetails($request->PayerID);
        $setting = Utility::settingsById($invoice->created_by);

        Session::forget('PayerID');

        if (empty($request->PayerID || empty($request->token))) {
            return redirect()->back()->with('error', __('Payment failed'));
        }

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
                    'txn_id' => $payment_id,
                    'payment_type' => __('PAYPAL'),
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
            $invoicePayment->type        = 'PAYPAL';
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
                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        } catch (\Exception $e) {
            if (Auth::check()) {
                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been complted.'));
            }
        }
    }
}
