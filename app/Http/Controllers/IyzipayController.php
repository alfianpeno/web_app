<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Utility;
use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use Exception;

class IyziPayController extends Controller
{
    public function invoicePayWithIyziPay(Request $request, $invoice_id)
    {

        // dd($request->all());
        $invoice = Invoice::find($invoice_id);
        $authuser  = Customer::find($invoice->customer_id);
        $currency = Utility::getValByName('site_currency');
        if (\Auth::check()) {

            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();

            $settings = Utility::settingById($invoice->created_by);
        }

        $companyPaymentSettings = Utility::getCompanyPaymentSetting($invoice->created_by);

        $iyzipay_key = $companyPaymentSettings['iyzipay_private_key'];
        $iyzipay_secret = $companyPaymentSettings['iyzipay_secret_key'];
        $iyzipay_mode = $companyPaymentSettings['iyzipay_mode'];

        $get_amount = $request->amount;

        $request->validate(['amount' => 'required|numeric|min:0']);

        try {
            $address = !empty($authuser['billing_address']) ? $authuser['billing_address'] : '';
            $setBaseUrl = ($iyzipay_mode == 'sandbox') ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';
            $options = new \Iyzipay\Options();
            $options->setApiKey($iyzipay_key);
            $options->setSecretKey($iyzipay_secret);
            $options->setBaseUrl($setBaseUrl); // or "https://api.iyzipay.com" for production
            $ipAddress = Http::get('https://ipinfo.io/?callback=')->json();
            $address = ($address) ? $address : 'Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1';
            // create a new payment request
            $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
            $request->setLocale('en');
            $request->setPrice($get_amount);
            $request->setPaidPrice($get_amount);
            $request->setCurrency($currency);
            $request->setCallbackUrl(route('iyzipay.callback', [$invoice->id, $get_amount]));
            $request->setEnabledInstallments(array(1));
            $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
            $buyer = new \Iyzipay\Model\Buyer();
            $buyer->setId($authuser->id);
            $buyer->setName(explode(' ', $authuser->name)[0]);
            $buyer->setSurname(explode(' ', $authuser->name)[0]);
            $buyer->setGsmNumber("+" . $authuser->dial_code . $authuser->phone);
            $buyer->setEmail($authuser->email);
            $buyer->setIdentityNumber(rand(0, 999999));
            $buyer->setLastLoginDate("2023-03-05 12:43:35");
            $buyer->setRegistrationDate("2023-04-21 15:12:09");
            $buyer->setRegistrationAddress($address);
            $buyer->setIp($ipAddress['ip']);
            $buyer->setCity($ipAddress['city']);
            $buyer->setCountry($ipAddress['country']);
            $buyer->setZipCode($ipAddress['postal']);
            $request->setBuyer($buyer);
            $shippingAddress = new \Iyzipay\Model\Address();
            $shippingAddress->setContactName($authuser->name);
            $shippingAddress->setCity($ipAddress['city']);
            $shippingAddress->setCountry($ipAddress['country']);
            $shippingAddress->setAddress($address);
            $shippingAddress->setZipCode($ipAddress['postal']);
            $request->setShippingAddress($shippingAddress);
            $billingAddress = new \Iyzipay\Model\Address();
            $billingAddress->setContactName($authuser->name);
            $billingAddress->setCity($ipAddress['city']);
            $billingAddress->setCountry($ipAddress['country']);
            $billingAddress->setAddress($address);
            $billingAddress->setZipCode($ipAddress['postal']);
            $request->setBillingAddress($billingAddress);
            $basketItems = array();
            $firstBasketItem = new \Iyzipay\Model\BasketItem();
            $firstBasketItem->setId("BI101");
            $firstBasketItem->setName("Binocular");
            $firstBasketItem->setCategory1("Collectibles");
            $firstBasketItem->setCategory2("Accessories");
            $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
            $firstBasketItem->setPrice($get_amount);
            $basketItems[0] = $firstBasketItem;
            $request->setBasketItems($basketItems);

            $checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, $options);
            return redirect()->to($checkoutFormInitialize->getpaymentPageUrl());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function iyzipaypaymentCallback(Request $request, $invoice_id, $amount)
    {
        $invoice = Invoice::find($invoice_id);

        if (Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->non_auth_setApiContext($invoice->created_by);
            $objUser = $user;
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
                    'currency' => Utility::getValByName('site_currency'),
                    'txn_id' => time(),
                    'payment_type' => __('Iyzipay'),
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
            $invoicePayment->type        = 'Iyzipay';
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

    public function retainerPayWithIyziPay(Request $request, $retainer_id)
    {

        $retainer = Retainer::find($retainer_id);
        $authuser  = Customer::find($retainer->customer_id);
        $currency = Utility::getValByName('site_currency');
        if (\Auth::check()) {

            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
        }

        $companyPaymentSettings = Utility::getCompanyPaymentSetting($retainer->created_by);

        $iyzipay_key = $companyPaymentSettings['iyzipay_private_key'];
        $iyzipay_secret = $companyPaymentSettings['iyzipay_secret_key'];
        $iyzipay_mode = $companyPaymentSettings['iyzipay_mode'];

        $get_amount = $request->amount;


        $request->validate(['amount' => 'required|numeric|min:0']);

        try {
            $address = !empty($authuser['billing_address']) ? $authuser['billing_address'] : '';
            $setBaseUrl = ($iyzipay_mode == 'sandbox') ? 'https://sandbox-api.iyzipay.com' : 'https://api.iyzipay.com';
            $options = new \Iyzipay\Options();
            $options->setApiKey($iyzipay_key);
            $options->setSecretKey($iyzipay_secret);
            $options->setBaseUrl($setBaseUrl); // or "https://api.iyzipay.com" for production
            $ipAddress = Http::get('https://ipinfo.io/?callback=')->json();
            $address = ($address) ? $address : 'Nidakule Göztepe, Merdivenköy Mah. Bora Sok. No:1';
            // create a new payment request
            $request = new \Iyzipay\Request\CreateCheckoutFormInitializeRequest();
            $request->setLocale('en');
            $request->setPrice($get_amount);
            $request->setPaidPrice($get_amount);
            $request->setCurrency($currency);
            $request->setCallbackUrl(route('retainer.iyzipay.callback', [$retainer->id, $get_amount]));
            $request->setEnabledInstallments(array(1));
            $request->setPaymentGroup(\Iyzipay\Model\PaymentGroup::PRODUCT);
            $buyer = new \Iyzipay\Model\Buyer();
            $buyer->setId($authuser->id);
            $buyer->setName(explode(' ', $authuser->name)[0]);
            $buyer->setSurname(explode(' ', $authuser->name)[0]);
            $buyer->setGsmNumber("+" . $authuser->dial_code . $authuser->phone);
            $buyer->setEmail($authuser->email);
            $buyer->setIdentityNumber(rand(0, 999999));
            $buyer->setLastLoginDate("2023-03-05 12:43:35");
            $buyer->setRegistrationDate("2023-04-21 15:12:09");
            $buyer->setRegistrationAddress($address);
            $buyer->setIp($ipAddress['ip']);
            $buyer->setCity($ipAddress['city']);
            $buyer->setCountry($ipAddress['country']);
            $buyer->setZipCode($ipAddress['postal']);
            $request->setBuyer($buyer);
            $shippingAddress = new \Iyzipay\Model\Address();
            $shippingAddress->setContactName($authuser->name);
            $shippingAddress->setCity($ipAddress['city']);
            $shippingAddress->setCountry($ipAddress['country']);
            $shippingAddress->setAddress($address);
            $shippingAddress->setZipCode($ipAddress['postal']);
            $request->setShippingAddress($shippingAddress);
            $billingAddress = new \Iyzipay\Model\Address();
            $billingAddress->setContactName($authuser->name);
            $billingAddress->setCity($ipAddress['city']);
            $billingAddress->setCountry($ipAddress['country']);
            $billingAddress->setAddress($address);
            $billingAddress->setZipCode($ipAddress['postal']);
            $request->setBillingAddress($billingAddress);
            $basketItems = array();
            $firstBasketItem = new \Iyzipay\Model\BasketItem();
            $firstBasketItem->setId("BI101");
            $firstBasketItem->setName("Binocular");
            $firstBasketItem->setCategory1("Collectibles");
            $firstBasketItem->setCategory2("Accessories");
            $firstBasketItem->setItemType(\Iyzipay\Model\BasketItemType::PHYSICAL);
            $firstBasketItem->setPrice($get_amount);
            $basketItems[0] = $firstBasketItem;
            $request->setBasketItems($basketItems);

            $checkoutFormInitialize = \Iyzipay\Model\CheckoutFormInitialize::create($request, $options);
            return redirect()->to($checkoutFormInitialize->getpaymentPageUrl());
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retaineriyzipaypaymentCallback(Request $request, $retainer_id, $amount)
    {
        $retainer = Retainer::find($retainer_id);

        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            //            $this->non_auth_setApiContext($invoice->created_by);
            $objUser = $user;
        }

        try {
            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

            $payments = RetainerPayment::create(
                [

                    'retainer_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    'amount' => $amount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => Utility::getValByName('site_currency'),
                    'txn_id' => time(),
                    'payment_type' => __('Iyzipay'),
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
            $retainerPayment->type        = 'Iyzipay';
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

            //Twilio Notification
            $setting  = Utility::settingsById($objUser->creatorId());
            $customer = Customer::find($retainer->customer_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'invoice_id' => $payments->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $amount,
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

            if (Auth::check()) {
                return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        } catch (\Exception $e) {
            if (Auth::check()) {
                return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been complted.'));
            }
        }
    }
}
