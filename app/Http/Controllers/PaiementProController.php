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


class PaiementProController extends Controller
{

    public function invoicePayWithPaiementpro(Request $request)
    {
        $invoiceID  = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice    = Invoice::find($invoiceID);

        if ($invoice) {
            $comapnysetting     = Utility::getCompanyPaymentSetting($invoice->created_by);
            $merchant_id        = isset($comapnysetting['paiementpro_merchant_id']) ? $comapnysetting['paiementpro_merchant_id'] : '';

            $get_amount = $request->amount;
            $setting    = Utility::settingsById($invoice->created_by);
            $order_id   = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            try{
                $call_back = route('invoice.paiementpro.status', [
                    $invoice->id,
                ]);
                $data = array(
                    'merchantId'            => $merchant_id,
                    'amount'                => $get_amount,
                    'description'           => "Api PHP",
                    'channel'               => $request->channel ?? 'CARD',
                    'countryCurrencyCode'   => $setting['site_currency'],
                    'referenceNumber'       => "REF-" . time(),
                    'customerEmail'         => 'a@gmail.com',
                    'customerFirstName'     => 'abc',
                    'customerLastname'      => 'xyz',
                    'customerPhoneNumber'   => $request->mobile_number ?? '1234567890',
                    'notificationURL'       => $call_back,
                    'returnURL'             => $call_back,
                    'returnContext'         => 'XYZ',
                );
                $data = json_encode($data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                $response = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($response);
                if (isset($response->success) && $response->success == true) {
                    // redirect to approve href
                    return redirect($response->url);

                } else {
                    return redirect()->back()->with('error',__('Something went wrong'));
                }

            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e);
            }
        } else {
            return redirect()->back()->with('error', 'Invoice is deleted.');
        }
    }

    public function invoiceGetPaiementproStatus(Request $request, $invoice_id)
    {
        $invoice    = Invoice::find($invoice_id);
        $getAmount  = $request->amount;
        $customer   = $objUser = Customer::find($invoice->customer_id);

        try {

            $setting = Utility::settingsById($invoice->created_by);
            if ($request->responsecode == 0) {

                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                $invoicePayment = InvoicePayment::create(
                    [
                        'invoice_id'        => $invoice->id,
                        'date'              => date('Y-m-d'),
                        'amount'            => $getAmount,
                        'account_id'        => 0,
                        'payment_method'    => 0,
                        'order_id'          => $order_id,
                        'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                        'txn_id'            => '',
                        'payment_type'      => __('Paiementpro'),
                        'receipt'           => '',
                        'reference'         => '',
                        'description'       => 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id),
                    ]
                );


                if ($invoice->getDue() <= 0) {
                    $invoice->status = 4;
                    $invoice->save();
                } elseif (($invoice->getDue() - $invoicePayment->amount) == 0) {
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
                $invoicePayment->type        = 'Paiementpro';
                $invoicePayment->created_by  = Auth::check() ? Auth::user()->id : $invoice->customer_id;
                $invoicePayment->payment_id  = $invoicePayment->id;
                $invoicePayment->category    = 'Invoice';
                $invoicePayment->amount      = $getAmount;
                $invoicePayment->date        = date('Y-m-d');
                $invoicePayment->created_by  = Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
                $invoicePayment->payment_id  = $invoicePayment->id;
                $invoicePayment->description = 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id);
                $invoicePayment->account     = 0;

                \App\Models\Transaction::addTransaction($invoicePayment);

                Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                //Twilio Notification
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($invoice->customer_id);
                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'invoice_id'        => $invoice->id,
                        'payment_name'      => isset($customer->name) ? $customer->name : '',
                        'payment_amount'    => $getAmount,
                        'payment_date'      => $objUser->dateFormat($request->date),
                        'type'              => 'Paiementpro',
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
                return redirect()->back()->with('success', __('Transaction has been success'));
            } else {
                return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __($e));
        }
    }

    public function retainerPayWithPaiementpro(Request $request, $retainer_id)
    {
        try {

            $retainer       = Retainer::find($retainer_id);
            $customers      = Customer::find($retainer->customer_id);
            $comapnysetting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $merchant_id    = isset($comapnysetting['paiementpro_merchant_id']) ? $comapnysetting['paiementpro_merchant_id'] : '';

            $get_amount     = $request->amount;
            $request->validate(['amount' => 'required|numeric|min:0']);

            $order_id   = strtoupper(str_replace('.', '', uniqid('', true)));
            $setting    = Utility::settingsById($retainer->created_by);

            try {

                $call_back = route('retainer.paiementpro.status', [$retainer->id, $get_amount]);

                $data = array(
                    'merchantId'            => $merchant_id,
                    'amount'                => $get_amount,
                    'description'           => "Api PHP",
                    'channel'               => $request->channel,
                    'countryCurrencyCode'   => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                    'referenceNumber'       => "REF-" . time(),
                    'customerEmail'         => '',
                    'customerFirstName'     => '',
                    'customerLastname'      => '',
                    'customerPhoneNumber'   => $request->mobile,
                    'notificationURL'       => $call_back,
                    'returnURL'             => $call_back,
                    'returnContext'         => '',
                );

                $data = json_encode($data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://www.paiementpro.net/webservice/onlinepayment/init/curl-init.php");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_HEADER, FALSE);
                curl_setopt($ch, CURLOPT_POST, TRUE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                $response = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($response);
                if (isset($response->success) && $response->success == true) {
                    // redirect to approve href
                    return redirect($response->url);

                } else {
                    return redirect()->back()->with('error',__('Something went wrong'));
                }

            } catch (\Throwable $th) {
                return redirect()->back()->with('error', __($th->getMessage()));
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerGetPaiementproStatus(Request $request)
    {
        $retainer       = Retainer::find($request->retainer);
        $objUser        = User::where('id', $retainer->created_by)->first();
        $getAmount      = $request->amount;
        $setting        = Utility::settingsById($retainer->created_by);
        $comapnysetting = Utility::getCompanyPaymentSetting($retainer->created_by);

        try {

            if ($request->responsecode == 0) {

                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                $payments = RetainerPayment::create(
                    [
                        'retainer_id'       => $retainer->id,
                        'date'              => date('Y-m-d'),
                        'amount'            => $getAmount,
                        'account_id'        => 0,
                        'payment_method'    => 0,
                        'order_id'          => $order_id,
                        'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                        'txn_id'            => $getAmount,
                        'payment_type'      => __('Paiementpro'),
                        'receipt'           => '',
                        'reference'         => '',
                        'description'       => 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id),
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
                $retainerPayment->type        = 'Paiementpro';
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
                        'retainer_id'       => $payments->id,
                        'payment_name'      => $customer->name,
                        'payment_amount'    => $getAmount,
                        'payment_date'      => $objUser->dateFormat($request->date),
                        'type'              => 'Paiementpro',
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

                return redirect()->back()->with('success', __('Transaction has been success'));
            } else {
                return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __($e));
        }
    }
}
