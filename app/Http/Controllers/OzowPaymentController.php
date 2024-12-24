<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;



class OzowPaymentController extends Controller
{
    function generate_request_hash_check($inputString)
    {
        $stringToHash = strtolower($inputString);
        return $this->get_sha512_hash($stringToHash);
    }

    function get_sha512_hash($stringToHash)
    {
        return hash('sha512', $stringToHash);
    }

    // public function planPayWithozow(Request $request)
    // {
    //     $payment_setting    = Utility::getAdminPaymentSetting();
    //     $user               = \Auth::user();
    //     $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
    //     $planID             = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);

    //     if ($currency !== 'ZAR') {
    //         return redirect()->back()->with('error', __('Transaction currency must be ZAR.'));
    //     }
    //     $plan       = Plan::find($planID);

    //     $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
    //     if ($plan) {

    //         $price = $plan->price;
    //         $get_amount = $price;
    //         $coupons    = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
    //         if (!empty($request->coupon)) {
    //             $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
    //             if (!empty($coupons)) {
    //                 $usedCoupun     = $coupons->used_coupon();
    //                 $discount_value = ($price / 100) * $coupons->discount;

    //                 $get_amount     = $price - $discount_value;
    //                 if ($coupons->limit == $usedCoupun) {
    //                     return redirect()->back()->with('error', __('This coupon code has expired.'));
    //                 }
    //                 if ($get_amount <= 0) {
    //                     $authuser       = \Auth::user();
    //                     $authuser->plan = $plan->id;
    //                     $authuser->save();
    //                     $assignPlan     = $authuser->assignPlan($plan->id, $authuser->id, $request->ozow_payment_frequency);
    //                     if ($assignPlan['is_success'] == true && !empty($plan)) {

    //                         $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
    //                         $userCoupon = new UserCoupon();

    //                         $userCoupon->user   = $authuser->id;
    //                         $userCoupon->coupon = $coupons->id;
    //                         $userCoupon->order  = $orderID;
    //                         $userCoupon->save();
    //                         Order::create(
    //                             [
    //                                 'order_id'          => $orderID,
    //                                 'name'              => null,
    //                                 'email'             => null,
    //                                 'card_number'       => null,
    //                                 'card_exp_month'    => null,
    //                                 'card_exp_year'     => null,
    //                                 'plan_name'         => $plan->name,
    //                                 'plan_id'           => $plan->id,
    //                                 'price'             => $get_amount == null ? 0 : $get_amount,
    //                                 'price_currency'    => $currency,
    //                                 'txn_id'            => '',
    //                                 'payment_type'      => 'Paiement Pro',
    //                                 'payment_status'    => 'success',
    //                                 'receipt'           => null,
    //                                 'user_id'           => $authuser->id,
    //                             ]
    //                         );
    //                         $assignPlan = $authuser->assignPlan($plan->id, $authuser->id, $price);
    //                         return redirect()->route('plans')->with('success', __('Plan Successfully Activated'));
    //                     }
    //                 }
    //             } else {
    //                 return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
    //             }
    //         }

    //         try {
    //             $siteCode       = isset($payment_setting['ozow_site_key']) ? $payment_setting['ozow_site_key'] : '';
    //             $privateKey     = isset($payment_setting['ozow_private_key']) ? $payment_setting['ozow_private_key'] : '';
    //             $apiKey         = isset($payment_setting['ozow_api_key']) ? $payment_setting['ozow_api_key'] : '';
    //             $isTest         = isset($payment_setting['ozow_mode']) && $payment_setting['ozow_mode'] == 'sandbox'  ? 'true' : 'false';
    //             $plan_id        = $plan->id;

    //             $countryCode        = "ZA";
    //             $currencyCode   = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
    //             $amount         = $get_amount;
    //             $bankReference  = time() . 'FKU';
    //             $transactionReference = time();

