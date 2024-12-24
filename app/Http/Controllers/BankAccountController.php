<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\CustomField;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Transaction;
use App\Models\TransactionLines;
use App\Models\Utility;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{

    public function index()
    {
        if (\Auth::user()->can('create bank account')) {
            $accounts = BankAccount::with('chartAccount')->where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('bankAccount.index', compact('accounts'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function create()
    {
        if (\Auth::user()->can('create bank account')) {

            // Fetch chart accounts
            $chartAccounts = ChartOfAccount::select([\DB::raw('CONCAT(code, " - ", name) AS code_name'),'id'])->where('parent', '=', 0)->where('created_by', \Auth::user()->creatorId())->get()->pluck('code_name', 'id')->prepend('Select Account', 0);

            // Fetch sub-accounts
            $subAccounts = ChartOfAccount::select(['chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account'])->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', '=', 'chart_of_account_parents.id')->where('chart_of_accounts.parent', '!=', 0)->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()->toArray();    
            
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'account')->get();

            return view('bankAccount.create', compact('customFields', 'chartAccounts', 'subAccounts'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create bank account')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'chart_account_id' => 'required',
                    'holder_name' => 'required',
                    'bank_name' => 'required',
                    'account_number' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('bank-account.index')->with('error', $messages->first());
            }

            $account                  = new BankAccount();
            $account->chart_account_id = $request->chart_account_id;
            $account->holder_name     = $request->holder_name;
            $account->bank_name       = $request->bank_name;
            $account->account_number  = $request->account_number;
            $account->opening_balance = $request->opening_balance ? $request->opening_balance : 0;
            $account->contact_number  = $request->contact_number ? $request->contact_number : '-';
            $account->bank_address    = $request->bank_address ? $request->bank_address : '-';
            $account->created_by      = \Auth::user()->creatorId();
            $account->save();
            CustomField::saveData($account, $request->customField);

            // $accountId = BankAccount::where('chart_account_id', $account->chart_account_id)->first();
            $data = [
                'account_id' => $account->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $account->opening_balance,
                'reference' => 'Bank Account',
                'reference_id' => $account->id,
                'reference_sub_id' => 0,
                'date' => date('Y-m-d'),
            ];

            Utility::addTransactionLines($data);


            return redirect()->route('bank-account.index')->with('success', __('Account successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit(BankAccount $bankAccount)
    {
        if (\Auth::user()->can('edit bank account')) {
            if ($bankAccount->created_by == \Auth::user()->creatorId()) {

                // Fetch chart accounts
                $chartAccounts = ChartOfAccount::select([
                    \DB::raw('CONCAT(code, " - ", name) AS code_name'),
                    'id'
                ])->where('parent', '=', 0)->where('created_by', \Auth::user()->creatorId())->get()->pluck('code_name', 'id')->prepend('Select Account', 0);

                // Fetch sub-accounts
                $subAccounts = ChartOfAccount::select(['chart_of_accounts.id', 'chart_of_accounts.code', 'chart_of_accounts.name', 'chart_of_account_parents.account'])->leftjoin('chart_of_account_parents', 'chart_of_accounts.parent', '=', 'chart_of_account_parents.id')->where('chart_of_accounts.parent', '!=', 0)->where('chart_of_accounts.created_by', \Auth::user()->creatorId())->get()->toArray();

                $bankAccount->customField = CustomField::getData($bankAccount, 'account');
                $customFields             = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'account')->get();

                return view('bankAccount.edit', compact('bankAccount', 'customFields', 'chartAccounts', 'subAccounts'));
            } else {
                return response()->json(['error' => __('Permission denied.')], 401);
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, BankAccount $bankAccount)
    {
        if (\Auth::user()->can('create bank account')) {

            $validator = \Validator::make(
                $request->all(),
                [
                    'chart_account_id' => 'required',
                    'holder_name' => 'required',
                    'bank_name' => 'required',
                    'account_number' => 'required',
                ]
            );

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('bank-account.index')->with('error', $messages->first());
            }

            $bankAccount->chart_account_id = $request->chart_account_id;
            $bankAccount->holder_name     = $request->holder_name;
            $bankAccount->bank_name       = $request->bank_name;
            $bankAccount->account_number  = $request->account_number;
            $bankAccount->opening_balance = $request->opening_balance ? $request->opening_balance : 0;
            $bankAccount->contact_number  = $request->contact_number ? $request->contact_number : '-';
            $bankAccount->bank_address    = $request->bank_address ? $request->bank_address : '-';
            $bankAccount->created_by      = \Auth::user()->creatorId();
            $bankAccount->save();
            CustomField::saveData($bankAccount, $request->customField);

            $data = [
                'account_id' => $bankAccount->chart_account_id,
                'transaction_type' => 'Credit',
                'transaction_amount' => $bankAccount->opening_balance,
                'reference' => 'Bank Account',
                'reference_id' => $bankAccount->id,
                'reference_sub_id' => 0,
                'date' => date('Y-m-d'),
            ];

            Utility::addTransactionLines($data);

            return redirect()->route('bank-account.index')->with('success', __('Account successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(BankAccount $bankAccount)
    {
        if (\Auth::user()->can('delete bank account')) {
            if ($bankAccount->created_by == \Auth::user()->creatorId()) {
                $revenue        = Revenue::where('account_id', $bankAccount->id)->first();
                $invoicePayment = InvoicePayment::where('account_id', $bankAccount->id)->first();
                $transaction    = Transaction::where('account', $bankAccount->id)->first();
                $payment        = Payment::where('account_id', $bankAccount->id)->first();
                $billPayment    = BillPayment::first();

                if (!empty($revenue) && !empty($invoicePayment) && !empty($transaction) && !empty($payment) && !empty($billPayment)) {
                    return redirect()->route('bank-account.index')->with('error', __('Please delete related record of this account.'));
                } else {
                    TransactionLines::where('reference_id', $bankAccount->id)->where('reference', 'Bank Account')->delete();
                    $bankAccount->delete();


                    return redirect()->route('bank-account.index')->with('success', __('Account successfully deleted.'));
                }
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
