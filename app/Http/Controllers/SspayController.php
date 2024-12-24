<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Utility;
use App\Models\RetainerPayment;
use Illuminate\Support\Facades\DB;

class SspayController extends Controller
{
    public $secretKey, $callBackUrl, $returnUrl, $categoryCode, $is_enabled, $invoiceData, $retainerData;

    public function paymentConfig()
    {
        if (\Auth::check()) {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting(!empty($this->invoiceData) ? $this->invoiceData->created_by : 0);
        }

        $this->secretKey = isset($payment_setting['sspay_secret_key']) ? $payment_setting['sspay_secret_key'] : '';
        $this->categoryCode                = isset($payment_setting['sspay_category_code']) ? $payment_setting['sspay_category_code'] : '';
        $this->is_enabled          = isset($payment_setting['is_sspay_enabled']) ? $payment_setting['is_sspay_enabled'] : 'off';
        return $this;
    }

    public function invoicepaywithsspaypay(Request $request)
    {
        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice = Invoice::find($invoiceID);
        $this->invoiceData = $invoice;
        $user      = User::find($invoice->created_by);

        $settings  = DB::table('settings')->where('created_by', '=', $invoice->created_by)->get()->pluck('value', 'name');

        $get_amount = $request->amount;
        $this->paymentConfig();

        if ($invoice) {
            if ($get_amount > $invoice->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $name = Utility::invoiceNumberFormat($settings, $invoice->invoice_id);
                $this->callBackUrl = route('invoice.sspay', [$invoice->id, $get_amount]);
                $this->returnUrl = route('invoice.sspay', [$invoice->id, $get_amount]);
            }
            $Date = date('d-m-Y');
            $ammount = $get_amount;
            $billExpiryDays = 3;
            $billExpiryDate = date('d-m-Y', strtotime($Date . ' + 3 days'));
            $billContentEmail = "Thank you for purchasing our product!";
            $some_data = array(
                'userSecretKey' => $this->secretKey,
                'categoryCode' => $this->categoryCode,
                'billName' => "invoice",
                'billDescription' => "invoice",
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => 100 * $ammount,
                'billReturnUrl' => $this->returnUrl,
                'billCallbackUrl' => $this->callBackUrl,
                'billExternalReferenceNo' => 'AFR341DFI',
                'billTo' => $user->name,
                'billEmail' => $user->email,
                'billPhone' => '0000000000',
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => '0',
                'billContentEmail' => $billContentEmail,
                'billChargeToCustomer' => 1,
                'billExpiryDate' => $billExpiryDate,
                'billExpiryDays' => $billExpiryDays,
            );

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://sspay.my/index.php/api/createBill');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);
            $result = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            $obj = json_decode($result);
            return redirect('https://sspay.my/' . $obj[0]->BillCode);

            return redirect()->route('invoice.show', \Crypt::encrypt($invoiceID))->back()->with('error', __('Unknown error occurred'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $amount)
    {
        // dd($request->all(),$invoice_id, $amount);
        $invoice = Invoice::find($invoice_id);
        $this->invoiceData = $invoice;
        $settings  = DB::table('settings')->where('created_by', '=', $invoice->created_by)->get()->pluck('value', 'name');

        if (\Auth::check()) {
            $objUser = \Auth::user();
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $objUser = $user;
        }

        $payment_id = \Session::get('PayerID');
        \Session::forget('PayerID');
        if (empty($request->PayerID || empty($request->token))) {
            return redirect()->back()->with('error', __('Payment failed'));
        }
        $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
        // $request['status_id'] = 1;
        try {

            // dd($request->status_id);
            if ($request->status_id == 3) {
                return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
                // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Your Transaction is fail please try again'));
            } else if ($request->status_id == 2) {
                return redirect()->back()->with('error', __('Your Transaction on pending'));
                // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Your Transaction on pending'));
            } else if ($request->status_id == 1) {

                $payments = InvoicePayment::create(
                    [

                        'invoice_id' => $invoice->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'account_id' => 0,
                        'payment_method' => 0,
                        'order_id' => $orderID,
                        'payment_type' => __('Sspay'),
                        'receipt' => '',
                        'reference' => '',
                        'description' => 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
                    ]
                );


                $invoicePayment              = new \App\Models\Transaction();
                $invoicePayment->user_id     = $invoice->customer_id;
                $invoicePayment->user_type   = 'Customer';
                $invoicePayment->type        = 'Sspay';
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


                // Twilio
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($invoice->customer_id);

                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'invoice_id' => $invoice->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Sspay',
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


                if (\Auth::check()) {
                    // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                    return redirect()->back()->with('error', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            }
        } catch (\Exception $e) {
            if (\Auth::check()) {
                // return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                return redirect()->back()->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been completed.'));
            }
        }
    }

    public function retainerpaywithsspaypay(Request $request)
    {
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer = Retainer::find($retainerID);
        $this->retainerData = $retainer;
        $user      = User::find($retainer->created_by);

        $settings  = DB::table('settings')->where('created_by', '=', $retainer->created_by)->get()->pluck('value', 'name');

        $get_amount = $request->amount;
        $this->paymentConfig();

        if ($retainer) {
            if ($get_amount > $retainer->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $name = Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                $this->callBackUrl = route('retainer.sspay', [$retainer->id, $get_amount]);
                $this->returnUrl = route('retainer.sspay', [$retainer->id, $get_amount]);
            }
            $Date = date('d-m-Y');
            $ammount = $get_amount;
            $billExpiryDays = 3;
            $billExpiryDate = date('d-m-Y', strtotime($Date . ' + 3 days'));
            $billContentEmail = "Thank you for purchasing our product!";
            $some_data = array(
                'userSecretKey' => $this->secretKey,
                'categoryCode' => $this->categoryCode,
                'billName' => "retainer",
                'billDescription' => "retainer",
                'billPriceSetting' => 1,
                'billPayorInfo' => 1,
                'billAmount' => 100 * $ammount,
                'billReturnUrl' => $this->returnUrl,
                'billCallbackUrl' => $this->callBackUrl,
                'billExternalReferenceNo' => 'AFR341DFI',
                'billTo' => $user->name,
                'billEmail' => $user->email,
                'billPhone' => '0000000000',
                'billSplitPayment' => 0,
                'billSplitPaymentArgs' => '',
                'billPaymentChannel' => '0',
                'billContentEmail' => $billContentEmail,
                'billChargeToCustomer' => 1,
                'billExpiryDate' => $billExpiryDate,
                'billExpiryDays' => $billExpiryDays,
            );

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_URL, 'https://sspay.my/index.php/api/createBill');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $some_data);
            $result = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            $obj = json_decode($result);
            return redirect('https://sspay.my/' . $obj[0]->BillCode);

            return redirect()->route('retainer.show', \Crypt::encrypt($retainerID))->back()->with('error', __('Unknown error occurred'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {
        // dd($request->all(),$retainer_id, $amount);
        $retainer = Retainer::find($retainer_id);
        $this->retainerData = $retainer;
        $settings  = DB::table('settings')->where('created_by', '=', $retainer->created_by)->get()->pluck('value', 'name');

        if (\Auth::check()) {
            $objUser = \Auth::user();
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $objUser = $user;
        }

        $payment_id = \Session::get('PayerID');
        \Session::forget('PayerID');
        if (empty($request->PayerID || empty($request->token))) {
            return redirect()->back()->with('error', __('Payment failed'));
        }
        $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
        // $request['status_id'] = 1;
        try {

            // dd($request->status_id);
            if ($request->status_id == 3) {
                return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
                // return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Your Transaction is fail please try again'));
            } else if ($request->status_id == 2) {
                return redirect()->back()->with('error', __('Your Transaction on pending'));
                // return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Your Transaction on pending'));
            } else if ($request->status_id == 1) {

                $payments = RetainerPayment::create(
                    [

                        'retainer_id' => $retainer->id,
                        'date' => date('Y-m-d'),
                        'amount' => $amount,
                        'account_id' => 0,
                        'payment_method' => 0,
                        'order_id' => $orderID,
                        'payment_type' => __('Sspay'),
                        'receipt' => '',
                        'reference' => '',
                        'description' => 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
                    ]
                );


                $retainerPayment              = new \App\Models\Transaction();
                $retainerPayment->user_id     = $retainer->customer_id;
                $retainerPayment->user_type   = 'Customer';
                $retainerPayment->type        = 'Sspay';
                $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
                $retainerPayment->payment_id  = $retainerPayment->id;
                $retainerPayment->category    = 'Invoice';
                $retainerPayment->amount      = $amount;
                $retainerPayment->date        = date('Y-m-d');
                $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
                $retainerPayment->payment_id  = $payments->id;
                $retainerPayment->description = 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                $retainerPayment->account     = 0;
                \App\Models\Transaction::addTransaction($retainerPayment);

                Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

                Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');


                // Twilio
                $setting  = Utility::settingsById($objUser->creatorId());
                $customer = Customer::find($retainer->customer_id);

                if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                    $uArr = [
                        'retainer_id' => $retainer->id,
                        'payment_name' => $customer->name,
                        'payment_amount' => $amount,
                        'payment_date' => $objUser->dateFormat($request->date),
                        'type' => 'Sspay',
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


                if (\Auth::check()) {
                    // return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
                    return redirect()->back()->with('error', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            }
        } catch (\Exception $e) {
            if (\Auth::check()) {
                // return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
                return redirect()->back()->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been completed.'));
            }
        }
    }
}