    //             $cancelUrl  = route('plan.get.ozow.status', [
    //                 'plan_id'       => $plan_id,
    //                 'amount'        => $get_amount,
    //                 'coupon_code'   => $request->coupon,
    //             ]);
    //             $errorUrl   = route('plan.get.ozow.status', [
    //                 'plan_id'       => $plan_id,
    //                 'amount'        => $get_amount,
    //                 'coupon_code'   => $request->coupon,
    //             ]);
    //             $successUrl = route('plan.get.ozow.status', [
    //                 'plan_id'       => $plan_id,
    //                 'amount'        => $get_amount,
    //                 'coupon_code'   => $request->coupon,
    //             ]);
    //             $notifyUrl  = route('plan.get.ozow.status', [
    //                 'plan_id'       => $plan_id,
    //                 'amount'        => $get_amount,
    //                 'coupon_code'   => $request->coupon,
    //             ]);

    //             // Calculate the hash with the exact same data being sent
    //             $inputString    = $siteCode . $countryCode . $currencyCode . $amount . $transactionReference . $bankReference . $cancelUrl . $errorUrl . $successUrl . $notifyUrl . $isTest . $privateKey;
    //             $hashCheck      = $this->generate_request_hash_check($inputString);
    //             $data = [
    //                 "countryCode"           => $countryCode,
    //                 "amount"                => $amount,
    //                 "transactionReference"  => $transactionReference,
    //                 "bankReference"         => $bankReference,
    //                 "cancelUrl"             => $cancelUrl,
    //                 "currencyCode"          => $currencyCode,
    //                 "errorUrl"              => $errorUrl,
    //                 "isTest"                => $isTest, // boolean value here is okay
    //                 "notifyUrl"             => $notifyUrl,
    //                 "siteCode"              => $siteCode,
    //                 "successUrl"            => $successUrl,
    //                 "hashCheck"             => $hashCheck,
    //             ];
    //             $curl = curl_init();
    //             curl_setopt_array($curl, array(
    //                 CURLOPT_URL             => 'https://api.ozow.com/postpaymentrequest',
    //                 CURLOPT_RETURNTRANSFER  => true,
    //                 CURLOPT_ENCODING        => '',
    //                 CURLOPT_MAXREDIRS       => 10,
    //                 CURLOPT_TIMEOUT         => 0,
    //                 CURLOPT_FOLLOWLOCATION  => true,
    //                 CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
    //                 CURLOPT_CUSTOMREQUEST   => 'POST',
    //                 CURLOPT_POSTFIELDS      => json_encode($data),
    //                 CURLOPT_HTTPHEADER      => array(
    //                     'Accept: application/json',
    //                     'ApiKey: ' . $apiKey,
    //                     'Content-Type: application/json'
    //                 ),
    //             ));

    //             $response   = curl_exec($curl);
    //             curl_close($curl);
    //             $json_attendance = json_decode($response, true);

    //             if (isset($json_attendance['url']) && $json_attendance['url'] != null) {
    //                 return redirect()->away($json_attendance['url']);
    //             } else {
    //                 return redirect()->back()->with('error',  $json_attendance['errorMessage'] ?? 'Something went wrong.');
    //             }
    //         } catch (\Exception $e) {
    //             return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
    //         }
    //     } else {
    //         return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
    //     }
    // }

    // public function planGetozowStatus(Request $request)
    // {
    //     $user = \Auth::user();
    //     $plan = Plan::find($request->plan_id);

    //     if ($plan) {
    //         $admin_settings = Utility::getAdminPaymentSetting();
    //         $currency       = isset($admin_settings['currency']) ? $admin_settings['currency'] : '';
    //         $orderID        = strtoupper(str_replace('.', '', uniqid('', true)));
    //         try {
    //             if ($request->has('coupon_id') && $request->coupon_id != '') {
    //                 $coupons = Coupon::find($request->coupon_id);
    //                 if (!empty($coupons)) {
    //                     $userCoupon         = new UserCoupon();
    //                     $userCoupon->user   = $user->id;
    //                     $userCoupon->coupon = $coupons->id;
    //                     $userCoupon->order  = $orderID;
    //                     $userCoupon->save();
    //                     $usedCoupun = $coupons->used_coupon();
    //                     if ($coupons->limit <= $usedCoupun) {
    //                         $coupons->is_active = 0;
    //                         $coupons->save();
    //                     }
    //                 }
    //             }

