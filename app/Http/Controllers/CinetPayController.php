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

class CinetPayController extends Controller
{

    public function planCinetPayNotify(Request $request , $id= null)
    {
        /* 1- Recovery of parameters posted on the URL by CinetPay
         * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#les-etapes-pour-configurer-lurl-de-notification
         * */
        if (isset($request->cpm_trans_id)) {
            // Using your transaction identifier, check that the order has not yet been processed
            $VerifyStatusCmd = "1"; // status value to retrieve from your database
            if ($VerifyStatusCmd == '00') {
                //The order has already been processed
                // Scarred you script
                die();
            }

            $comapnysetting = Utility::getCompanyPaymentSetting($id);

            /* 2- Otherwise, we check the status of the transaction in the event of a payment attempt on CinetPay
            * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#2-verifier-letat-de-la-transaction */
            $cinetpay_check = [
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $request->cpm_trans_id
            ];

            $response = $this->getPayStatus($cinetpay_check); // call query function to retrieve status

            //We get the response from CinetPay
            $response_body = json_decode($response, true);
            if ($response_body['code'] == '00') {
                /* correct, on délivre le service
                 * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#3-delivrer-un-service*/
                echo 'Congratulations, your payment has been successfully completed';
            } else {
                // transaction a échoué
                echo 'Failure, code:' . $response_body['code'] . ' Description' . $response_body['description'] . ' Message: ' . $response_body['message'];
            }
            // Update the transaction in your database
            /*  $order->update(); */
        } else {
            print("cpm_trans_id non found");
        }
    }

    public function getPayStatus($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment/check',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "content-type:application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            return redirect()->route('plans.index')->with('error', __('Something went wrong!'));

        else
            return ($response);
    }

