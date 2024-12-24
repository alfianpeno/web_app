<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\Order;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Obydul\LaraSkrill\SkrillClient;
use Obydul\LaraSkrill\SkrillRequest;

class SkrillPaymentController extends Controller
{
    public $email;
    public $is_enabled;

    public function paymentConfig()
    {
        if (Auth::check()) {
            $user = Auth::user();
        }
        if (\Auth::user()->type == 'company') {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting($user);
        }


        $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
        $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';

        return $this;
    }

    public function retainerPayWithSkrill(Request $request)
    {
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);

        $retainer   = Retainer::find($retainerID);
        $setting = Utility::settingsById($retainer->created_by);
        if ($retainer) {


            if (Auth::check()) {
                $payment  = $this->paymentConfig();
                $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
                $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
                $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
                $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
                $settings = Utility::settingsById($retainer->created_by);
            }



            $result    = array();

            $price = $request->amount;


            if ($price > 0) {

                $tran_id             = md5(date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id');


                $skill               = new SkrillRequest();

                $skill->pay_to_email = $this->email;

                $skill->return_url   = route(
                    'retainer.skrill',
                    [
                        $request->retainer_id,
                        $price,
                        'tansaction_id=' . MD5($tran_id),
                    ]
                );


                $skill->cancel_url   = route(
                    'retainer.skrill',
                    [
                        $request->retainer_id,
                        $price,
                    ]
                );

                // create object instance of SkrillRequest
                $skill->transaction_id  = MD5($tran_id); // generate transaction id
                $skill->amount          = $price;
                $skill->currency        = $setting['site_currency'];
                $skill->language        = 'EN';
                $skill->prepare_only    = '1';
                $skill->merchant_fields = 'site_name, customer_email';
                $skill->site_name       = $retainer->customer->name;
                $skill->customer_email  =  $retainer->customer->email;


                // create object instance of SkrillClient
                $client = new SkrillClient($skill);

                $sid    = $client->generateSID(); //return SESSION ID

                // handle error
                $jsonSID = json_decode($sid);

                if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST") {
                    return redirect()->back()->with('error', $jsonSID->message);
                }


                // do the payment
                $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url

                if ($tran_id) {
                    $data = [
                        'amount' => $price,
                        'trans_id' => MD5($request['transaction_id']),
                        'currency' => $setting['site_currency'],
                    ];
                    session()->put('skrill_data', $data);
                }

                return redirect($redirectUrl);
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('customer.retainer')->with('error', __('Retainer is deleted.'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);
        $retainer   = Retainer::find($retainerID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment  = $this->paymentConfig();
            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
            $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
            $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
            $settings = Utility::settingsById($retainer->created_by);
            $objUser = $user;
        }



        $result    = array();

        if ($retainer) {
            try {

                if (session()->has('skrill_data')) {
                    $get_data = session()->get('skrill_data');


                    $payments = RetainerPayment::create(
                        [
                            'retainer' => $retainer->id,
                            'date' => date('Y-m-d'),
                            'amount' => $amount,
                            'payment_method' => 1,
                            'transaction' => $orderID,
                            'payment_type' => __('Skrill'),
                            'receipt' => '',
                            'notes' => __('Retainer') . ' ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
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

                    //Twilio Notification
                    $setting  = Utility::settingsById($objUser->creatorId());

                    $customer = Customer::find($retainer->customer_id);
                    if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                        $uArr = [
                            'invoice_id' => $payments->id,
                            'payment_name' => $customer->name,
                            'payment_amount' => $request->amount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'Skrill',
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
                        return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
                    } else {
                        return redirect()->back()->with('success', __(' Payment successfully added.'));
                    }
                } else {
                    if (Auth::check()) {
                        return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been ' . $status));
                    } else {
                        return redirect()->back()->with('success', __('Transaction succesfull'));
                    }
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Retainer not found!'));
            }
        }
    }

    public function invoicePayWithSkrill(Request $request)
    {
        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice   = Invoice::find($invoiceID);
        $setting = Utility::settingsById($invoice->created_by);

        if ($invoice) {

            if (Auth::check()) {
                $payment  = $this->paymentConfig();
                $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
                $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
                $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
                $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
                $settings = Utility::settingsById($invoice->created_by);
            }



            $result    = array();

            $price = $request->amount;
            if ($price > 0) {

                $tran_id             = md5(date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id');
                $skill               = new SkrillRequest();
                $skill->pay_to_email = $this->email;
                $skill->return_url   = route(
                    'customer.invoice.skrill',
                    [
                        $request->invoice_id,
                        $price,
                        'tansaction_id=' . MD5($tran_id),
                    ]
                );
                $skill->cancel_url   = route(
                    'customer.invoice.skrill',
                    [
                        $request->invoice_id,
                        $price,
                    ]
                );

                // create object instance of SkrillRequest
                $skill->transaction_id  = MD5($tran_id); // generate transaction id
                $skill->amount          = $price;
                $skill->currency        = $setting['site_currency'];
                $skill->language        = 'EN';
                $skill->prepare_only    = '1';
                $skill->merchant_fields = 'site_name, customer_email';
                $skill->site_name       = $invoice->customer->name;
                $skill->customer_email  =  $invoice->customer->email;

                // create object instance of SkrillClient
                $client = new SkrillClient($skill);
                $sid    = $client->generateSID(); //return SESSION ID

                // handle error
                $jsonSID = json_decode($sid);

                if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST") {
                    return redirect()->back()->with('error', $jsonSID->message);
                }


                // do the payment
                $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url
                if ($tran_id) {
                $data = [
                        'amount' => $price,
                        'trans_id' => MD5($request['transaction_id']),
                        'currency' => $setting['site_currency'],
                    ];
                    session()->put('skrill_data', $data);
                }

                return redirect($redirectUrl);
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('invoice.index')->with('error', __('Invoice is deleted.'));
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $amount)
    {

        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($invoice_id);
        $invoice   = Invoice::find($invoiceID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment  = $this->paymentConfig();
            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
            $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
            $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
            $settings = Utility::settingsById($invoice->created_by);
            $objUser = $user;
        }



        $result    = array();

        if ($invoice) {
            try {

                if (session()->has('skrill_data')) {
                    $get_data = session()->get('skrill_data');


                    $payments = InvoicePayment::create(
                        [
                            'invoice' => $invoice->id,
                            'date' => date('Y-m-d'),
                            'amount' => $amount,
                            'payment_method' => 1,
                            'transaction' => $orderID,
                            'payment_type' => __('Skrill'),
                            'receipt' => '',
                            'notes' => __('Invoice') . ' ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
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
                            'payment_amount' => $amount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'Skrill',
                            'user_name' => $objUser->name,
                        ];

                        Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $invoice->created_by);
                    }

                    Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                    Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');


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
                } else {
                    if (Auth::check()) {
                        return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been ' . $status));
                    } else {
                        return redirect()->back()->with('success', __('Transaction succesfull'));
                    }
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Invoice not found!'));
            }
        }
    }
}
