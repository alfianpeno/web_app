<?php

namespace App\Http\Controllers;

use App\Khalti\Khalti;
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
use Illuminate\Http\Request;

class KhaltiPaymentController extends Controller
{
    public function planPayWithKhalti(Request $request)
    {
        $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        $user            = \Auth::user();
        $currency        = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $planID          = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);

        $plan = Plan::find($planID);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $stripe_session = '';
        if ($plan) {
            $get_amount               = $plan->price;
            if (!empty($request->coupon_code)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon_code))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun     = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;

                    $get_amount = $plan->price - $discount_value;
                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                    if ($get_amount <= 0) {
                        $authuser = Auth::user();
                        $authuser->plan = $plan->id;
                        $authuser->save();
                        $assignPlan = $authuser->assignPlan($plan->id);
                        if ($assignPlan['is_success'] == true && !empty($plan)) {
                            if (!empty($authuser->payment_subscription_id) && $authuser->payment_subscription_id != '') {
                                try {
                                    $authuser->cancel_subscription($authuser->id);
                                } catch (\Exception $exception) {
                                    \Log::debug($exception->getMessage());
                                }
                            }
                            $orderID               = strtoupper(str_replace('.', '', uniqid('', true)));
                            $userCoupon            = new UserCoupon();
                            $userCoupon->user      = $authuser->id;
                            $userCoupon->coupon    = $coupons->id;
                            $userCoupon->order     = $orderID;
                            $userCoupon->save();
                            Order::create(
                                [
                                    'order_id' => $orderID,
                                    'name' => null,
                                    'email' => null,
                                    'card_number' => null,
                                    'card_exp_month' => null,
                                    'card_exp_year' => null,
                                    'plan_name' => $plan->name,
                                    'plan_id' => $plan->id,
                                    'price' => $get_amount == null ? 0 : $get_amount,
                                    'price_currency' => $currency,
                                    'txn_id' => '',
                                    'payment_type' => 'Authorizenet',
                                    'payment_status' => 'Success',
                                    'receipt' => null,
                                    'user_id' => $authuser->id,
                                    'frequency' => $frequency,
                                ]
                            );
                            $assignPlan = $authuser->assignPlan($plan->id);

                            // return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                            return $get_amount;
                        }
                    }
                } else {
                    // return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                    return response()->json([
                        'success' => true, 'inputs' => __('Something into warning.'),
                    ]);
                }
            }

            try {
                $secret     = !empty($admin_settings['khalti_secret_key']) ? $admin_settings['khalti_secret_key'] : '';

                $amount     = $get_amount;
                return $amount;
            } catch (\Exception $e) {
                \Log::debug($e->getMessage());
                return redirect()->route('plan.index')->with('error', __('Plan is deleted.'));
            }

        } else {
            return redirect()->route('plan.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planGetKhaltiStatus(Request $request)
    {
        $planID     = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
        $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));

        $admin_settings = Utility::getCompanyPaymentSetting($invoice->created_by);
        $plan       = Plan::find($planID);
        $user       = User::find(\Auth::user()->id);
        if ($plan) {
            $price               = $plan->price;

            if ($request->coupon_code) {
                $coupons = Coupon::where('code', strtoupper($request->coupon_code))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun     = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;

                    $price  = $plan->price - $discount_value;

                    $userCoupon = new UserCoupon();
                    $userCoupon->user = $user->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
                    $userCoupon->save();
                    $usedCoupun = $coupons->used_coupon();
                    if ($coupons->limit <= $usedCoupun) {
                        $coupons->is_active = 0;
                        $coupons->save();
                    }
                }
            }

            $payload    = $request->payload;
            $secret     = !empty($admin_settings['khalti_secret_key']) ? $admin_settings['khalti_secret_key'] : '';
            $currency   = isset($admin_settings['currency']) ? $admin_settings['currency'] : 'USD';
            $token      = $payload['token'];
            $amount     = $payload['amount'];
            $khalti     = new Khalti();
            $response   = $khalti->verifyPayment($secret, $token, $amount);

            try {
                if ($response['status_code'] == '200') {
                    $product = !empty($plan->name) ? $plan->name : 'Basic Package';
                    Utility::referralTransaction($plan);
                    $order =
                        Order::create(
                            [
                                'order_id' => $orderID,
                                'name' => $user->name ?? '',
                                'email' => $user->email ?? '',
                                'card_number' => null,
                                'card_exp_month' => null,
                                'card_exp_year' => null,
                                'plan_name' => $plan->name,
                                'plan_id' => $plan->id,
                                'price' => $amount == null ? 0 : $amount,
                                'price_currency' => $currency,
                                'txn_id' => '',
                                'payment_type' => __('Authorizenet'),
                                'payment_status' => 'Success',
                                'receipt' => null,
                                'user_id' => $user->id,
                            ]
                        );
                    $user       = User::find($user->id);
                    $assignPlan = $user->assignPlan($plan->id);
                    if ($assignPlan['is_success']) {
                        return $response;
                    } else {
                        return redirect()->route('plan.index')->with('error', __($assignPlan['error']) ?? 'Something went wrong');
                    }
                } else {
                    return redirect()->route('plan.index')->with('error', __('Transaction has been failed.'));
                }
            } catch (\Exception $e) {
                return response()->json('failed');
            }
        } else {
            return response()->json('deleted');
        }
    }

    public function invoicePayWithKhalti(Request $request)
    {
        $invoice_id = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice = Invoice::find($invoice_id);
        $getAmount = $request->amount;
        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
        }

        $authuser = User::where('id', $user->id)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $get_amount = round($request->amount);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($invoice) {
            $invoiceID = $request->invoice_id;
            $get_amount = $request->amount;
            $type = $request->type;
            $authuser = User::where('id', $user->id)->first();
            $data = [
                'invoiceID'     =>  $invoiceID,
                'user_id'       =>  $user->id,
                'get_amount'    =>  $get_amount,
                'type'          =>  $type,
                'authuser'      =>  $authuser->id,
            ];

            $data  =    json_encode($data);

            try {
                // return view('AuthorizeNet.invoice', compact('invoice', 'get_amount', 'authuser', 'data', 'currency'));
                return $get_amount;
            } catch (\Exception $e) {
                dd($e);
                \Log::error($e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', 'Invoice not found.');
        }
    }

    public function getInvoicePaymentStatus(Request $request)
    {
        $invoice_id     =   \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice        = Invoice::find($invoice_id);

        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
        }
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $settings= Utility::settingsById($invoice->created_by);
        $company_payment_setting = Utility::getCompanyPaymentSetting($user->id);

        $payload    = $request->payload;
        $secret     = !empty($company_payment_setting['khalti_secret_key']) ? $company_payment_setting['khalti_secret_key'] : '';
        $currency   = isset($company_payment_setting['currency']) ? $company_payment_setting['currency'] : 'USD';
        $token      = $payload['token'];
        $amount     = $payload['amount'];
        $khalti     = new Khalti();
        $response   = $khalti->verifyPayment($secret, $token, $amount);

        if ($response['status_code'] == '200') {
            if (!empty($invoice_id)) {
                $invoice        =  Invoice::find($invoice_id);

                $invoice_payment                 = new InvoicePayment();
                $invoice_payment->invoice_id     = $invoice_id;
                $invoice_payment->date           = Date('Y-m-d');
                $invoice_payment->amount         = $amount;
                $invoice_payment->account_id         = 0;
                $invoice_payment->payment_method         = 0;
                $invoice_payment->order_id      =$orderID;
                $invoice_payment->payment_type   = 'Khalti';
                $invoice_payment->receipt     = '';
                $invoice_payment->reference     = '';
                $invoice_payment->description     = 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id);
                $invoice_payment->save();

                if($invoice->getDue() <= 0)
                {
                    $invoice->status = 4;
                    $invoice->save();
                }
                elseif(($invoice->getDue() - $invoice_payment->amount) == 0)
                {
                    $invoice->status = 4;
                    $invoice->save();
                }
                else
                {
                    $invoice->status = 3;
                    $invoice->save();
                }

                //for customer balance update
                Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                //For Notification
                $setting  = Utility::settingsById($invoice->created_by);
                $customer = Customer::find($invoice->customer_id);
                $notificationArr = [
                        'payment_price' => $request->amount,
                        'invoice_payment_type' => 'Aamarpay',
                        'customer_name' => $customer->name,
                    ];
                //Slack Notification
                if(isset($settings['payment_notification']) && $settings['payment_notification'] ==1)
                {
                    Utility::send_slack_msg('new_invoice_payment', $notificationArr,$invoice->created_by);
                }
                //Telegram Notification
                if(isset($settings['telegram_payment_notification']) && $settings['telegram_payment_notification'] == 1)
                {
                    Utility::send_telegram_msg('new_invoice_payment', $notificationArr,$invoice->created_by);
                }
                //Twilio Notification
                if(isset($settings['twilio_payment_notification']) && $settings['twilio_payment_notification'] ==1)
                {
                    Utility::send_twilio_msg($customer->contact,'new_invoice_payment', $notificationArr,$invoice->created_by);
                }
                //webhook
                $module ='New Invoice Payment';
                $webhook=  Utility::webhookSetting($module,$invoice->created_by);
                if($webhook)
                {
                    $parameter = json_encode($invoice_payment);
                    $status = Utility::WebhookCall($webhook['url'],$parameter,$webhook['method']);
                    if($status == true)
                    {
                        return redirect()->route('invoice.link.copy', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
                    }
                    else
                    {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                // return redirect()->route('pay.invoice', \Crypt::encrypt($invoice_id))->with('success', __('Invoice paid Successfully!'));
                return $response;
            } else {
                return redirect()->route('pay.invoice', $invoice_id)->with('error', __('Oops something went wrong.'));
            }
        } else {
            return redirect()->route('pay.invoice', $invoice_id)->with('error', __('No reponse returned!'));
        }
    }

    public function retainerPayWithKhalti(Request $request)
    {
        $retainer_id = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer = Retainer::find($retainer_id);
        $getAmount = $request->amount;
        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
        }

        $authuser = User::where('id', $user->id)->first();
        $payment_setting = Utility::getCompanyPaymentSetting($user->id);
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $get_amount = round($request->amount);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        if ($retainer) {
            $retainerID = $request->retainer_id;
            $get_amount = $request->amount;
            $type = $request->type;
            $authuser = User::where('id', $user->id)->first();
            $data = [
                'retainerID'     =>  $retainerID,
                'user_id'       =>  $user->id,
                'get_amount'    =>  $get_amount,
                'type'          =>  $type,
                'authuser'      =>  $authuser->id,
            ];

            $data  =    json_encode($data);

            try {
                // return view('AuthorizeNet.invoice', compact('invoice', 'get_amount', 'authuser', 'data', 'currency'));
                return $get_amount;
            } catch (\Exception $e) {
                dd($e);
                \Log::error($e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', 'Retainer not found.');
        }
    }

    public function getRetainerPaymentStatus(Request $request)
    {
        $retainer_id     =   \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);
        $retainer        = Retainer::find($retainer_id);

        if (\Auth::check()) {
            $user = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
        }
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $settings= Utility::settingsById($retainer->created_by);
        $company_payment_setting = Utility::getCompanyPaymentSetting($user->id);

        $payload    = $request->payload;
        $secret     = !empty($company_payment_setting['khalti_secret_key']) ? $company_payment_setting['khalti_secret_key'] : '';
        $currency   = isset($company_payment_setting['currency']) ? $company_payment_setting['currency'] : 'USD';
        $token      = $payload['token'];
        $amount     = $payload['amount'];
        $khalti     = new Khalti();
        $response   = $khalti->verifyPayment($secret, $token, $amount);

        if ($response['status_code'] == '200') {
            if (!empty($retainer_id)) {
                $retainer        =  Retainer::find($retainer_id);

                $retainer_payment                 = new RetainerPayment();
                $retainer_payment->retainer_id     = $retainer_id;
                $retainer_payment->date           = Date('Y-m-d');
                $retainer_payment->amount         = $amount;
                $retainer_payment->account_id         = 0;
                $retainer_payment->payment_method         = 0;
                $retainer_payment->order_id      =$orderID;
                $retainer_payment->payment_type   = 'Khalti';
                $retainer_payment->receipt     = '';
                $retainer_payment->reference     = '';
                $retainer_payment->description     = 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                $retainer_payment->save();

                if($retainer->getDue() <= 0)
                {
                    $retainer->status = 4;
                    $retainer->save();
                }
                elseif(($retainer->getDue() - $retainer_payment->amount) == 0)
                {
                    $retainer->status = 4;
                    $retainer->save();
                }
                else
                {
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
                if(isset($settings['payment_notification']) && $settings['payment_notification'] ==1)
                {
                    Utility::send_slack_msg('new_retainer_payment', $notificationArr,$retainer->created_by);
                }
                //Telegram Notification
                if(isset($settings['telegram_payment_notification']) && $settings['telegram_payment_notification'] == 1)
                {
                    Utility::send_telegram_msg('new_retainer_payment', $notificationArr,$retainer->created_by);
                }
                //Twilio Notification
                if(isset($settings['twilio_payment_notification']) && $settings['twilio_payment_notification'] ==1)
                {
                    Utility::send_twilio_msg($customer->contact,'new_retainer_payment', $notificationArr,$retainer->created_by);
                }
                //webhook
                $module ='New retainer Payment';
                $webhook=  Utility::webhookSetting($module,$retainer->created_by);
                if($webhook)
                {
                    $parameter = json_encode($retainer_payment);
                    $status = Utility::WebhookCall($webhook['url'],$parameter,$webhook['method']);
                    if($status == true)
                    {
                        return redirect()->route('retainer.link.copy', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
                    }
                    else
                    {
                        return redirect()->back()->with('error', __('Webhook call failed.'));
                    }
                }

                // return redirect()->route('pay.retainerpay', \Crypt::encrypt($retainer_id))->with('success', __('Retainer paid Successfully!'));
                return $response;
            } else {
                return redirect()->route('pay.retainerpay', $retainer_id)->with('error', __('Oops something went wrong.'));
            }
        } else {
            return redirect()->route('pay.retainerpay', $retainer_id)->with('error', __('No reponse returned!'));
        }
    }
}
