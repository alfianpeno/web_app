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
use App\Models\Utility;
use App\Coingate\Coingate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class CoingatePaymentController extends Controller
{


    public $mode;
    public $coingate_auth_token;
    public $is_enabled;
    public $invoiceData;

    public function paymentConfig()
    {

        $payment_setting = Utility::getCompanyPaymentSetting(!empty($this->invoiceData) ? $this->invoiceData->created_by : 0);

        $this->coingate_auth_token = isset($payment_setting['coingate_auth_token']) ? $payment_setting['coingate_auth_token'] : '';
        $this->mode                = isset($payment_setting['coingate_mode']) ? $payment_setting['coingate_mode'] : 'off';
        $this->is_enabled          = isset($payment_setting['is_coingate_enabled']) ? $payment_setting['is_coingate_enabled'] : 'off';

        return $this;
    }

    public function retainerPayWithCoingate(Request $request)
    {

        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer   = Retainer::find($retainerID);
        $setting = Utility::settingsById($retainer->created_by);


        if ($retainer) {
            if (Auth::check()) {
                $payment   = $this->paymentConfig();
                $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
                $this->coingate_auth_token = isset($payment_setting['coingate_auth_token']) ? $payment_setting['coingate_auth_token'] : '';
                $this->mode                = isset($payment_setting['coingate_mode']) ? $payment_setting['coingate_mode'] : 'off';
                $this->is_enabled          = isset($payment_setting['is_coingate_enabled']) ? $payment_setting['is_coingate_enabled'] : 'off';
                $settings = Utility::settingsById($retainer->created_by);
            }
            $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
            $result    = array();
            $price = $request->amount;
            if ($price > 0) {
                Coingate::config(
                    array(
                        'environment' => $this->mode,
                        'auth_token' => $this->coingate_auth_token,
                        'curlopt_ssl_verifypeer' => FALSE,
                    )
                );
                $post_params = array(
                    'order_id' => time(),
                    'price_amount' => $price,
                    'price_currency' => $setting['site_currency'],
                    'receive_currency' => $setting['site_currency'],
                    'callback_url' => route(
                        'retainer.coingate',
                        [
                            $request->retainer_id,
                            $price,
                        ]
                    ),
                    'cancel_url' => route('customer.retainer.show', [Crypt::encrypt($request->retainer_id)]),
                    'success_url' => route(
                        'retainer.coingate',
                        [
                            $request->retainer_id,
                            $price,
                        ]
                    ),
                    'title' => __('Retainer') . ' ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
                );

                $order = Coingate::coingatePayment($post_params, 'POST');

                if($order['status_code'] === 200) {
                    $response = $order['response'];
                    return redirect($response['payment_url']);
                } else {
                    return redirect()->back()->with('error', __('opps something went wrong.'));
                }
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('customer.retainer')->with('error', __('Invoice is deleted.'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {

        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);
        $retainer   = Retainer::find($retainerID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment   = $this->paymentConfig();
            $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
            $this->coingate_auth_token = isset($payment_setting['coingate_auth_token']) ? $payment_setting['coingate_auth_token'] : '';
            $this->mode                = isset($payment_setting['coingate_mode']) ? $payment_setting['coingate_mode'] : 'off';
            $this->is_enabled          = isset($payment_setting['is_coingate_enabled']) ? $payment_setting['is_coingate_enabled'] : 'off';
            $settings = Utility::settingsById($retainer->created_by);
            $objUser = $user;
        }
        $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
        $result    = array();

        if ($retainer) {
            $payments = RetainerPayment::create(
                [
                    'retainer_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    'amount' => $amount,
                    'payment_method' => 1,
                    'order_id' => $orderID,
                    'payment_type' => __('Coingate'),
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

            //Twilio Notification
            $setting  = Utility::settingsById($objUser->creatorId());
            $customer = Customer::find($retainer->customer_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'retainer_id' => $retainer->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $amount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Coingate',
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
    }

    public function invoicePayWithCoingate(Request $request)
    {

        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice   = Invoice::find($invoiceID);
        $setting = Utility::settingsById($invoice->created_by);

        if ($invoice) {
            if (Auth::check()) {
                $payment   = $this->paymentConfig();
                $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
                $this->coingate_auth_token = isset($payment_setting['coingate_auth_token']) ? $payment_setting['coingate_auth_token'] : '';
                $this->mode                = isset($payment_setting['coingate_mode']) ? $payment_setting['coingate_mode'] : 'off';
                $this->is_enabled          = isset($payment_setting['is_coingate_enabled']) ? $payment_setting['is_coingate_enabled'] : 'off';
                $settings = Utility::settingsById($invoice->created_by);
            }
            $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
            $result    = array();
            $price = $request->amount;
            if ($price > 0) {
                CoinGate::config(
                    array(
                        'environment' => $this->mode,
                        'auth_token' => $this->coingate_auth_token,
                        'curlopt_ssl_verifypeer' => FALSE,
                    )
                );
                $post_params = array(
                    'order_id' => time(),
                    'price_amount' => $price,
                    'price_currency' => $setting['site_currency'] ?? 'USD',
                    'receive_currency' => $setting['site_currency'] ?? 'USD',
                    'callback_url' => route(
                        'customer.invoice.coingate',
                        [
                            $request->invoice_id,
                            $price,
                        ]
                    ),
                    'cancel_url' => route('invoice.show', [Crypt::encrypt($request->invoice_id)]),
                    'success_url' => route(
                        'customer.invoice.coingate',
                        [
                            $request->invoice_id,
                            $price,
                        ]
                    ),
                    'title' => __('Invoice') . ' ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
                );
                $order = Coingate::coingatePayment($post_params, 'POST');
                if($order['status_code'] === 200) {
                    $response = $order['response'];
                    return redirect($response['payment_url']);

                } else {
                    return redirect()->back()->with('error', __('opps something when wrong.'));
                }
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
            $payment   = $this->paymentConfig();
            $settings  = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
            $this->coingate_auth_token = isset($payment_setting['coingate_auth_token']) ? $payment_setting['coingate_auth_token'] : '';
            $this->mode                = isset($payment_setting['coingate_mode']) ? $payment_setting['coingate_mode'] : 'off';
            $this->is_enabled          = isset($payment_setting['is_coingate_enabled']) ? $payment_setting['is_coingate_enabled'] : 'off';
            $settings = Utility::settingsById($invoice->created_by);
            $objUser = $user;
        }
        $orderID   = strtoupper(str_replace('.', '', uniqid('', true)));
        $result    = array();

        if ($invoice) {
            $payments = InvoicePayment::create(
                [
                    'invoice_id' => $invoice->id,
                    'date' => date('Y-m-d'),
                    'amount' => $amount,
                    'account_id' => 1,
                    'payment_method' => 1,
                    'order_id' => $orderID,
                    'reference' => '',
                    'payment_type' => __('Coingate'),
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

            // Twilio Notification
            $setting  = Utility::settingsById($objUser->creatorId());

            $customer = Customer::find($invoice->customer_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'invoice_id' => $invoice->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $amount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Coingate',
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
        } else {
            if (Auth::check()) {
                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been ' . $status));
            } else {
                return redirect()->back()->with('success', __('Transaction succesfull'));
            }
        }
    }
}