    //             if (isset($request['Status']) && $request['Status'] == 'Complete') {
    //                 Utility::referralTransaction($plan);
    //                 $order                 = new Order();
    //                 $order->order_id       = $orderID;
    //                 $order->name           = $user->name;
    //                 $order->card_number    = '';
    //                 $order->card_exp_month = '';
    //                 $order->card_exp_year  = '';
    //                 $order->plan_name      = $plan->name;
    //                 $order->plan_id        = $plan->id;
    //                 $order->price          = $request->amount;;
    //                 $order->price_currency = $admin_settings['currency'];
    //                 $order->payment_type   = __('Ozow');
    //                 $order->payment_status = __('successfull');
    //                 $order->receipt        = '';
    //                 $order->user_id        = $user->id;
    //                 $order->save();

    //                 $assignPlan = $user->assignPlan($plan->id);
    //                 // dd($assignPlan);
    //                 if ($assignPlan['is_success']) {
    //                     return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
    //                 } else {
    //                     return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
    //                 }
    //             } else {
    //                 return redirect()->route('plans')->with('error', __('Transaction has been failed.'));
    //             }
    //         } catch (\Exception $e) {
    //             return redirect()->route('plans')->with('error', __('Transaction has been failed.'));
    //         }
    //     } else {
    //         return redirect()->route('plans')->with('error', __('Plan is deleted.'));
    //     }
    // }

