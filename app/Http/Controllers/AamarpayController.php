<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Utility;
use App\Models\UserCoupon;
use App\Models\InvoicePayment;
use App\Models\RetainerPayment;
use App\Models\ProductCoupon;
use App\Models\Shipping;
use App\Models\User;
use App\Models\Order;
use App\Models\Retainer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use PhpParser\Node\Stmt\TryCatch;

class AamarpayController extends Controller
{

    function redirect_to_merchant($url)
    {

        $token = csrf_token();
?>
        <html xmlns="http://www.w3.org/1999/xhtml">

        <head>
            <script type="text/javascript">
                function closethisasap() {
                    document.forms["redirectpost"].submit();
                }
            </script>
        </head>

        <body onLoad="closethisasap();">

            <form name="redirectpost" method="post" action="<?php echo 'https://sandbox.aamarpay.com/' . $url; ?>">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
            </form>
        </body>

        </html>
<?php
        exit;
    }

    public function invoicepayWithAamarpay(Request $request, $invoice_id)
    {

        try {
            $invoice = Invoice::find($invoice_id);
            $setting = Utility::settingsById($invoice->created_by);
            $customer = Customer::find($invoice->customer_id);
            $url = 'https://sandbox.aamarpay.com/request.php';
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $aamarpay_store_id = $payment_setting['aamarpay_store_id'];
            $aamarpay_signature_key = $payment_setting['aamarpay_signature_key'];
            $aamarpay_description = $payment_setting['aamarpay_description'];
            $currency = $setting['site_currency'];
            // Utility::getValByName('site_currency'),

            if (Auth::check()) {
                $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
                $user     = \Auth::user();
            } else {
                $user = User::where('id', $invoice->created_by)->first();
                $settings = Utility::settingById($invoice->created_by);
            }


            $get_amount = $request->amount;

            $request->validate(['amount' => 'required|numeric|min:0']);

            try {
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $fields = array(
                    'store_id' => $aamarpay_store_id,
                    //store id will be aamarpay,  contact integration@aamarpay.com for test/live id
                    'amount' => $get_amount,
                    //transaction amount
                    'payment_type' => '',
                    //no need to change
                    'currency' => $currency,
                    //currenct will be USD/BDT
                    'tran_id' => $orderID,
                    //transaction id must be unique from your end
                    'cus_name' => $customer['name'],
                    //customer name
                    'cus_email' => $customer['email'],
                    //customer email address
                    'cus_add1' => '',
                    //customer address
                    'cus_add2' => '',
                    //customer address
                    'cus_city' => '',
                    //customer city
                    'cus_state' => '',
                    //state
                    'cus_postcode' => '',
                    //postcode or zipcode
                    'cus_country' => '',
                    //country
                    'cus_phone' => '1234567890',
                    //customer phone number
                    'success_url' => route('invoice.pay.aamarpay.success', Crypt::encrypt(['response' => 'success', 'invoice' => $invoice_id, 'amount' => $get_amount, 'order_id' => $orderID])),
                    //your success route
                    'fail_url' => route('invoice.pay.aamarpay.success', Crypt::encrypt(['response' => 'failure', 'invoice' => $invoice_id, 'amount' => $get_amount, 'order_id' => $orderID])),
                    //your fail route
                    'cancel_url' => route('invoice.pay.aamarpay.success', Crypt::encrypt(['response' => 'cancel'])),
                    //your cancel url
                    'signature_key' => $aamarpay_signature_key,
                    'desc' => $aamarpay_description,
                ); //signature key will provided aamarpay, contact integration@aamarpay.com for test/live signature key

                $fields_string = http_build_query($fields);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                curl_setopt($ch, CURLOPT_URL, $url);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $url_forward = str_replace('"', '', stripslashes(curl_exec($ch)));
                curl_close($ch);
                $this->redirect_to_merchant($url_forward);
            } catch (\Exception $e) {

                return redirect()->back()->with('error', $e);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceAamarpaysuccess($data, Request $request)
    {
        $data = Crypt::decrypt($data);
        $invoice = Invoice::find($data['invoice']);
        $getAmount = $data['amount'];
        $order_id = $data['order_id'];
        $setting = Utility::settingsById($invoice->created_by);
        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $invoice['created_by'])->first();
            $settings = Utility::settingById($invoice->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $objUser = $user;
        }

        if ($data['response'] == "success") {
            $payments = InvoicePayment::create(
                [
                    'invoice_id' => $invoice->id,
                    'date' => date('Y-m-d'),
                    'amount' => $getAmount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => $setting['site_currency'],
                    'txn_id' => $order_id,
                    'payment_type' => __('Aamarpay'),
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
            $invoicePayment->type        = 'Aamarpay';
            $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->id : $invoice->customer_id;
            $invoicePayment->payment_id  = $invoicePayment->id;
            $invoicePayment->category    = 'Invoice';
            $invoicePayment->amount      = $getAmount;
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
                    'payment_amount' => $getAmount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Aamarpay',
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
                return redirect()->route('invoice.show', \Crypt::encrypt($data['invoice']))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        } elseif ($data['response'] == "cancel") {
            return redirect()->back()->with('error', __('Your payment is cancel'));
        } else {
            return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
        }
    }

    public function retainerpayWithAamarpay(Request $request, $retainer_id)
    {

        try {
            $retainer = Retainer::find($retainer_id);
            $setting = Utility::settingsById($retainer->created_by);
            $customer = Customer::find($retainer->customer_id);
            $url = 'https://sandbox.aamarpay.com/request.php';
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $aamarpay_store_id = $payment_setting['aamarpay_store_id'];
            $aamarpay_signature_key = $payment_setting['aamarpay_signature_key'];
            $aamarpay_description = $payment_setting['aamarpay_description'];
            $currency = $setting['site_currency'];
            // Utility::getValByName('site_currency'),

            if (Auth::check()) {
                $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
                $user     = \Auth::user();
            } else {
                $user = User::where('id', $retainer->created_by)->first();
                $settings = Utility::settingById($retainer->created_by);
            }


            $get_amount = $request->amount;

            $request->validate(['amount' => 'required|numeric|min:0']);

            try {
                $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                $fields = array(
                    'store_id' => $aamarpay_store_id,
                    //store id will be aamarpay,  contact integration@aamarpay.com for test/live id
                    'amount' => $get_amount,
                    //transaction amount
                    'payment_type' => '',
                    //no need to change
                    'currency' => $currency,
                    //currenct will be USD/BDT
                    'tran_id' => $orderID,
                    //transaction id must be unique from your end
                    'cus_name' => $customer['name'],
                    //customer name
                    'cus_email' => $customer['email'],
                    //customer email address
                    'cus_add1' => '',
                    //customer address
                    'cus_add2' => '',
                    //customer address
                    'cus_city' => '',
                    //customer city
                    'cus_state' => '',
                    //state
                    'cus_postcode' => '',
                    //postcode or zipcode
                    'cus_country' => '',
                    //country
                    'cus_phone' => '1234567890',
                    //customer phone number
                    'success_url' => route('retainer.pay.aamarpay.success', Crypt::encrypt(['response' => 'success', 'retainer' => $retainer_id, 'amount' => $get_amount, 'order_id' => $orderID])),
                    //your success route
                    'fail_url' => route('retainer.pay.aamarpay.success', Crypt::encrypt(['response' => 'failure', 'retainer' => $retainer_id, 'amount' => $get_amount, 'order_id' => $orderID])),
                    //your fail route
                    'cancel_url' => route('retainer.pay.aamarpay.success', Crypt::encrypt(['response' => 'cancel'])),
                    //your cancel url
                    'signature_key' => $aamarpay_signature_key,
                    'desc' => $aamarpay_description,
                ); //signature key will provided aamarpay, contact integration@aamarpay.com for test/live signature key

                $fields_string = http_build_query($fields);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                curl_setopt($ch, CURLOPT_URL, $url);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $url_forward = str_replace('"', '', stripslashes(curl_exec($ch)));
                curl_close($ch);
                $this->redirect_to_merchant($url_forward);
            } catch (\Exception $e) {

                return redirect()->back()->with('error', $e);
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerAamarpaysuccess($data, Request $request)
    {
        $data = Crypt::decrypt($data);
        $retainer = Retainer::find($data['retainer']);
        $getAmount = $data['amount'];
        $order_id = $data['order_id'];
        $setting = Utility::settingsById($invoice->created_by);
        if (Auth::check()) {
            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $retainer['created_by'])->first();
            $settings = Utility::settingById($retainer->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $objUser = $user;
        }

        if ($data['response'] == "success") {
            $payments = RetainerPayment::create(
                [

                    'retainer_id' => $retainer->id,
                    'date' => date('Y-m-d'),
                    'amount' => $getAmount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => $setting['site_currency'],
                    'txn_id' => $order_id,
                    'payment_type' => __('Aamarpay'),
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
            $retainerPayment->type        = 'Aamarpay';
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
                    'retainer_id' => $payments->id,
                    'payment_name' => $customer->name,
                    'payment_amount' => $getAmount,
                    'payment_date' => $objUser->dateFormat($request->date),
                    'type' => 'Aamarpay',
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
            if (Auth::check()) {
                return redirect()->route('retainer.show', \Crypt::encrypt($data['retainer']))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        } elseif ($data['response'] == "cancel") {
            return redirect()->back()->with('error', __('Your payment is cancel'));
        } else {
            return redirect()->back()->with('error', __('Your Transaction is fail please try again'));
        }
    }
}
