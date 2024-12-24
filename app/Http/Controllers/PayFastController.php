<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\InvoicePayment;
use App\Models\UserCoupon;
use App\Models\Utility;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;


class PayFastController extends Controller
{
    protected $invoiceData, $payfast_merchant_id, $payfast_merchant_key, $payfast_signature, $payfast_mode, $is_enabled;
    public function paymentConfig()
    {
        if (\Auth::check()) {
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting($this->invoiceData->created_by);
        }

        // if ($payment_setting['payfast_mode'] == 'live') {
        //     config([
        //         'payfast.live.merchant_id' => isset($payment_setting['payfast_merchant_id']) ? $payment_setting['payfast_merchant_id'] : '',
        //         'payfast.live.merchant_key' => isset($payment_setting['payfast_merchant_key']) ? $payment_setting['payfast_merchant_key'] : '',
        //         'payfast.live.payfast_signature' => isset($payment_setting['payfast_signature']) ? $payment_setting['payfast_signature'] : '',
        //         'payfast.mode' => isset($payment_setting['payfast_mode']) ? $payment_setting['payfast_mode'] : '',
        //     ]);
        // } else {
        //     config([
        //         'payfast.live.merchant_id' => isset($payment_setting['payfast_merchant_id']) ? $payment_setting['payfast_merchant_id'] : '',
        //         'payfast.live.merchant_key' => isset($payment_setting['payfast_merchant_key']) ? $payment_setting['payfast_merchant_key'] : '',
        //         'payfast.live.payfast_signature' => isset($payment_setting['payfast_signature']) ? $payment_setting['payfast_signature'] : '',
        //         'payfast.mode' => isset($payment_setting['payfast_mode']) ? $payment_setting['payfast_mode'] : '',
        //     ]);
        // }

        $this->payfast_merchant_id = isset($payment_setting['payfast_merchant_id']) ? $payment_setting['payfast_merchant_id'] : '';
        $this->payfast_merchant_key = isset($payment_setting['payfast_merchant_key']) ? $payment_setting['payfast_merchant_key'] : '';
        $this->payfast_signature = isset($payment_setting['payfast_signature']) ? $payment_setting['payfast_signature'] : '';
        $this->payfast_mode = isset($payment_setting['payfast_mode']) ? $payment_setting['payfast_mode'] : 'off';
        $this->is_enabled = isset($payment_setting['is_payfast_enabled']) ? $payment_setting['is_payfast_enabled'] : 'off';

        return $this;
    }

