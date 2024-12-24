<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillPayment;
use App\Models\Customer;
use App\Models\Mail\BillPaymentCreate;
use App\Models\Mail\SendWorkspaceInvication;
use App\Models\Payment;
use App\Models\ProductServiceCategory;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\Vender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Exports\PaymentExport;
use App\Models\BillAccount;
use App\Models\ChartOfAccount;
use App\Models\TransactionLines;
use Maatwebsite\Excel\Facades\Excel;


class PaymentController extends Controller
{

    public function index(Request $request)
    {
        if (\Auth::user()->can('manage payment')) {
            $vender = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $vender->prepend('Select Vender', '');

            $account = BankAccount::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('holder_name', 'id');
            $account->prepend('Select Account', '');

            $category = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->where('type', '=', 'expense')->get()->pluck('name', 'id');
            $category->prepend('Select Category', '');


            $query = Payment::where('created_by', '=', \Auth::user()->creatorId());

            if (str_contains($request->date, ' to ')) {
                $date_range = explode(' to ', $request->date);
                $query->whereBetween('date', $date_range);
            } elseif (!empty($request->date)) {

                $query->where('date', $request->date);
            }

            // if(!empty($request->date))
            // {
            //     $date_range = explode(' to ', $request->date);
            //     $query->whereBetween('date', $date_range);
            // }

            if (!empty($request->vender)) {
                $query->where('id', '=', $request->vender);
            }
            if (!empty($request->account)) {
                $query->where('account_id', '=', $request->account);
            }

            if (!empty($request->category)) {
                $query->where('category_id', '=', $request->category);
            }


            $payments = $query->get();


            return view('payment.index', compact('payments', 'account', 'category', 'vender'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create payment')) {
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('--', 0);
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->whereNotIn('type', ['product & service', 'income'])->get()->pluck('name', 'id');
            $accounts   = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('payment.create', compact('venders', 'categories', 'accounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {

        if (\Auth::user()->can('create payment')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                    'category_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $payment                 = new Payment();
            $payment->date           = $request->date;
            $payment->amount         = $request->amount;
            $payment->account_id     = $request->account_id;
            $payment->vender_id      = $request->vender_id;
            $payment->category_id    = $request->category_id;
            $payment->payment_method = 0;
            $payment->reference      = $request->reference;
            $payment->description    = $request->description ?? '-';
            if (!empty($request->add_receipt)) {

                $image_size = $request->file('add_receipt')->getSize();

                // $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                // if ($result == 1) {
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    // $request->add_receipt->storeAs('uploads/payment', $fileName);
                    $payment->add_receipt = $fileName;

                    $dir        = 'uploads/payment';
                    $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);

                    if ($path['flag'] == 0) {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                // }
                // else {
                //     return redirect()->back()->with('error', $result);
                // }
                // $request->add_receipt  = $path['url'];
                // $payment->save();

            }
            $payment->created_by     = \Auth::user()->creatorId();
            $payment->save();

            $chartAccountId = ChartOfAccount::find($request->account_id);
//            dd($chartAccountId);

            $accountId = BankAccount::find($payment->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $payment->amount,
                'reference' => 'Payment',
                'reference_id' => $payment->id,
                'reference_sub_id' => 0,
                'date' => $payment->date,
            ];
            Utility::addTransactionLines($data);

            $account = new BillAccount();
            $account->chart_account_id = $chartAccountId;
            $account->price = $request->amount;
            $account->description = $request->description;
            $account->type = 'Payment';
            $account->ref_id = $payment->id;
            $account->save();

            $category            = ProductServiceCategory::where('id', $request->category_id)->first();
            $payment->payment_id = $payment->id;
            $payment->type       = 'Payment';
            $payment->category   = $category->name;
            $payment->user_id    = $payment->vender_id;
            $payment->user_type  = 'Vender';
            $payment->account    = $request->account_id;

            Transaction::addTransaction($payment);

            $vender          = Vender::where('id', $request->vender_id)->first();
            $payment         = new BillPayment();
            $payment->name   = !empty($vender) ? $vender['name'] : '';
            $payment->method = '-';
            $payment->date   = \Auth::user()->dateFormat($request->date);
            $payment->amount = \Auth::user()->priceFormat($request->amount);
            $payment->bill   = '';

            if (!empty($vender)) {
                Utility::userBalance('vendor', $vender->id, $request->amount, 'debit');
            }

            Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

            $uArr = [
                'payment_name' => $payment->name,
                'payment_bill' => $payment->bil,
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->date,
                'payment_method' => $payment->method,
            ];
            try {
                $resp = Utility::sendEmailTemplate('new_bill_payment', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            //Twilio Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            $vender = Vender::find($request->vender_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'payment_name' => $payment->name,
                    'payment_amount' => $payment->amount,
                    'payment_date' => $payment->date,
                    'type' => 'Payment',
                ];
                Utility::send_twilio_msg($vender->contact, 'new_payment', $uArr);
            }


            // webhook
            $module = 'New Payment';
            $webhook =  Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($payment);
                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->route('payment.index')->with('success', __('Payment successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('payment.index')->with('success', __('Payment successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function edit(Payment $payment)
    {
        if (\Auth::user()->can('edit payment')) {
            $venders = Vender::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
            $venders->prepend('--', 0);
            $categories = ProductServiceCategory::where('created_by', '=', \Auth::user()->creatorId())->get()->whereNotIn('type', ['product & service', 'income'])->pluck('name', 'id');

            $accounts = BankAccount::select('*', \DB::raw("CONCAT(bank_name,' ',holder_name) AS name"))->where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

            return view('payment.edit', compact('venders', 'categories', 'accounts', 'payment'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, Payment $payment)
    {
        if (\Auth::user()->can('edit payment')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'amount' => 'required',
                    'account_id' => 'required',
                    'vender_id' => 'required',
                    'category_id' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }
            $vender = Vender::where('id', $request->vender_id)->first();

            if(!empty($vender))
            {
                Utility::userBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
            }

            Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');

            if(!empty($vender))
            {
                Utility::userBalance('vendor', $vender->id, $request->amount, 'debit');
            }

            Utility::bankAccountBalance($request->account_id, $request->amount, 'debit');

            $payment->date           = $request->date;
            $payment->amount         = $request->amount;
            $payment->account_id     = $request->account_id;
            $payment->vender_id      = $request->vender_id;
            $payment->category_id    = $request->category_id;
            $payment->payment_method = 0;
            $payment->reference      = $request->reference;
            $payment->description    = $request->description;

            if (!empty($request->add_receipt)) {

                $file_path = 'uploads/payment/' . $payment->add_receipt;
                $image_size = $request->file('add_receipt')->getSize();

                // if ($result == 1) {
                    // Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);
                    $fileName = time() . "_" . $request->add_receipt->getClientOriginalName();
                    // $request->add_receipt->storeAs('uploads/payment', $fileName);
                    $payment->add_receipt = $fileName;

                    $dir        = 'uploads/payment';
                    $path = Utility::upload_file($request, 'add_receipt', $fileName, $dir, []);
                    // $request->add_receipt  = $path['url'];
                    if ($path['flag'] == 0) {
                        return redirect()->back()->with('error', __($path['msg']));
                    }
                // }
                // else {
                //     return redirect()->back()->with('error', $result);
                // }
                // $payment->save();
            }
            $payment->save();

            $accountId = BankAccount::find($payment->account_id);
            $data = [
                'account_id' => $accountId->chart_account_id,
                'transaction_type' => 'Debit',
                'transaction_amount' => $payment->amount,
                'reference' => 'Payment',
                'reference_id' => $payment->id,
                'reference_sub_id' => 0,
                'date' => $payment->date,
            ];
            Utility::addTransactionLines($data);

            $category            = ProductServiceCategory::where('id', $request->category_id)->first();
            $payment->category   = $category->name;
            $payment->payment_id = $payment->id;
            $payment->type       = 'Payment';
            $payment->account    = $request->account_id;
            Transaction::editTransaction($payment);

            return redirect()->route('payment.index')->with('success', __('Payment successfully updated.') .((isset($result) && $result!=1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));

        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Payment $payment)
    {
        if (\Auth::user()->can('delete payment')) {
            if ($payment->created_by == \Auth::user()->creatorId()) {
                $file_path = 'uploads/payment/' . $payment->add_receipt;

                TransactionLines::where('reference_id', $payment->id)->where('reference', 'Payment')->delete();

                $payment->delete();
                $type = 'Payment';
                $user = 'Vender';
                Transaction::destroyTransaction($payment->id, $type, $user);

                if ($payment->vender_id != 0) {
                    Utility::userBalance('vendor', $payment->vender_id, $payment->amount, 'credit');
                }
                Utility::bankAccountBalance($payment->account_id, $payment->amount, 'credit');

                return redirect()->route('payment.index')->with('success', __('Payment successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function export($date = null)
    {
        $name = 'payment_' . date('Y-m-d i:h:s');
        $data = Excel::download(new PaymentExport($date), $name . '.xlsx');

        return $data;
    }
}