    public function invoicePayWithozow(Request $request)
    {

        $invoice_id        = Crypt::decrypt($request->invoice_id);
        $invoice           = Invoice::find($invoice_id);
        if (Auth::check()) {
            $user          = Auth::user();
        } else {
            $user          = User::where('id', $invoice->created_by)->first();
        }
        $get_amount = $request->amount; // Assuming $request->amount is a string
        // OR
        $amount_int = intval($get_amount); // Using intval() function

        $orderID           = strtoupper(str_replace('.', '', uniqid('', true)));
        $company_settings  = Utility::getCompanyPaymentSetting();


        if ($invoice) {
            if ($amount_int > $invoice->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {
                $siteCode       = isset($company_settings['ozow_site_key']) ? $company_settings['ozow_site_key'] : '';
                $privateKey     = isset($company_settings['ozow_private_key']) ? $company_settings['ozow_private_key'] : '';
                $apiKey         = isset($company_settings['ozow_api_key']) ? $company_settings['ozow_api_key'] : '';
                $isTest         = isset($company_settings['ozow_mode']) && $company_settings['ozow_mode'] == 'sandbox'  ? 'true' : 'false';

                $countryCode    = "ZA";
                $currencyCode   = $payment_setting['currency'] ?? 'ZAR';
                $amount         = $amount_int;

                $bankReference  = time() . 'FKU';
                $transactionReference = time();

                $cancelUrl      = route('invoice.get.ozow.status', [$invoice_id]);
                $errorUrl       = route('invoice.get.ozow.status', [$invoice_id]);
                $successUrl     = route('invoice.get.ozow.status', [$invoice_id]);
                $notifyUrl      = route('invoice.get.ozow.status', [$invoice_id]);

                // Calculate the hash with the exact same data being sent
                $inputString    = $siteCode . $countryCode . $currencyCode . $amount . $transactionReference . $bankReference . $cancelUrl . $errorUrl . $successUrl . $notifyUrl . $isTest . $privateKey;

                $hashCheck      = $this->generate_request_hash_check($inputString);

                $data = [
                    "countryCode"           => $countryCode,
                    "amount"                => $amount,
                    "transactionReference"  => $transactionReference,
                    "bankReference"         => $bankReference,
                    "cancelUrl"             => $cancelUrl,
                    "currencyCode"          => $currencyCode,
                    "errorUrl"              => $errorUrl,
                    "isTest"                => $isTest, // boolean value here is okay
                    "notifyUrl"             => $notifyUrl,
                    "siteCode"              => $siteCode,
                    "successUrl"            => $successUrl,
                    "hashCheck"             => $hashCheck,
                ];
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL             => 'https://api.ozow.com/postpaymentrequest',
                    CURLOPT_RETURNTRANSFER  => true,
                    CURLOPT_ENCODING        => '',
                    CURLOPT_MAXREDIRS       => 10,
                    CURLOPT_TIMEOUT         => 0,
                    CURLOPT_FOLLOWLOCATION  => true,
                    CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST   => 'POST',
                    CURLOPT_POSTFIELDS      => json_encode($data),
                    CURLOPT_HTTPHEADER      => array(
                        'Accept: application/json',
                        'ApiKey: ' . $apiKey,
                        'Content-Type: application/json'
                    ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                $json_attendance = json_decode($response, true);

                if (isset($json_attendance['url']) && $json_attendance['url'] != null) {
                    return redirect()->away($json_attendance['url']);
                } else {
                    if ($request->type == 'invoice') {
                        return redirect()->route('pay.invoice', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', $response['message'] ?? 'Something went wrong.');
                    } elseif ($request->type == 'retainer') {
                        return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($invoice_id))->with('error', $response['message'] ?? 'Something went wrong.');
                    }
                }
                return redirect()->back()->with('error', __('Unknown error occurred'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id)
    {
        $invoice        = Invoice::find($invoice_id);
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
        $get_amount     = $request->Amount;
        $orderID        = strtoupper(str_replace('.', '', uniqid('', true)));
        $setting = Utility::settingsById($invoice->created_by);
        if ($invoice) {
            try {
                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
                $payments = InvoicePayment::create(
                    [
                        'invoice_id' => $invoice->id,
                        'date' => date('Y-m-d'),
                        'amount' => $get_amount,
                        'account_id' => 0,
                        'payment_method' => 0,
                        'order_id' => $orderID,
                        'currency' => $setting['site_currency'] ?? '',
                        'payment_type' => __('Ozow'),
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
                $invoicePayment->amount      = $get_amount;
                $invoicePayment->date        = date('Y-m-d');
                $invoicePayment->payment_id  = $payments->id;
                $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
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

                if (Auth::check()) {
                    return redirect()->route('pay.invoice', Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                } else {
                    return redirect()->back()->with('success', __(' Payment successfully added.'));
                }
            } catch (Exception $e) {
                dd($e->getMessage());
                return redirect()->route('pay.invoice', Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
            }
        } else {
            if (Auth::check()) {
                return redirect()->route('pay.invoice', $request->invoice)->with('error', __('Invoice not found.'));
            } else {
                return redirect()->route('pay.invoice', encrypt($request->invoice))->with('success', __('Invoice not found.'));
            }
        }
    }

    public function retainerPayWithozow(Request $request)
    {
        $retainer_id = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer = Retainer::find($retainer_id);
        // $getAmount = $request->amount;
        $get_amount = $request->amount; // Assuming $request->amount is a string
        $amount_int = intval($get_amount); // Using intval() function

        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
        }

        $authuser = User::where('id', $user->id)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $setting = Utility::settingsById($retainer->created_by);
        $currency = isset($setting['site_currency']) ? $setting['site_currency'] : '';
        $get_amount = round($request->amount);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        if ($retainer) {

            $siteCode       = isset($payment_setting['ozow_site_key']) ? $payment_setting['ozow_site_key'] : '';
            $privateKey     = isset($payment_setting['ozow_private_key']) ? $payment_setting['ozow_private_key'] : '';
            $apiKey         = isset($payment_setting['ozow_api_key']) ? $payment_setting['ozow_api_key'] : '';
            $isTest         = isset($payment_setting['ozow_mode']) && $payment_setting['ozow_mode'] == 'sandbox'  ? 'true' : 'false';
            $countryCode    = "ZA";
            $currencyCode   = $setting['site_currency'] ?? 'ZAR';
            $amount         = $amount_int;

            $bankReference  = time() . 'FKU';
            $transactionReference = time();

            $cancelUrl      = route('retainer.get.ozow.status', [$retainer_id]);
            $errorUrl       = route('retainer.get.ozow.status', [$retainer_id]);
            $successUrl     = route('retainer.get.ozow.status', [$retainer_id]);
            $notifyUrl      = route('retainer.get.ozow.status', [$retainer_id]);

            // Calculate the hash with the exact same data being sent
            $inputString    = $siteCode . $countryCode . $currencyCode . $amount . $transactionReference . $bankReference . $cancelUrl . $errorUrl . $successUrl . $notifyUrl . $isTest . $privateKey;

            $hashCheck      = $this->generate_request_hash_check($inputString);

            $data = [
                "countryCode"           => $countryCode,
                "amount"                => $amount,
                "transactionReference"  => $transactionReference,
                "bankReference"         => $bankReference,
                "cancelUrl"             => $cancelUrl,
                "currencyCode"          => $currencyCode,
                "errorUrl"              => $errorUrl,
                "isTest"                => $isTest, // boolean value here is okay
                "notifyUrl"             => $notifyUrl,
                "siteCode"              => $siteCode,
                "successUrl"            => $successUrl,
                "hashCheck"             => $hashCheck,
            ];
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL             => 'https://api.ozow.com/postpaymentrequest',
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_ENCODING        => '',
                CURLOPT_MAXREDIRS       => 10,
                CURLOPT_TIMEOUT         => 0,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST   => 'POST',
                CURLOPT_POSTFIELDS      => json_encode($data),
                CURLOPT_HTTPHEADER      => array(
                    'Accept: application/json',
                    'ApiKey: ' . $apiKey,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);
            $json_attendance = json_decode($response, true);

            if (isset($json_attendance['url']) && $json_attendance['url'] != null) {
                return redirect()->away($json_attendance['url']);
            } else {
                return redirect()->route('pay.retainer', \Illuminate\Support\Facades\Crypt::encrypt($retainer_id))->with('error', $response['message'] ?? 'Something went wrong.');
            }
            return redirect()->back()->with('error', __('Unknown error occurred'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id)
    {
        // dd($request->all());
        $retainer        = Retainer::find($retainer_id);

        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
        }

        $get_amount     = $request->Amount;
        $orderID        = strtoupper(str_replace('.', '', uniqid('', true)));
        $settings = Utility::settingsById($retainer->created_by);
        $currency       = isset($settings['site_currency']) ? $settings['site_currency'] : '';

        if ($retainer) {
            if (isset($request['Status']) && $request['Status'] != 'Complete') {
                return redirect()->route('pay.retainerpay', $retainer_id)->with('error', __('Payment failed'));
            }

            $retainer_payment                  = new RetainerPayment();
            $retainer_payment->retainer_id     = $retainer_id;
            $retainer_payment->date            = Date('Y-m-d');
            $retainer_payment->amount          = $get_amount;
            $retainer_payment->account_id      = 0;
            $retainer_payment->payment_method  = 0;
            $retainer_payment->order_id        = $orderID;
            $retainer_payment->payment_type    = 'Ozow';
            $retainer_payment->receipt         = '';
            $retainer_payment->reference       = '';
            $retainer_payment->description     = 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id);
            $retainer_payment->save();

            if ($retainer->getDue() <= 0) {
                $retainer->status = 4;
                $retainer->save();
            } elseif (($retainer->getDue() - $retainer_payment->amount) == 0) {
                $retainer->status = 4;
                $retainer->save();
            } else {
                $retainer->status = 3;
                $retainer->save();
            }
            //for customer balance update
            Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

            //For Notification
            $setting  = Utility::settingsById($retainer->created_by);
            $customer = Customer::find($retainer->customer_id);
            $notificationArr = [
                'payment_price' => $request->amount,
                'retainer_payment_type' => 'Aamarpay',
                'customer_name' => $customer->name,
            ];
            //Slack Notification
            if (isset($settings['payment_notification']) && $settings['payment_notification'] == 1) {
                Utility::send_slack_msg('new_retainer_payment', $notificationArr, $retainer->created_by);
            }
            //Telegram Notification
            if (isset($settings['telegram_payment_notification']) && $settings['telegram_payment_notification'] == 1) {
                Utility::send_telegram_msg('new_retainer_payment', $notificationArr, $retainer->created_by);
            }
            //Twilio Notification
            if (isset($settings['twilio_payment_notification']) && $settings['twilio_payment_notification'] == 1) {
                Utility::send_twilio_msg($customer->contact, 'new_retainer_payment', $notificationArr, $retainer->created_by);
            }
            //webhook
            $module = 'New retainer Payment';
            $webhook =  Utility::webhookSetting($module, $retainer->created_by);
            if ($webhook) {
                $parameter = json_encode($retainer_payment);
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->route('retainer.link.copy', Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('pay.retainerpay', Crypt::encrypt($retainer_id))->with('success', __('Retainer paid Successfully!'));

        } else {
            return redirect()->route('pay.retainerpay', $retainer_id)->with('error', __('No reponse returned!'));
        }
    }
}