    public function index(Request $request)
    {
        if (Auth::check()) {
            // $payment_setting = Utility::getCompanyPaymentSetting();
            // $planID = Crypt::decrypt($request->plan_id);
            // $plan = Plan::find($planID);

            $planID = Crypt::decrypt($request->plan_id);

    $plan                 = Plan::find($planID);

    // $user      = User::find($plan->created_by);

    $settings=Utility::settingsById($plan->created_by);

    $this->invoiceData =$plan;
    $admin = Utility::getCompanyPaymentSetting($invoice->created_by);

    $payment_setting   = $this->paymentConfig();


            if ($plan) {

                $plan_amount = $plan->price;

                $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
                $user = Auth::user();

                if ($request->coupon_code != null) {
                    $coupons = Coupon::where('code', $request->coupon_code)->first();

                    if (!empty($coupons)) {
                        $userCoupon = new UserCoupon();
                        $userCoupon->user = $user->id;
                        $userCoupon->coupon = $coupons->id;
                        $userCoupon->order = $order_id;
                        $userCoupon->save();
                        $usedCoupun = $coupons->used_coupon();
                        if ($coupons->limit <= $usedCoupun) {
                            $coupons->is_active = 0;
                            $coupons->save();
                        }

                        $plan_amount = $request->coupon_amount;
                    }
                }

                if ($plan_amount < 1) {
                    $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                    $user = Auth::user();
                    $order                 = new Order();
                    $order->order_id       = $orderID;
                    $order->name           = $user->name;
                    $order->card_number    = '';
                    $order->card_exp_month = '';
                    $order->card_exp_year  = '';
                    $order->plan_name      = $plan->name;
                    $order->plan_id        = $plan->id;
                    $order->price          = $plan_amount;
                    $order->price_currency = $admin['currency'] ? $admin['currency'] : 'USD';
                    $order->txn_id         = '';
                    $order->payment_type   = __('PayFast');
                    $order->payment_status = 'success';
                    $order->receipt        = '';
                    $order->user_id        = $user->id;

                    $order->save();

                    $assignPlan = $user->assignPlan($plan->id);

                    if ($assignPlan['is_success']) {
                        return response()->json(['success' => __('Plan activated Successfully.')]);
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                }

                $success = Crypt::encrypt([
                    'plan' => $plan->toArray(),
                    'order_id' => $order_id,
                    'plan_amount' => $plan_amount
                ]);

                $data = array(
                    // Merchant details
                    'merchant_id' => !empty($payment_setting->payfast_merchant_id) ? $payment_setting->payfast_merchant_id : '',
                    'merchant_key' => !empty($payment_setting->payfast_merchant_key) ? $payment_setting->payfast_merchant_key : '',
                    'return_url' => route('payfast.payment.success', $success),
                    'cancel_url' => route('plans.index'),
                    'notify_url' => route('plans.index'),
                    // Buyer details
                    'name_first' => $user->name,
                    'name_last' => '',
                    'email_address' => $user->email,
                    // Transaction details
                    'm_payment_id' => $order_id, //Unique payment ID to pass through to notify_url
                    'amount' => number_format(sprintf('%.2f', $plan_amount), 2, '.', ''),
                    'item_name' => $plan->name,
                );

                $passphrase = !empty($payment_setting->payfast_signature) ? $payment_setting->payfast_signature : '';

                $signature = $this->generateSignature($data, $passphrase);
                $data['signature'] = $signature;

                $htmlForm = '';

                foreach ($data as $name => $value) {
                    $htmlForm .= '<input name="' . $name . '" type="hidden" value=\'' . $value . '\' />';
                }

                return response()->json([
                    'success' => true,
                    'inputs' => $htmlForm,
                ]);
            }
        }
    }

    public function generateSignature($data, $passPhrase = null)
    {

        $pfOutput = '';
        foreach ($data as $key => $val) {
            if ($val !== '') {
                $pfOutput .= $key . '=' . urlencode(trim($val)) . '&';
            }
        }

        $getString = substr($pfOutput, 0, -1);
        if ($passPhrase !== null) {
            $getString .= '&passphrase=' . urlencode(trim($passPhrase));
        }
        return md5($getString);
    }

    public function success($success)
    {
        $admin = Utility::getCompanyPaymentSetting($invoice->created_by);
        try {
            $user = Auth::user();
            $data = Crypt::decrypt($success);

            $order = new Order();
            $order->order_id = $data['order_id'];
            $order->name = $user->name;
            $order->card_number = '';
            $order->card_exp_month = '';
            $order->card_exp_year = '';
            $order->plan_name = $data['plan']['name'];
            $order->plan_id = $data['plan']['id'];
            $order->price = $data['plan_amount'];
            $order->price_currency =  $admin['currency'] ? $admin['currency'] : 'USD';
            $order->txn_id = $data['order_id'];
            $order->payment_type = __('PayFast');
            $order->payment_status = 'success';
            $order->txn_id = '';
            $order->receipt = '';
            $order->user_id = $user->id;
            $order->save();
            $assignPlan = $user->assignPlan($data['plan']['id']);

            if ($assignPlan['is_success']) {
                return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
            } else {
                return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
            }
        } catch (Exception $e) {
            return redirect()->route('plans.index')->with('error', __($e));
        }
    }

    public function invoicePayWithPayFast(Request $request)
    {
        $invoiceID = Crypt::decrypt($request->invoice_id);
        $invoice                 = Invoice::find($invoiceID);
        $user      = User::find($invoice->created_by);
        $settings= Utility::settingsById($invoice->created_by);

        $this->invoiceData =$invoice;

        $payment_setting   = $this->paymentConfig();


        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
        }
        $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
        $get_amount = $request->amount;
        $success = Crypt::encrypt([
            'invoice' => $invoice->id,
            'order_id' => $order_id,
            'invoice_amount' => $get_amount
        ]);

        $data = array(
            // Merchant details
            'merchant_id' => !empty($payment_setting->payfast_merchant_id) ? $payment_setting->payfast_merchant_id : '',
            'merchant_key' => !empty($payment_setting->payfast_merchant_key) ? $payment_setting->payfast_merchant_key : '',
            'return_url' => route('invoice.payfast.status', $success),
            // 'cancel_url' => route('invoice.show'),
            // 'notify_url' => route('invoice.show'),
            // Buyer details
            'name_first' => $user->name,
            'name_last' => '',
            'email_address' => $user->email,
            // Transaction details
            'm_payment_id' => $order_id, // Unique payment ID to pass through to notify_url
            'amount' => number_format(sprintf('%.2f', $get_amount), 2, '.', ''),
            'item_name' => $user->name,
        );

        $passphrase = !empty($payment_setting->payfast_signature) ? $payment_setting->payfast_signature : '';

        $signature = $this->generateSignature($data, $passphrase);
        $data['signature'] = $signature;

        $htmlForm = '';

        foreach ($data as $name => $value) {
            $htmlForm .= '<input name="' . $name . '" type="hidden" value=\'' . $value . '\' />';
        }

        return response()->json([
            'success' => true,
            'inputs' => $htmlForm,
        ]);
    }