    public function invoicePayWithCinetPay(Request $request, $invoice_id)
    {
        try {
            $invoice            = Invoice::find($invoice_id);
            $customer           = Customer::find($invoice->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $api_key            = isset($payment_setting['cinetpay_public_key']) ? $payment_setting['cinetpay_public_key'] : '';
            $get_amount         = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            if (
                $currency != 'XOF' &&
                $currency != 'CDF' &&
                $currency != 'USD' &&
                $currency != 'KMF' &&
                $currency != 'GNF'
            ) {
                return redirect()->route('plans.index')->with('error', __('Availabe currencies: XOF, CDF, USD, KMF, GNF'));
            }

            $cinetpay_data =  [
                "amount"            => $get_amount,
                "currency"          => $currency,
                "apikey"            => $api_key,
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $order_id,
                "description"       => "Invoice Payment",
                "return_url"        => route('invoice.cinetpay.return', [$invoice_id, $get_amount]),
                "notify_url"        => route('plan.cinetpay.notify', $invoice_id),
                "metadata"          => $invoice->id,
                'customer_name'     => $customer->name,
                'customer_email'    => $customer->email,
                // Add other customer details if required
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => array(
                    "content-type:application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            $response_body = json_decode($response, true);
            if (isset($response_body['code']) && $response_body['code'] == '201') {
                // Store CinetPay session data if needed
                // Redirect to CinetPay payment URL
                $payment_link = $response_body["data"]["payment_url"];
                return redirect($payment_link);
            } else {
                return redirect()->back()->with('error', isset($response_body["description"]) ? $response_body["description"] : 'Something Went Wrong!!!');
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceCinetPayReturn(Request $request , $invoice_id, $get_amount)
    {
        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');

        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $currency        = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $invoice         = Invoice::find($invoice_id);


        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey"            => $payment_setting['cinetpay_api_key'],
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);

            if ($response_body['code'] == '00') {

                $setting    = Utility::settingsById($invoice->created_by);

                try{

                    $payments = InvoicePayment::create(
                        [
                            'invoice_id'        => $invoice->id,
                            'date'              => date('Y-m-d'),
                            'amount'            => $getAmount,
                            'account_id'        => 0,
                            'payment_method'    => 0,
                            'order_id'          => $request->transaction_id,
                            'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                            'txn_id'            => '',
                            'payment_type'      => __('CinetPay'),
                            'receipt'           => '',
                            'reference'         => '',
                            'description'       => 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id),
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
                    $invoicePayment->type        = 'CinetPay';
                    $invoicePayment->created_by  = Auth::check() ? Auth::user()->id : $invoice->customer_id;
                    $invoicePayment->payment_id  = $payments->id;
                    $invoicePayment->category    = 'Invoice';
                    $invoicePayment->amount      = $getAmount;
                    $invoicePayment->date        = date('Y-m-d');
                    $invoicePayment->created_by  = Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
                    $invoicePayment->description = 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id);
                    $invoicePayment->account     = 0;

                    \App\Models\Transaction::addTransaction($invoicePayment);

                    Utility::updateUserBalance('customer', $invoice->customer_id, $getAmount, 'debit');

                    Utility::bankAccountBalance($request->account_id, $getAmount, 'credit');

                    //Twilio Notification
                    $customer = $objUser = Customer::find($invoice->customer_id);
                    $setting  = Utility::settingsById($objUser->creatorId());
                    if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                        $uArr = [
                            'invoice_id' => $invoice->id,
                            'payment_name' => isset($customer->name) ? $customer->name : '',
                            'payment_amount' => $getAmount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'CinetPay',
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

                } catch (\Throwable $e) {
                    return redirect()->back()->with('error', __($e->getMessage()));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        }
    }

    public function retainerPayWithCinetPay(Request $request, $retainer_id)
    {
        try {
            $retainer           = Retainer::find($retainer_id);
            $customer           = Customer::find($retainer->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($retainer->created_by);
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $api_key            = isset($payment_setting['cinetpay_public_key']) ? $payment_setting['cinetpay_public_key'] : '';
            $get_amount         = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            if (
                $currency != 'XOF' &&
                $currency != 'CDF' &&
                $currency != 'USD' &&
                $currency != 'KMF' &&
                $currency != 'GNF'
            ) {
                return redirect()->route('retainers.index')->with('error', __('Available currencies: XOF, CDF, USD, KMF, GNF'));
            }

            $cinetpay_data =  [
                "amount"            => $get_amount,
                "currency"          => $currency,
                "apikey"            => $api_key,
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $order_id,
                "description"       => "Retainer Payment",
                "return_url"        => route('retainer.cinetpay.return', [$retainer_id, $get_amount]),
                "notify_url"        => route('plan.cinetpay.notify', $retainer_id),
                "metadata"          => '',
                'customer_name'     => $customer->name,
                'customer_email'    => $customer->email,
                // Add other customer details if required
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => array(
                    "content-type:application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            $response_body = json_decode($response, true);
            if (isset($response_body['code']) && $response_body['code'] == '201') {
                // Store CinetPay session data if needed
                // Redirect to CinetPay payment URL
                $payment_link = $response_body["data"]["payment_url"];
                return redirect($payment_link);
            } else {
                return redirect()->back()->with('error', isset($response_body["description"]) ? $response_body["description"] : 'Something Went Wrong!!!');
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerCinetPayReturn(Request $request, $retainer_id, $get_amount)
    {
        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');

        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $currency        = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $retainer        = Retainer::find($retainer_id);

        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey"            => $payment_setting['cinetpay_api_key'],
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);

            if ($response_body['code'] == '00') {

                $setting = Utility::settingsById($retainer->created_by);

                try{

                    $payments = RetainerPayment::create(
                        [
                            'retainer_id'      => $retainer->id,
                            'date'             => date('Y-m-d'),
                            'amount'           => $getAmount,
                            'account_id'       => 0,
                            'payment_method'   => 0,
                            'order_id'         => $request->transaction_id,
                            'currency'         => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                            'txn_id'           => '',
                            'payment_type'     => __('CinetPay'),
                            'receipt'          => '',
                            'reference'        => '',
                            'description'      => 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id),
                        ]
                    );

                    if ($retainer->getDue() <= 0) {
                        $retainer->status = 4;
                        $retainer->save();
                    } elseif (($retainer->getDue() - $payments->amount) == 0) {
                        $retainer->status = 4;
                        $retainer->save();
                    } elseif ($retainer->getDue() > 0) {
                        $retainer->status = 3;
                        $retainer->save();
                    } else {
                        $retainer->status = 2;
                        $retainer->save();
                    }

                    $retainerPayment              = new \App\Models\Transaction();
                    $retainerPayment->user_id     = $retainer->customer_id;
                    $retainerPayment->user_type   = 'Customer';
                    $retainerPayment->type        = 'CinetPay';
                    $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
                    $retainerPayment->category    = 'Retainer';
                    $retainerPayment->amount      = $getAmount;
                    $retainerPayment->date        = date('Y-m-d');
                    $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
                    $retainerPayment->payment_id  = $payments->id;
                    $retainerPayment->description = 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id);
                    $retainerPayment->account     = 0;

                    \App\Models\Transaction::addTransaction($retainerPayment);

                    Utility::updateUserBalance('customer', $retainer->customer_id, $getAmount, 'debit');

                    Utility::bankAccountBalance($request->account_id, $getAmount, 'credit');

                    //Twilio Notification
                    $setting  = Utility::settingsById($objUser->creatorId());
                    $customer = Customer::find($retainer->customer_id);
                    if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                        $uArr = [
                            'retainer_id' => $payments->id,
                            'payment_name' => $customer->name,
                            'payment_amount' => $getAmount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'CinetPay',
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

                    return redirect()->route('retainers.index')->with('success', __('Transaction has been successful'));

                } catch (\Throwable $e) {
                    return redirect()->route('retainers.index')->with('error', __($e->getMessage()));
                }
            } else {
                return redirect()->route('retainers.index')->with('error', __('Your Payment has failed!'));
            }
        }
    }

}

