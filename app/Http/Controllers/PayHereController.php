<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserCoupon;
use App\Models\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lahirulhr\PayHere\PayHere;

class PayHereController extends Controller
{
    public $paymentSetting;
    public function __construct()
    {
        $paymentSetting = Utility::getCompanyPaymentSetting();
        $config = [
            'payhere.api_endpoint' => isset($paymentSetting['payhere_mode']) && $paymentSetting['payhere_mode'] === 'sandbox'
                ? 'https://sandbox.payhere.lk/'
                : 'https://www.payhere.lk/',
        ];

        $config['payhere.merchant_id']      = $paymentSetting['payhere_merchant_id'] ?? '';
        $config['payhere.merchant_secret']  = $paymentSetting['payhere_merchant_secret'] ?? '';
        $config['payhere.app_secret']       = $paymentSetting['payhere_app_secret'] ?? '';
        $config['payhere.app_id']           = $paymentSetting['payhere_app_id'] ?? '';

        config($config);

        $this->paymentSetting = $paymentSetting;
    }

    public function invoicePayWithPayHere(Request $request, $invoice_id)
    {
        try {
            $invoice            = Invoice::find($invoice_id);
            $customer           = Customer::find($invoice->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $setting            = Utility::settingsById($invoice->created_by);
            $currency           = isset($setting['currency']) ? $setting['currency'] : '';
            $api_key            = isset($payment_setting['payhere_merchant_id']) ? $payment_setting['payhere_merchant_id'] : '';
            $get_amount         = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));

            $request->validate(['amount' => 'required|numeric|min:0']);

            $hash = strtoupper(
                md5(
                    $api_key .
                        $order_id .
                        number_format($get_amount, 2, '.', '') .
                        $currency .
                        strtoupper(md5(config('payhere.merchant_secret')))
                )
            );

            $data = [
                'first_name'    => $customer->name,
                'last_name'     => '',
                'email'         => $customer->email,
                'address'       => '',
                'city'          => '',
                'country'       => '',
                'order_id'      => $order_id,
                'items'         => 'Invoice Payment',
                'currency'      => $currency,
                'amount'        => $get_amount,
                'hash'          => $hash,
            ];

            return PayHere::checkOut()
                ->data($data)
                ->successUrl(route('invoice.payhere.status', ['success' => 1, 'id' => $invoice_id, 'amount' => $get_amount]))
                ->failUrl(route('invoice.payhere.status', ['success' => 0, 'id' => $invoice_id]))
                ->renderView();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function invoiceGetPayHereStatus(Request $request)
    {
        if ($request->success == 1) {
            $info = PayHere::retrieve()
                ->orderId($request->order_id)
                ->submit();

            if ($info['data'][0]['order_id'] == $request->order_id) {
                if ($info['data'][0]['status'] == "RECEIVED") {
                    $invoice = Invoice::find($request->invoice_id);

                    $payment = new InvoicePayment();
                    $payment->invoice_id    = $invoice->id;
                    $payment->date          = date('Y-m-d');
                    $payment->amount        = $request->amount;
                    $payment->account_id    = 0;
                    $payment->payment_method = 0;
                    $payment->order_id      = $request->order_id;
                    $payment->currency      = $invoice->currency;
                    $payment->txn_id        = '';
                    $payment->payment_type  = __('PayHere');
                    $payment->receipt       = '';
                    $payment->reference     = '';
                    $payment->description   = 'Invoice Payment ' . Utility::invoiceNumberFormat($invoice->created_by, $invoice->invoice_id);
                    $payment->save();

                    if ($invoice->getDue() <= 0) {
                        $invoice->status = 4;
                    } elseif (($invoice->getDue() - $payment->amount) == 0) {
                        $invoice->status = 4;
                    } else {
                        $invoice->status = 3;
                    }
                    $invoice->save();
                    return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                }
            }
        } else {
            return redirect()->route('invoices.index')->with('error', __('Invoice payment failed.'));
        }
    }

    public function retainerPayWithPayHere(Request $request, $retainer_id)
    {
        try {
            $retainer       = Retainer::find($retainer_id);
            $customer       = Customer::find($retainer->customer_id);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $currency       = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
            $api_key        = isset($payment_setting['payhere_merchant_id']) ? $payment_setting['payhere_merchant_id'] : '';
            $get_amount     = $request->amount;
            $order_id       = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            $hash = strtoupper(
                md5(
                    $api_key .
                        $order_id .
                        number_format($get_amount, 2, '.', '') .
                        $currency .
                        strtoupper(md5(config('payhere.merchant_secret')))
                )
            );

            $data = [
                'first_name'    => $customer->name,
                'last_name'     => '',
                'email'         => $customer->email,
                'address'       => '',
                'city'          => '',
                'country'       => '',
                'order_id'      => $order_id,
                'items'         => 'Retainer Payment',
                'currency'      => $currency,
                'amount'        => $get_amount,
                'hash'          => $hash,
            ];

            return PayHere::checkOut()
                ->data($data)
                ->successUrl(route('retainer.payhere.status', ['success' => 1, $retainer_id, $get_amount]))
                ->failUrl(route('retainer.payhere.status', ['success' => 0,  $retainer_id]))
                ->renderView();
        } catch (\Exception $e) {
            return redirect()->route('retainers.index')->with('error', $e->getMessage());
        }
    }

    public function retainerGetPayHereStatus(Request $request, $retainer_id , $getAmount=0)
    {
        if ($request->success == 1) {
            $info = PayHere::retrieve()
                ->orderId($request->order_id)
                ->submit();

            if ($info['data'][0]['order_id'] == $request->order_id) {
                if ($info['data'][0]['status'] == "RECEIVED") {
                    $retainer = Retainer::find($request->retainer_id);

                    $payment = new RetainerPayment();
                    $payment->retainer_id       = $retainer->id;
                    $payment->date              = date('Y-m-d');
                    $payment->amount            = $request->amount;
                    $payment->account_id        = 0;
                    $payment->payment_method    = 0;
                    $payment->order_id          = $request->order_id;
                    $payment->currency          = $retainer->currency;
                    $payment->txn_id            = '';
                    $payment->payment_type      = __('PayHere');
                    $payment->receipt           = '';
                    $payment->reference         = '';
                    $payment->description       = 'Retainer Payment ' . Utility::retainerNumberFormat($retainer->created_by, $retainer->retainer_id);
                    $payment->save();

                    if ($retainer->getDue() <= 0) {
                        $retainer->status = 'close';
                    } elseif (($retainer->getDue() - $payment->amount) == 0) {
                        $retainer->status = 'close';
                    } else {
                        $retainer->status = 'active';
                    }
                    $retainer->save();

                    return redirect()->route('retainers.index')->with('success', __('Retainer payment has been received successfully.'));
                }
            }
        } else {
            return redirect()->route('retainers.index')->with('error', __('Retainer payment failed.'));
        }
    }


}