    public function invoicepayfaststatus(Request $request, $success)
    {
        $data = Crypt::decrypt($success);
        $invoice                 = Invoice::find($data['invoice']);
        $setting = Utility::settingsById($invoice->created_by);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $objUser = $user;
            //            $this->non_auth_setApiContext($invoice->created_by);
        }



        // $payment_id = \Session::get('PayerID');

        // \Session::forget('PayerID');

        if (empty($request->PayerID || empty($request->token))) {
            return redirect()->back()->with('error', __('Payment failed'));
        }

        try {

            $payments = InvoicePayment::create(
                [
                    'invoice_id' => $invoice->id,
                    'date' => date('Y-m-d'),
                    'amount' => $data['invoice_amount'],
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' =>  $data['order_id'],
                    'currency' => $setting['site_currency'],
                    'txn_id' =>  $data['order_id'],
                    'payment_type' => __('Payfast'),
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
            $invoicePayment->type        = 'PayFast';
            $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->id : $invoice->customer_id;
            $invoicePayment->payment_id  = $invoicePayment->id;
            $invoicePayment->category    = 'Invoice';
            $invoicePayment->amount      = $data['invoice_amount'];
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

            if(isset($setting['payment_notification']) && $setting['payment_notification'] ==1)
            {
                $uArr = [
                    'invoice_id' => $invoice->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $data['invoice_amount'],
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Payfast',
                    'user_name' => $objUser->name,
                ];


                Utility::send_twilio_msg($customer->contact,'new_payment', $uArr, $invoice->created_by);
            }

             // webhook
             $module = 'New Payment';

             $webhook =  Utility::webhookSetting($module,$invoice->created_by);

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

    public function retainerPayWithPayFast(Request $request)
    {

        $retainerID = Crypt::decrypt($request->retainer_id);

        $retainer                 = Retainer::find($retainerID);

        $user      = User::find($retainer->created_by);

        $settings= Utility::settingsById($retainer->created_by);

        $this->invoiceData = $retainer;

        $payment_setting   = $this->paymentConfig();


        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
        }
        $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
        $get_amount = $request->amount;

        $success = Crypt::encrypt([
            'retainer' => $retainer->id,
            'order_id' => $order_id,
            'retainer_amount' => $get_amount
        ]);


        $data = array(
            // Merchant details
            'merchant_id' => !empty($payment_setting->payfast_merchant_id) ? $payment_setting->payfast_merchant_id : '',
            'merchant_key' => !empty($payment_setting->payfast_merchant_key) ? $payment_setting->payfast_merchant_key : '',
            'return_url' => route('retainer.payfast.status', $success),
            // 'cancel_url' => route('invoice.show'),
            // 'notify_url' => route('invoice.show'),
            // Buyer details
            'name_first' => $user->name,
            'name_last' => '',
            'email_address' => $user->email,
            // Transaction details
            'm_payment_id' => $order_id, // Unique payment ID to pass through to notify_url
            'amount' => number_format(sprintf('%.2f', $get_amount), 2, '.', ''),
            'item_name' => $user->name,
        );


        $passphrase = !empty($payment_setting->payfast_signature) ? $payment_setting->payfast_signature : '';


        $signature = $this->generateSignature($data, $passphrase);

        $data['signature'] = $signature;


        $htmlForm = '';

        foreach ($data as $name => $value) {
            $htmlForm .= '<input name="' . $name . '" type="hidden" value=\'' . $value . '\' />';
        }

        return response()->json([
            'success' => true,
            'inputs' => $htmlForm,
        ]);
    }

    public function retainerpayfaststatus(Request $request, $success)
    {

        $data = Crypt::decrypt($success);
        $retainer                 = Retainer::find($data['retainer']);
        $setting = Utility::settingsById($retainer->created_by);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $objUser = $user;
            //            $this->non_auth_setApiContext($invoice->created_by);
        }



        // $payment_id = \Session::get('PayerID');

        // \Session::forget('PayerID');

        if (empty($request->PayerID || empty($request->token))) {
            return redirect()->back()->with('error', __('Payment failed'));
        }

        try {

            $payments = RetainerPayment::create(
                [
                    'invoice_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    'amount' => $data['retainer_amount'],
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' =>  $data['order_id'],
                    'currency' => $setting['site_currency'],
                    'txn_id' =>  $data['order_id'],
                    'payment_type' => __('Payfast'),
                    'receipt' => '',
                    'reference' => '',
                    'description' => 'Invoice ' . Utility::invoiceNumberFormat($settings, $retainer->invoice_id),
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
            $retainerPayment->type        = 'PayFast';
            $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
            $retainerPayment->payment_id  = $retainerPayment->id;
            $retainerPayment->category    = 'Retainer';
            $retainerPayment->amount      = $data['retainer_amount'];
            $retainerPayment->date        = date('Y-m-d');
            $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
            $retainerPayment->payment_id  = $payments->id;
            $retainerPayment->description = 'Retainer ' . Utility::invoiceNumberFormat($settings, $retainer->retainer_id);
            $retainerPayment->account     = 0;
            \App\Models\Transaction::addTransaction($retainerPayment);

            Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

            Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

            // Twilio
            $setting  = Utility::settingsById($objUser->creatorId());
            $customer = Customer::find($retainer->customer_id);

            if(isset($setting['payment_notification']) && $setting['payment_notification'] ==1)
            {
                $uArr = [
                    'retainer_id' => $retainer->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $data['retainer_amount'],
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Payfast',
                    'user_name' => $objUser->name,
                ];


                Utility::send_twilio_msg($customer->contact,'new_payment', $uArr, $retainer->created_by);
            }

            // webhook
            $module = 'New Payment';

            $webhook =  Utility::webhookSetting($module,$retainer->created_by);

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
                return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been complted.'));
            }
        }
    }

}
