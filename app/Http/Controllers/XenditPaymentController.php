<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Xendit\Xendit;

class XenditPaymentController extends Controller
{
    public function invoicePayWithXendit(Request $request)
    {
        $invoice_id = decrypt($request->invoice_id);

        $invoice = Invoice::find($invoice_id);

        $user = User::where('id', $invoice->created_by)->first();
        $get_amount = $request->amount;
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        try {
            if ($invoice) {
                $payment_setting = Utility::getCompanyPaymentSetting($user->id);
                $xendit_token = $payment_setting['xendit_token'];
                $xendit_api = $payment_setting['xendit_api'];
                $currency = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'RUB';
                $response = ['orderId' => $orderID, 'user' => $user, 'get_amount' => $get_amount, 'invoice' => $invoice, 'currency' => $currency];
                Xendit::setApiKey($xendit_api);
                $params = [
                    'external_id' => $orderID,
                    'payer_email' => $user->email,
                    'description' => 'Payment for order ' . $orderID,
                    'amount' => $get_amount,
                    'callback_url' =>  route('invoice.xendit.status'),
                    'success_redirect_url' => route('invoice.xendit.status', $response),
                ];

                $Xenditinvoice = \App\Xendit\Invoice::create($params);
                Session::put('invoicepay', $Xenditinvoice);
                return redirect($Xenditinvoice['invoice_url']);
            } else {
                return redirect()->back()->with('error', 'Invoice not found.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e));
        }
    }

    public function getInvociePaymentStatus(Request $request)
    {
        $get_amount = $request['get_amount'];
        $session = Session::get('invoicepay');
        $invoice = Invoice::find($request['invoice']);
        $user = User::where('id', $invoice->created_by)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $xendit_api = $payment_setting['xendit_api'];
        Xendit::setApiKey($xendit_api);
        $getInvoice = \App\Xendit\Invoice::retrieve($session['id']);
        $setting = Utility::settingsById($invoice->created_by);


        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($invoice->created_by);
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($invoice->created_by);
            $objUser = $user;
        }

        if ($getInvoice['status'] == 'PAID') {

            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $payments = InvoicePayment::create(
                [

                    'invoice_id' => $invoice->id,
                    'date' => date('Y-m-d'),
                    'amount' => $get_amount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => $setting['site_currency'],
                    'txn_id' => '',
                    'payment_type' => __('Xendit'),
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
            $invoicePayment->type        = 'Yookassa';
            $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->id : $invoice->customer_id;
            $invoicePayment->payment_id  = $invoicePayment->id;
            $invoicePayment->category    = 'Invoice';
            $invoicePayment->amount      = $get_amount;
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
                    'payment_amount' => $get_amount,
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
            }
        }
        if (Auth::check()) {
            // return redirect()->route('pay.invoice', $invoice->id)->with('success', __('Invoice paid Successfully!'));
            return redirect()->back()->with('success', __(' Payment successfully added.'));
        } else {
            // return redirect()->route('pay.invoice', encrypt($invoice->id))->with('success', __('Invoice paid Successfully!'));
            return redirect()->back()->with('success', __(' Payment successfully added.'));
        }
    }

    public function retainerPayWithXendit(Request $request)
    {
        $retainer_id = decrypt($request->retainer_id);

        $retainer = Retainer::find($retainer_id);

        $user = User::where('id', $retainer->created_by)->first();
        $get_amount = $request->amount;
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        try {
            if ($retainer) {
                $payment_setting = Utility::getCompanyPaymentSetting($user->id);
                $xendit_token = $payment_setting['xendit_token'];
                $xendit_api = $payment_setting['xendit_api'];
                $currency = isset($payment_setting['site_currency']) ? $payment_setting['site_currency'] : 'RUB';
                $response = ['orderId' => $orderID, 'user' => $user, 'get_amount' => $get_amount, 'retainer' => $retainer, 'currency' => $currency];
                Xendit::setApiKey($xendit_api);
                $params = [
                    'external_id' => $orderID,
                    'payer_email' => Auth::user()->email,
                    'description' => 'Payment for order ' . $orderID,
                    'amount' => $get_amount,
                    'callback_url' =>  route('retainer.xendit.status'),
                    'success_redirect_url' => route('retainer.xendit.status', $response),
                ];

                $Xenditinvoice = \App\Xendit\Invoice::create($params);
                Session::put('invoicepay', $Xenditinvoice);
                return redirect($Xenditinvoice['invoice_url']);
            } else {
                return redirect()->back()->with('error', 'Retainer not found.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e));
        }
    }

    public function getRetainerPaymentStatus(Request $request)
    {
        $get_amount = $request['get_amount'];
        $session = Session::get('invoicepay');
        $retainer = Retainer::find($request['retainer']);
        $user = User::where('id', $retainer->created_by)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $xendit_api = $payment_setting['xendit_api'];
        Xendit::setApiKey($xendit_api);
        $getretainer = \App\Xendit\Invoice::retrieve($session['id']);
        $setting = Utility::settingsById($retainer->created_by);


        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($retainer->created_by);
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $payment_setting = Utility::getCompanyPaymentSettingWithOutAuth($retainer->created_by);
            $objUser = $user;
        }

        if ($getretainer['status'] == 'PAID') {

            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $payments = RetainerPayment::create(
                [

                    'retainer_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    'amount' => $get_amount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => $setting['site_currency'],
                    'txn_id' => '',
                    'payment_type' => __('Xendit'),
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
            $retainerPayment->type        = 'Xendit';
            $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
            $retainerPayment->payment_id  = $retainerPayment->id;
            $retainerPayment->category    = 'Retainer';
            $retainerPayment->amount      = $get_amount;
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
            }
        }
        if (Auth::check()) {
            // return redirect()->route('pay.invoice', $invoice->id)->with('success', __('Invoice paid Successfully!'));
            return redirect()->back()->with('success', __(' Payment successfully added.'));
        } else {
            // return redirect()->route('pay.invoice', encrypt($invoice->id))->with('success', __('Invoice paid Successfully!'));
            return redirect()->back()->with('success', __(' Payment successfully added.'));
        }
    }
}
