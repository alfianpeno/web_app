<?php

namespace App\Http\Controllers;

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

class FedapayController extends Controller
{

    public function invoicePayWithFedapay(Request $request, $invoice_id)
    {
        try {
            $invoice            = Invoice::find($invoice_id);
            $customer           = Customer::find($invoice->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $setting            = Utility::settingsById($invoice->created_by);
            $currency           = isset($setting['site_currency']) ? $setting['site_currency'] : 'XOF';
            $api_key            = isset($payment_setting['fedapay_secret_key']) ? $payment_setting['fedapay_secret_key'] : '';
            $amount             = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            \FedaPay\FedaPay::setApiKey($api_key);

            // Create Fedapay transaction
            $transaction = \FedaPay\Transaction::create([
                "description"   => "Invoice Payment",
                "amount"        => (int)$amount,
                "currency"      => ["iso" => $currency],
                "callback_url"  => route('invoice.fedapay.status', [$invoice_id, $amount]),
                "cancel_url"    => route('invoice.fedapay.status', [$invoice_id, $amount]),
            ]);
            $token = $transaction->generateToken();

            return redirect($token->url);

        } catch (\Exception $e) {
            
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function invoiceGetFedapayStatus(Request $request, $invoice_id, $amount)
    {
        try {
            $invoice            = Invoice::find($invoice_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $setting            = Utility::settingsById($invoice->created_by);

            if ($request->status == 'approved') {
                // Payment is successful
                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                // Record the payment in the database
                $payments = InvoicePayment::create([
                    'invoice_id'    => $invoice->id,
                    'date'          => date('Y-m-d'),
                    'amount'        => $amount,
                    'account_id'    => 0,
                    'payment_method' => 0,
                    'order_id'      => $order_id,
                    'currency'      => $currency,
                    'txn_id'        => '',
                    'payment_type'  => __('Fedapay'),
                    'receipt'       => '',
                    'reference'     => '',
                    'description'   => 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id),
                ]);

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
                $invoicePayment->type        = 'Nepalste';
                $invoicePayment->created_by  = Auth::check() ? Auth::user()->id : $invoice->customer_id;
                $invoicePayment->payment_id  = $payments->id;
                $invoicePayment->category    = 'Invoice';
                $invoicePayment->amount      = $amount;
                $invoicePayment->date        = date('Y-m-d');
                $invoicePayment->created_by  = Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
                $invoicePayment->description = 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id);
                $invoicePayment->account     = 0;
    
                \App\Models\Transaction::addTransaction($invoicePayment);
    
                Utility::updateUserBalance('customer', $invoice->customer_id, $amount, 'debit');
    
                Utility::bankAccountBalance($request->account_id, $amount, 'credit');
    
                //Twilio Notification
                $customer = $objUser = Customer::find($invoice->customer_id);
                $setting  = Utility::settingsById($objUser->creatorId());
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'invoice_id'        => $invoice->id,
                        'payment_name'      => isset($customer->name) ? $customer->name : '',
                        'payment_amount'    => $amount,
                        'payment_date'      => $objUser->dateFormat($request->date),
                        'type'              => 'Nepalste',
                        'user_name'         => $objUser->name,
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
            if (Auth::check()) {
                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been complted.'));
            }
        }
    }

    public function retainerPayWithFedapay(Request $request, $retainer_id)
    {
        try {
            $retainer = Retainer::find($retainer_id);
            $customer = Customer::find($retainer->customer_id);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $setting    = Utility::settingsById($invoretainerice->created_by);

            $currency   = isset($setting['currency']) ? $setting['currency'] : 'USD';
            $api_key    = isset($payment_setting['fedapay_secret_key']) ? $payment_setting['fedapay_secret_key'] : '';
            $amount     = $request->amount;
            $order_id   = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            \FedaPay\FedaPay::setApiKey($api_key);

            // Create Fedapay transaction
            $transaction = \FedaPay\Transaction::create([
                "description"   => "Retainer Payment",
                "amount"        => (int)$amount,
                "currency"      => ["iso" => $currency],
                "callback_url"  => route('retainer.fedapay.status', [$retainer_id, $amount]),
                "cancel_url"    => route('retainer.fedapay.status', [$retainer_id, $amount]),
            ]);

            $token = $transaction->generateToken();
            return redirect($token->url);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function retainerGetFedapayStatus(Request $request, $retainer_id, $amount)
    {
        try {
            $retainer   = Retainer::find($retainer_id);
            $setting    = Utility::settingsById($invoretainerice->created_by);

            $currency = isset($setting['currency']) ? $setting['currency'] : 'USD';

            if ($request->status == 'approved') {
                // Payment is successful
                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                // Record the payment in the database
                $payments = RetainerPayment::create([
                    'retainer_id'   => $retainer->id,
                    'date'          => date('Y-m-d'),
                    'amount'        => $amount,
                    'account_id'    => 0,
                    'payment_method' => 0,
                    'order_id'      => $order_id,
                    'currency'      => $currency,
                    'txn_id'        => '',
                    'payment_type'  => __('Fedapay'),
                    'receipt'       => '',
                    'reference'     => '',
                    'description'   => 'Retainer ' . Utility::retainerNumberFormat($retainer->retainer_id),
                ]);

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
                $retainerPayment->category    = 'Retainer';
                $retainerPayment->amount      = $amount;
                $retainerPayment->date        = date('Y-m-d');
                $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
                $retainerPayment->payment_id  = $payments->id;
                $retainerPayment->description = 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id);
                $retainerPayment->account     = 0;
    
                \App\Models\Transaction::addTransaction($retainerPayment);
    
                Utility::updateUserBalance('customer', $retainer->customer_id, $amount, 'debit');
    
                Utility::bankAccountBalance($request->account_id, $amount, 'credit');
    
                //Twilio Notification
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($retainer->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'retainer_id'       => $payments->id,
                        'payment_name'      => $customer->name,
                        'payment_amount'    => $amount,
                        'payment_date'      => $objUser->dateFormat($request->date),
                        'type'              => 'Cashfree',
                        'user_name'         => $objUser->name,
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

                return redirect()->route('retainers.index')->with('success', __('Payment successful.'));
            } else {
                // Payment failed
                return redirect()->route('retainers.index')->with('error', 'Payment failed.');
            }
        } catch (\Exception $e) {
            return redirect()->route('retainers.index')->with
            ('error', $e->getMessage());
        }
    }
}
