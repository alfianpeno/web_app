<?php

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
    use Notifiable;


    protected $appends = ['profile'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'avatar',
        'lang',
        'delete_status',
        'plan',
        'plan_expire_date',
        'last_login_at',
        'is_enable_login',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function extraKeyword()
    {
        $keyArr = [
            __('Url'),
            __('Invoice URL'),
            __('Vendor Name'),
            __('Revenue name'),

        ];
    }

    public $settings;

    public function authId()
    {
        return $this->id;
    }

    public function creatorId()
    {
        if ($this->type == 'company' || $this->type == 'super admin') {
            return $this->id;
        } else {
            return $this->created_by;
        }
    }


    public function currentLanguage()
    {
        return $this->lang;
    }

    public function priceFormat($price)
    {
        $settings = Utility::settings();

        return (($settings['site_currency_symbol_position'] == "pre") ? $settings['site_currency_symbol'] : '') . number_format($price, $settings['decimal_number']) . (($settings['site_currency_symbol_position'] == "post") ? $settings['site_currency_symbol'] : '');
    }

    public function currencySymbol()
    {
        $settings = Utility::settings();

        return $settings['site_currency_symbol'];
    }

    public static function dateFormat($date)
    {
        $settings = Utility::settings();

        return date($settings['site_date_format'], strtotime($date));
    }

    public function timeFormat($time)
    {
        $settings = Utility::settings();

        return date($settings['site_time_format'], strtotime($time));
    }

    public function invoiceNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["invoice_prefix"] . sprintf("%05d", $number);
    }

    public function contractNumberFormat($number)
    {
        $settings = Utility::settings();
        return $settings["contract_prefix"] . sprintf("%05d", $number);
    }

    public function proposalNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["proposal_prefix"] . sprintf("%05d", $number);
    }

    public function retainerNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["retainer_prefix"] . sprintf("%05d", $number);
    }

    public function billNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["bill_prefix"] . sprintf("%05d", $number);
    }

    public function journalNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["journal_prefix"] . sprintf("%05d", $number);
    }

    public function getPlan()
    {
        return $this->hasOne('App\Models\Plan', 'id', 'plan');
    }

    public function userDefaultDataRegister($user_id)
    {
        // Make Entry In User_Email_Template
        $allEmail = EmailTemplate::all();
        foreach ($allEmail as $email) {
            UserEmailTemplate::create(
                [
                    'template_id' => $email->id,
                    'user_id' => $user_id,
                    'is_active' => 1,
                ]
            );
        }
    }

    public function assignPlan($planID)
    {
        $plan = Plan::find($planID);
        if ($plan) {
            $this->plan = $plan->id;
            if ($plan->duration == 'month') {
                $this->plan_expire_date = Carbon::now()->addMonths(1)->isoFormat('YYYY-MM-DD');
            } elseif ($plan->duration == 'year') {
                $this->plan_expire_date = Carbon::now()->addYears(1)->isoFormat('YYYY-MM-DD');
            } else {
                $this->plan_expire_date = null;
            }
            $this->save();

            $users     = User::where('created_by', '=', \Auth::user()->creatorId())->where('type', '!=', 'super admin')->where('type', '!=', 'company')->get();
            $customers = Customer::where('created_by', '=', $this->id)->get();
            $venders   = Vender::where('created_by', '=', $this->id)->get();



            if ($plan->max_users == -1) {
                foreach ($users as $user) {
                    $user->is_active = 1;
                    $user->save();
                }
            } else {
                $userCount = 0;
                foreach ($users as $user) {
                    $userCount++;
                    if ($userCount <= $plan->max_users) {
                        $user->is_active = 1;
                        $user->save();
                    } else {
                        $user->is_active = 0;
                        $user->save();
                    }
                }
            }

            if ($plan->max_customers == -1) {
                foreach ($customers as $customer) {
                    $customer->is_active = 1;
                    $customer->save();
                }
            } else {
                $customerCount = 0;
                foreach ($customers as $customer) {
                    $customerCount++;
                    if ($customerCount <= $plan->max_customers) {
                        $customer->is_active = 1;
                        $customer->save();
                    } else {
                        $customer->is_active = 0;
                        $customer->save();
                    }
                }
            }


            if ($plan->max_venders == -1) {
                foreach ($venders as $vender) {
                    $vender->is_active = 1;
                    $vender->save();
                }
            } else {
                $venderCount = 0;
                foreach ($venders as $vender) {
                    $venderCount++;
                    if ($venderCount <= $plan->max_venders) {
                        $vender->is_active = 1;
                        $vender->save();
                    } else {
                        $vender->is_active = 0;
                        $vender->save();
                    }
                }
            }

            return ['is_success' => true];
        } else {
            return [
                'is_success' => false,
                'error' => 'Plan is deleted.',
            ];
        }
    }

    public function customerNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["customer_prefix"] . sprintf("%05d", $number);
    }

    public function venderNumberFormat($number)
    {
        $settings = Utility::settings();

        return $settings["vender_prefix"] . sprintf("%05d", $number);
    }

    public function countUsers()
    {
        return User::where('type', '!=', 'super admin')->where('type', '!=', 'company')->where('created_by', '=', $this->creatorId())->count();
    }

    public function countCompany()
    {
        return User::where('type', '=', 'company')->where('created_by', '=', $this->creatorId())->count();
    }

    public function countOrder()
    {
        return Order::count();
    }

    public function countplan()
    {
        return Plan::count();
    }

    public function countPaidCompany()
    {
        return User::where('type', '=', 'company')->whereNotIn(
            'plan',
            [
                0,
                1,
            ]
        )->where('created_by', '=', \Auth::user()->id)->count();
    }

    public function countCustomers()
    {
        return Customer::where('created_by', '=', $this->creatorId())->count();
    }

    public function countVenders()
    {
        return Vender::where('created_by', '=', $this->creatorId())->count();
    }

    public function countInvoices()
    {
        return Invoice::where('created_by', '=', $this->creatorId())->count();
    }

    public function countBills()
    {
        return Bill::where('created_by', '=', $this->creatorId())->count();
    }

    public function todayIncome()
    {
        $revenue      = Revenue::where('created_by', '=', $this->creatorId())->whereRaw('Date(date) = CURDATE()')->where('created_by', \Auth::user()->creatorId())->sum('amount');
        $invoices     = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('Date(send_date) = CURDATE()')->get();
        $invoiceArray = array();
        foreach ($invoices as $invoice) {
            $invoiceArray[] = $invoice->getTotal();
        }
        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceArray) ? array_sum($invoiceArray) : 0);

        return $totalIncome;
    }

    public function todayExpense()
    {
        $payment = Payment::where('created_by', '=', $this->creatorId())->where('created_by', \Auth::user()->creatorId())->whereRaw('Date(date) = CURDATE()')->sum('amount');

        $bills = Bill::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('Date(send_date) = CURDATE()')->get();

        $billArray = array();
        foreach ($bills as $bill) {
            $billArray[] = $bill->getTotal();
        }

        $totalExpense = (!empty($payment) ? $payment : 0) + (!empty($billArray) ? array_sum($billArray) : 0);

        return $totalExpense;
    }

    public function incomeCurrentMonth()
    {
        $currentMonth = date('m');
        $revenue      = Revenue::where('created_by', '=', $this->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');

        $invoices = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('MONTH(send_date) = ?', [$currentMonth])->get();

        $invoiceArray = array();
        foreach ($invoices as $invoice) {
            $invoiceArray[] = $invoice->getTotal();
        }
        $totalIncome = (!empty($revenue) ? $revenue : 0) + (!empty($invoiceArray) ? array_sum($invoiceArray) : 0);

        return $totalIncome;
    }

    public function expenseCurrentMonth()
    {
        $currentMonth = date('m');

        $payment = Payment::where('created_by', '=', $this->creatorId())->whereRaw('MONTH(date) = ?', [$currentMonth])->sum('amount');

        $bills     = Bill::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('MONTH(send_date) = ?', [$currentMonth])->get();
        $billArray = array();
        foreach ($bills as $bill) {
            $billArray[] = $bill->getTotal();
        }

        $totalExpense = (!empty($payment) ? $payment : 0) + (!empty($billArray) ? array_sum($billArray) : 0);

        return $totalExpense;
    }

    public function getincExpBarChartData()
    {
        $month[]          = __('January');
        $month[]          = __('February');
        $month[]          = __('March');
        $month[]          = __('April');
        $month[]          = __('May');
        $month[]          = __('June');
        $month[]          = __('July');
        $month[]          = __('August');
        $month[]          = __('September');
        $month[]          = __('October');
        $month[]          = __('November');
        $month[]          = __('December');
        $dataArr['month'] = $month;


        for ($i = 1; $i <= 12; $i++) {
            $monthlyIncome = Revenue::selectRaw('sum(amount) amount')->where('created_by', '=', $this->creatorId())->whereRaw('year(`date`) = ?', array(date('Y')))->whereRaw('month(`date`) = ?', $i)->first();
            $invoices      = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->whereRaw('year(`send_date`) = ?', array(date('Y')))->whereRaw('month(`send_date`) = ?', $i)->get();

            $invoiceArray = array();
            foreach ($invoices as $invoice) {
                $invoiceArray[] = $invoice->getTotal();
            }
            $totalIncome = (!empty($monthlyIncome) ? $monthlyIncome->amount : 0) + (!empty($invoiceArray) ? array_sum($invoiceArray) : 0);


            $incomeArr[] = !empty($totalIncome) ? number_format($totalIncome, 2) : 0;

            $monthlyExpense = Payment::selectRaw('sum(amount) amount')->where('created_by', '=', $this->creatorId())->whereRaw('year(`date`) = ?', array(date('Y')))->whereRaw('month(`date`) = ?', $i)->first();
            $bills          = Bill::select('*')->where('created_by', \Auth::user()->creatorId())->whereRaw('year(`send_date`) = ?', array(date('Y')))->whereRaw('month(`send_date`) = ?', $i)->get();
            $billArray      = array();
            foreach ($bills as $bill) {
                $billArray[] = $bill->getTotal();
            }

            $totalExpense = (!empty($monthlyExpense) ? $monthlyExpense->amount : 0) + (!empty($billArray) ? array_sum($billArray) : 0);

            $expenseArr[] = !empty($totalExpense) ? number_format($totalExpense, 2) : 0;
        }

        $dataArr['income']  = $incomeArr;
        $dataArr['expense'] = $expenseArr;

        return $dataArr;
    }

    public function getIncExpLineChartDate()
    {
        $usr           = \Auth::user();
        $m             = date("m");
        $de            = date("d");
        $y             = date("Y");
        $format        = 'Y-m-d';
        $arrDate       = [];
        $arrDateFormat = [];

        for ($i = 0; $i <= 15 - 1; $i++) {
            $date = date($format, mktime(0, 0, 0, $m, ($de - $i), $y));

            $arrDay[]        = date('D', mktime(0, 0, 0, $m, ($de - $i), $y));
            $arrDate[]       = $date;
            $arrDateFormat[] = date("d-M", strtotime($date));;
        }
        $dataArr['day'] = $arrDateFormat;
        for ($i = 0; $i < count($arrDate); $i++) {
            $dayIncome = Revenue::selectRaw('sum(amount) amount')->where('created_by', \Auth::user()->creatorId())->whereRaw('date = ?', $arrDate[$i])->first();

            $invoices     = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('send_date = ?', $arrDate[$i])->get();
            $invoiceArray = array();
            foreach ($invoices as $invoice) {
                $invoiceArray[] = $invoice->getTotal();
            }

            $incomeAmount = (!empty($dayIncome->amount) ? $dayIncome->amount : 0) + (!empty($invoiceArray) ? array_sum($invoiceArray) : 0);
            $incomeArr[]  = str_replace(",", "", number_format($incomeAmount, 2));

            $dayExpense = Payment::selectRaw('sum(amount) amount')->where('created_by', \Auth::user()->creatorId())->whereRaw('date = ?', $arrDate[$i])->first();

            $bills     = Bill::select('*')->where('created_by', \Auth::user()->creatorId())->whereRAW('send_date = ?', $arrDate[$i])->get();
            $billArray = array();
            foreach ($bills as $bill) {
                $billArray[] = $bill->getTotal();
            }
            $expenseAmount = (!empty($dayExpense->amount) ? $dayExpense->amount : 0) + (!empty($billArray) ? array_sum($billArray) : 0);
            $expenseArr[]  = str_replace(",", "", number_format($expenseAmount, 2));
        }

        $dataArr['income']  = $incomeArr;
        $dataArr['expense'] = $expenseArr;

        return $dataArr;
    }

    public function totalCompanyUser($id)
    {
        return User::where('created_by', '=', $id)->count();
    }

    public function totalCompanyCustomer($id)
    {
        return Customer::where('created_by', '=', $id)->count();
    }

    public function totalCompanyVender($id)
    {
        return Vender::where('created_by', '=', $id)->count();
    }

    public function planPrice()
    {
        $user = \Auth::user();
        if ($user->type == 'super admin') {
            $userId = $user->id;
        } else {
            $userId = $user->created_by;
        }

        return DB::table('settings')->where('created_by', '=', $userId)->get()->pluck('value', 'name');
    }

    public function currentPlan()
    {
        return $this->hasOne('App\Models\Plan', 'id', 'plan');
    }

    public function weeklyInvoice()
    {
        $staticstart  = date('Y-m-d', strtotime('last Week'));
        $currentDate  = date('Y-m-d');
        $invoices     = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->where('issue_date', '>=', $staticstart)->where('issue_date', '<=', $currentDate)->get();
        $invoiceTotal = 0;
        $invoicePaid  = 0;
        $invoiceDue   = 0;
        foreach ($invoices as $invoice) {
            $invoiceTotal += $invoice->getTotal();
            $invoicePaid  += ($invoice->getTotal() - $invoice->getDue());
            $invoiceDue   += $invoice->getDue();
        }

        $invoiceDetail['invoiceTotal'] = $invoiceTotal;
        $invoiceDetail['invoicePaid']  = $invoicePaid;
        $invoiceDetail['invoiceDue']   = $invoiceDue;

        return $invoiceDetail;
    }

    public function monthlyInvoice()
    {
        $staticstart  = date('Y-m-d', strtotime('last Month'));
        $currentDate  = date('Y-m-d');
        $invoices     = Invoice::select('*')->where('created_by', \Auth::user()->creatorId())->where('issue_date', '>=', $staticstart)->where('issue_date', '<=', $currentDate)->get();
        $invoiceTotal = 0;
        $invoicePaid  = 0;
        $invoiceDue   = 0;
        foreach ($invoices as $invoice) {
            $invoiceTotal += $invoice->getTotal();
            $invoicePaid  += ($invoice->getTotal() - $invoice->getDue());
            $invoiceDue   += $invoice->getDue();
        }

        $invoiceDetail['invoiceTotal'] = $invoiceTotal;
        $invoiceDetail['invoicePaid']  = $invoicePaid;
        $invoiceDetail['invoiceDue']   = $invoiceDue;

        return $invoiceDetail;
    }

    public function weeklyBill()
    {
        $staticstart = date('Y-m-d', strtotime('last Week'));
        $currentDate = date('Y-m-d');
        $bills       = Bill::select('*')->where('created_by', \Auth::user()->creatorId())->where('bill_date', '>=', $staticstart)->where('bill_date', '<=', $currentDate)->get();
        $billTotal   = 0;
        $billPaid    = 0;
        $billDue     = 0;
        foreach ($bills as $bill) {
            $billTotal += $bill->getTotal();
            $billPaid  += ($bill->getTotal() - $bill->getDue());
            $billDue   += $bill->getDue();
        }

        $billDetail['billTotal'] = $billTotal;
        $billDetail['billPaid']  = $billPaid;
        $billDetail['billDue']   = $billDue;

        return $billDetail;
    }

    public function monthlyBill()
    {
        $staticstart = date('Y-m-d', strtotime('last Month'));
        $currentDate = date('Y-m-d');
        $bills       = Bill::select('*')->where('created_by', \Auth::user()->creatorId())->where('bill_date', '>=', $staticstart)->where('bill_date', '<=', $currentDate)->get();
        $billTotal   = 0;
        $billPaid    = 0;
        $billDue     = 0;
        foreach ($bills as $bill) {
            $billTotal += $bill->getTotal();
            $billPaid  += ($bill->getTotal() - $bill->getDue());
            $billDue   += $bill->getDue();
        }

        $billDetail['billTotal'] = $billTotal;
        $billDetail['billPaid']  = $billPaid;
        $billDetail['billDue']   = $billDue;

        return $billDetail;
    }

    public static function userDefaultData()
    {
        // Make Entry In User_Email_Template
        $allEmail = EmailTemplate::all();

        foreach ($allEmail as $email) {
            UserEmailTemplate::create(
                [
                    'template_id' => $email->id,
                    'user_id' => 1,
                    'is_active' => 1,
                ]
            );
        }
    }
    public function defaultEmail()
    {
        // Email Template
        $emailTemplate = [
            'New Bill Payment',
            'Customer Invoice Sent',
            'Bill Sent',
            'New Invoice Payment',
            'Invoice Sent',
            'Payment Reminder',
            'Proposal Sent',
            'User Created',
            'Vendor Bill Sent',
            'New Contract',
            'Retainer Sent',
            'Customer Retainer Sent',
            'New Retainer Payment',

        ];

        foreach ($emailTemplate as $eTemp) {
            $emailTemp = EmailTemplate::where('name', $eTemp)->count();
            if ($emailTemp == 0) {
                EmailTemplate::create(
                    [
                        'name' => $eTemp,
                        'from' => env('APP_NAME'),
                        'slug' => strtolower(str_replace(' ', '_', $eTemp)),
                        'created_by' => 1,
                    ]
                );
            }
        }

        $defaultTemplate = [
            'new_bill_payment' => [
                'subject' => 'New Bill Payment',
                'lang' => [
                    'ar' => '<p>مرحبا ، { payment_name }</p>
                    <p>&nbsp;</p>
                    <p>مرحبا بك في { app_name }</p>
                    <p>&nbsp;</p>
                    <p>نحن نكتب لإبلاغكم بأننا قد أرسلنا مدفوعات (payment_bill) } الخاصة بك.</p>
                    <p>&nbsp;</p>
                    <p>لقد أرسلنا قيمتك { payment_amount } لأجل { payment_bill } قمت بالاحالة في التاريخ { payment_date } من خلال { payment_method }.</p>
                    <p>&nbsp;</p>
                    <p>شكرا جزيلا لك وطاب يومك ! !!!</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'da' => '<p>Hej, { payment_name }</p>
                    <p>&nbsp;</p>
                    <p>Velkommen til { app_name }</p>
                    <p>&nbsp;</p>
                    <p>Vi skriver for at informere dig om, at vi har sendt din { payment_bill }-betaling.</p>
                    <p>&nbsp;</p>
                    <p>Vi har sendt dit bel&oslash;b { payment_amount } betaling for { payment_bill } undertvist p&aring; dato { payment_date } via { payment_method }.</p>
                    <p>&nbsp;</p>
                    <p>Mange tak, og ha en god dag!</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'de' => '<p>Hi, {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>Willkommen bei {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Wir schreiben Ihnen mitzuteilen, dass wir Ihre Zahlung von {payment_bill} gesendet haben.</p>
                    <p>&nbsp;</p>
                    <p>Wir haben Ihre Zahlung {payment_amount} Zahlung f&uuml;r {payment_bill} am Datum {payment_date} &uuml;ber {payment_method} gesendet.</p>
                    <p>&nbsp;</p>
                    <p>Vielen Dank und haben einen guten Tag! !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'en' => '<p>Hi, {payment_name}</p>
                    <p>Welcome to {app_name}</p>
                    <p>We are writing to inform you that we has sent your {payment_bill} payment.</p>
                    <p>We has sent your amount {payment_amount} payment for {payment_bill} submited on date {payment_date} via {payment_method}.</p>
                    <p>Thank You very much and have a good day !!!!</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'es' => '<p>Hi, {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>Bienvenido a {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Estamos escribiendo para informarle que hemos enviado su pago {payment_bill}.</p>
                    <p>&nbsp;</p>
                    <p>Hemos enviado su importe {payment_amount} pago para {payment_bill} submitado en la fecha {payment_date} a trav&eacute;s de {payment_method}.</p>
                    <p>&nbsp;</p>
                    <p>Thank You very much and have a good day! !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'fr' => '<p>Salut, { payment_name }</p>
                    <p>&nbsp;</p>
                    <p>Bienvenue dans { app_name }</p>
                    <p>&nbsp;</p>
                    <p>Nous vous &eacute;crivons pour vous informer que nous avons envoy&eacute; votre paiement { payment_bill }.</p>
                    <p>&nbsp;</p>
                    <p>Nous avons envoy&eacute; votre paiement { payment_amount } pour { payment_bill } soumis &agrave; la date { payment_date } via { payment_method }.</p>
                    <p>&nbsp;</p>
                    <p>Merci beaucoup et avez un bon jour ! !!!</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'it' => '<p>Ciao, {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>Benvenuti in {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Scriviamo per informarti che abbiamo inviato il tuo pagamento {payment_bill}.</p>
                    <p>&nbsp;</p>
                    <p>Abbiamo inviato la tua quantit&agrave; {payment_amount} pagamento per {payment_bill} subita alla data {payment_date} tramite {payment_method}.</p>
                    <p>&nbsp;</p>
                    <p>Grazie mille e buona giornata! !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'ja' => '<p>こんにちは、 {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_name} へようこそ</p>
                    <p>&nbsp;</p>
                    <p>{payment_bill} の支払いを送信したことをお知らせするために執筆しています。</p>
                    <p>&nbsp;</p>
                    <p>{payment_date } に提出された {payment_議案} に対する金額 {payment_date} の支払いは、 {payment_method}を介して送信されました。</p>
                    <p>&nbsp;</p>
                    <p>ありがとうございます。良い日をお願いします。</p>
                    <p>&nbsp;</p>
                    <p>{ company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'nl' => '<p>Hallo, { payment_name }</p>
                    <p>&nbsp;</p>
                    <p>Welkom bij { app_name }</p>
                    <p>&nbsp;</p>
                    <p>Wij schrijven u om u te informeren dat wij uw betaling van { payment_bill } hebben verzonden.</p>
                    <p>&nbsp;</p>
                    <p>We hebben uw bedrag { payment_amount } betaling voor { payment_bill } verzonden op datum { payment_date } via { payment_method }.</p>
                    <p>&nbsp;</p>
                    <p>Hartelijk dank en hebben een goede dag! !!!</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'pl' => '<p>Witaj, {payment_name }</p>
                    <p>&nbsp;</p>
                    <p>Witamy w aplikacji {app_name }</p>
                    <p>&nbsp;</p>
                    <p>Piszemy, aby poinformować Cię, że wysłaliśmy Twoją płatność {payment_bill }.</p>
                    <p>&nbsp;</p>
                    <p>Twoja kwota {payment_amount } została wysłana przez użytkownika {payment_bill } w dniu {payment_date } za pomocą metody {payment_method }.</p>
                    <p>&nbsp;</p>
                    <p>Dziękuję bardzo i mam dobry dzień! !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name }</p>
                    <p>&nbsp;</p>
                    <p>{app_url }</p>',
                    'ru' => '<p>Привет, { payment_name }</p>
                    <p>&nbsp;</p>
                    <p>Вас приветствует { app_name }</p>
                    <p>&nbsp;</p>
                    <p>Мы пишем, чтобы сообщить вам, что мы отправили вашу оплату { payment_bill }.</p>
                    <p>&nbsp;</p>
                    <p>Мы отправили вашу сумму оплаты { payment_amount } для { payment_bill }, подав на дату { payment_date } через { payment_method }.</p>
                    <p>&nbsp;</p>
                    <p>Большое спасибо и хорошего дня! !!!</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'pt' => '<p>Oi, {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>Bem-vindo a {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Estamos escrevendo para inform&aacute;-lo que enviamos o seu pagamento {payment_bill}.</p>
                    <p>&nbsp;</p>
                    <p>N&oacute;s enviamos sua quantia {payment_amount} pagamento por {payment_bill} requisitado na data {payment_date} via {payment_method}.</p>
                    <p>&nbsp;</p>
                    <p>Muito obrigado e tenha um bom dia! !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'tr' => '<p>Merhaba, {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>Hoşgeldiniz {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Ödemenizi şu kişiden aldığımızı size bildirmek için yazıyoruz: {payment_bill} gönderildi.</p>
                    <p>&nbsp;</p>
                    <p>ödemeniz bizde {payment_amount} İçin ödeme {payment_bill} tarihte {payment_date} &uuml;hesaplanmış {payment_method} Gönderildi.</p>
                    <p>&nbsp;</p>
                    <p>Teşekkürler ve iyi günler! !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'zh' => '<p>你好, {payment_name}</p>
                    <p>&nbsp;</p>
                    <p>欢迎 {app_name}</p>
                    <p>&nbsp;</p>
                    <p>我们写信通知您，我们已收到您的付款: {payment_bill} gönderildi.</p>
                    <p>&nbsp;</p>
                    <p>我们已收到您的付款 {payment_amount} 支付 {payment_bill} 在历史上 {payment_date} ü 计算 {payment_method} 发送.</p>
                    <p>&nbsp;</p>
                    <p>谢谢，美好的一天！ !!!</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'he' => '<p>היי, {payment_name}</p>
                    <p>ברוך הבא ל{app_name}</p>
                    <p>אנו כותבים כדי להודיע ​​לך ששלחנו את שלך{payment_bill} תַשְׁלוּם.</p>
                    <p>שלחנו את הסכום שלך{payment_amount} תשלום עבור {payment_bill} הוגש בתאריך {payment_date} באמצעות {payment_method}.</p>
                    <p>תודה רבה ויום טוב!!!!</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'pt-br' => '<p>Oi, {payment_name}</p>
                    <p>Bem-vindo ao {app_name}</p>
                    <p>Estamos escrevendo para informá-lo que enviamos seu pagamento {payment_bill}.</p>
                    <p>Enviamos seu valor {payment_amount} de pagamento para {payment_bill} enviado na data {payment_date} via {payment_method}.</p>
                    <p>Muito obrigado e tenha um bom dia !!!!</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                ],
            ],
            'customer_invoice_sent' => [
                'subject' => 'Customer Invoice Sent',
                'lang' => [
                    'ar' => '<p>مرحبا ، { invoice_name }</p>
                    <p>مرحبا بك في { app_name }</p>
                    <p>أتمنى أن يجدك هذا البريد الإلكتروني جيدا برجاء الرجوع الى رقم الفاتورة الملحقة { invoice_number } للخدمة / الخدمة.</p>
                    <p>ببساطة اضغط على الاختيار بأسفل.</p>
                    <p>{ invoice_url }</p>
                    <p>إشعر بالحرية للوصول إلى الخارج إذا عندك أي أسئلة.</p>
                    <p>شكرا لك</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'da' => '<p>Hej, { invoice_name }</p>
                    <p>Velkommen til { app_name }</p>
                    <p>H&aring;ber denne e-mail finder dig godt! Se vedlagte fakturanummer { invoice_number } for product/service.</p>
                    <p>Klik p&aring; knappen nedenfor.</p>
                    <p>{ invoice_url }</p>
                    <p>Du er velkommen til at r&aelig;kke ud, hvis du har nogen sp&oslash;rgsm&aring;l.</p>
                    <p>Tak.</p>
                    <p>&nbsp;</p>
                    <p>Med venlig hilsen</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'de' => '<p>Hi, {invoice_name}</p>
                    <p>Willkommen bei {app_name}</p>
                    <p>Hoffe, diese E-Mail findet dich gut! Bitte beachten Sie die beigef&uuml;gte Rechnungsnummer {invoice_number} f&uuml;r Produkt/Service.</p>
                    <p>Klicken Sie einfach auf den Button unten.</p>
                    <p>{invoice_url}</p>
                    <p>F&uuml;hlen Sie sich frei, wenn Sie Fragen haben.</p>
                    <p>Vielen Dank,</p>
                    <p>&nbsp;</p>
                    <p>Betrachtet,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'en' => '<p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Hi, {invoice_name}</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Welcome to {app_name}</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Hope this email finds you well! Please see attached invoice number {invoice_number} for product/service.</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Simply click on the button below.</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">{invoice_url}</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Feel free to reach out if you have any questions.</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Thank You,</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">Regards,</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">{company_name}</span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;">{app_url}</span></p>',
                    'es' => '<p>Hi, {invoice_name}</p>
                    <p>&nbsp;</p>
                    <p>Bienvenido a {app_name}</p>+
                    <p>&nbsp;</p>
                    <p>&iexcl;Espero que este email le encuentre bien! Consulte el n&uacute;mero de factura adjunto {invoice_number} para el producto/servicio.</p>
                    <p>&nbsp;</p>
                    <p>Simplemente haga clic en el bot&oacute;n de abajo.</p>
                    <p>&nbsp;</p>
                    <p>{invoice_url}</p>
                    <p>&nbsp;</p>
                    <p>Si&eacute;ntase libre de llegar si usted tiene alguna pregunta.</p>
                    <p>&nbsp;</p>
                    <p>Gracias,</p>
                    <p>&nbsp;</p>
                    <p>Considerando,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'fr' => '<p>Bonjour, { invoice_name }</p>
                    <p>&nbsp;</p>
                    <p>Bienvenue dans { app_name }</p>
                    <p>&nbsp;</p>
                    <p>Jesp&egrave;re que ce courriel vous trouve bien ! Voir le num&eacute;ro de facture { invoice_number } pour le produit/service.</p>
                    <p>&nbsp;</p>
                    <p>Cliquez simplement sur le bouton ci-dessous.</p>
                    <p>&nbsp;</p>
                    <p>{ invoice_url }</p>
                    <p>&nbsp;</p>
                    <p>Nh&eacute;sitez pas &agrave; nous contacter si vous avez des questions.</p>
                    <p>&nbsp;</p>
                    <p>Merci,</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'it' => '<p>Ciao, {invoice_name}</p>
                    <p>&nbsp;</p>
                    <p>Benvenuti in {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Spero che questa email ti trovi bene! Si prega di consultare il numero di fattura collegato {invoice_number} per il prodotto/servizio.</p>
                    <p>&nbsp;</p>
                    <p>Semplicemente clicca sul pulsante sottostante.</p>
                    <p>&nbsp;</p>
                    <p>{invoice_url}</p>
                    <p>&nbsp;</p>
                    <p>Sentiti libero di raggiungere se hai domande.</p>
                    <p>&nbsp;</p>
                    <p>Grazie,</p>
                    <p>&nbsp;</p>
                    <p>Riguardo,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'ja' => '<p>こんにちは、 {invoice_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_name} へようこそ</p>
                    <p>&nbsp;</p>
                    <p>この E メールでよくご確認ください。 製品 / サービスについては、添付された請求書番号 {invoice_number} を参照してください。</p>
                    <p>&nbsp;</p>
                    <p>以下のボタンをクリックしてください。</p>
                    <p>&nbsp;</p>
                    <p>{invoice_url}</p>
                    <p>&nbsp;</p>
                    <p>質問がある場合は、自由に連絡してください。</p>
                    <p>&nbsp;</p>
                    <p>ありがとうございます</p>
                    <p>&nbsp;</p>
                    <p>よろしく</p>
                    <p>&nbsp;</p>
                    <p>{ company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'nl' => '<p>Hallo, { invoice_name }</p>
                    <p>Welkom bij { app_name }</p>
                    <p>Hoop dat deze e-mail je goed vindt! Zie bijgevoegde factuurnummer { invoice_number } voor product/service.</p>
                    <p>Klik gewoon op de knop hieronder.</p>
                    <p>{ invoice_url }</p>
                    <p>Voel je vrij om uit te reiken als je vragen hebt.</p>
                    <p>Dank U,</p>
                    <p>Betreft:</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pl' => '<p>Witaj, {invoice_name }</p>
                    <p>&nbsp;</p>
                    <p>Witamy w aplikacji {app_name }</p>
                    <p>&nbsp;</p>
                    <p>Mam nadzieję, że ta wiadomość znajdzie Cię dobrze! Sprawdź załączoną fakturę numer {invoice_number } dla produktu/usługi.</p>
                    <p>&nbsp;</p>
                    <p>Wystarczy kliknąć na przycisk poniżej.</p>
                    <p>&nbsp;</p>
                    <p>{invoice_url }</p>
                    <p>&nbsp;</p>
                    <p>Czuj się swobodnie, jeśli masz jakieś pytania.</p>
                    <p>&nbsp;</p>
                    <p>Dziękuję,</p>
                    <p>&nbsp;</p>
                    <p>W odniesieniu do</p>
                    <p>&nbsp;</p>
                    <p>{company_name }</p>
                    <p>&nbsp;</p>
                    <p>{app_url }</p>',
                    'ru' => '<p>Привет, { invoice_name }</p>
                    <p>&nbsp;</p>
                    <p>Вас приветствует { app_name }</p>
                    <p>&nbsp;</p>
                    <p>Надеюсь, это электронное письмо найдет вас хорошо! См. вложенный номер счета-фактуры { invoice_number } для производства/услуги.</p>
                    <p>&nbsp;</p>
                    <p>Просто нажмите на кнопку внизу.</p>
                    <p>&nbsp;</p>
                    <p>{ invoice_url }</p>
                    <p>&nbsp;</p>
                    <p>Не стеснитесь, если у вас есть вопросы.</p>
                    <p>&nbsp;</p>
                    <p>Спасибо.</p>
                    <p>&nbsp;</p>
                    <p>С уважением,</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>&nbsp;</p>
                    <p>{ app_url }</p>',
                    'pt' => '<p>Oi, {invoice_name}</p>
                    <p>&nbsp;</p>
                    <p>Bem-vindo a {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Espero que este e-mail encontre voc&ecirc; bem! Por favor, consulte o n&uacute;mero da fatura anexa {invoice_number} para produto/servi&ccedil;o.</p>
                    <p>&nbsp;</p>
                    <p>Basta clicar no bot&atilde;o abaixo.</p>
                    <p>&nbsp;</p>
                    <p>{invoice_url}</p>
                    <p>&nbsp;</p>
                    <p>Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</p>
                    <p>&nbsp;</p>
                    <p>Obrigado,</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                    'tr' => '<p>Merhaba, { invoice_name }</p>
                    <p>Hoşgeldiniz { app_name }</p>
                    <p>Umarım bu e-posta sizi iyi bulur! Ürün/hizmet için ekteki fatura numarasına bakın { fatura_numarası }.</p>
                    <p>Tıklamak aşağıdaki düğme.</p>
                    <p>{ invoice_url }</p>
                    <p>Herhangi bir sorunuz varsa bize ulaşabilirsiniz.</p>
                    <p>Teşekkürler.</p>
                    <p>&nbsp;</p>
                    <p>Saygılarımızla</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'zh' => '<p>你好, { invoice_name }</p>
                    <p>你好 { app_name }</p>
                    <p>希望这封电子邮件能让您满意！请参阅随附的产品/服务发票编号 {invoice_number}。</p>
                    <p>Klik p&aring; knappen nedenfor.</p>
                    <p>{ invoice_url }</p>
                    <p>点击下面的按钮。</p>
                    <p>谢谢.</p>
                    <p>&nbsp;</p>
                    <p>最诚挚的问候</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'he' => '<p>שלום, { invoice_name }</p>
                    <p>ברוך הבא ל { app_name }</p>
                    <p>מקווה שהמייל הזה ימצא אותך טוב! ראה את מספר החשבונית המצורפת { invoice_number } למוצר/שירות.</p>
                    <p>לחץ על כפתור למטה.</p>
                    <p>{ invoice_url }</p>
                    <p>אתה מוזמן לפנות אם יש לך שאלות.</p>
                    <p>תודה.</p>
                    <p>&nbsp;</p>
                    <p>איחוליי הלבביים</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pt-br' => '<p>Oi, {invoice_name}</p>
                    <p>&nbsp;</p>
                    <p>Bem-vindo a {app_name}</p>
                    <p>&nbsp;</p>
                    <p>Espero que este e-mail encontre voc&ecirc; bem! Por favor, consulte o n&uacute;mero da fatura anexa {invoice_number} para produto/servi&ccedil;o.</p>
                    <p>&nbsp;</p>
                    <p>Basta clicar no bot&atilde;o abaixo.</p>
                    <p>&nbsp;</p>
                    <p>{invoice_url}</p>
                    <p>&nbsp;</p>
                    <p>Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</p>
                    <p>&nbsp;</p>
                    <p>Obrigado,</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>&nbsp;</p>
                    <p>{app_url}</p>',
                ],
            ],
            'bill_sent' => [
                'subject' => 'Bill Sent',
                'lang' => [
                    'ar' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">مرحبا ، { bill_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">مرحبا بك في { app_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">أتمنى أن يجدك هذا البريد الإلكتروني جيدا ! ! برجاء الرجوع الى رقم الفاتورة الملحقة { bill_number } للحصول على المنتج / الخدمة.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">ببساطة اضغط على الاختيار بأسفل.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ bill_url }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">إشعر بالحرية للوصول إلى الخارج إذا عندك أي أسئلة.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">شكرا لك</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Regards,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ company_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ app_url }</span></p>',
                    'da' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hej, { bill_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Velkommen til { app_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">H&aring;ber denne e-mail finder dig godt! Se vedlagte fakturanummer } { bill_number } for product/service.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Klik p&aring; knappen nedenfor.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ bill_url }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Du er velkommen til at r&aelig;kke ud, hvis du har nogen sp&oslash;rgsm&aring;l.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Tak.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Med venlig hilsen</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ company_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ app_url }</span></p>',
                    'de' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hi, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Willkommen bei {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hoffe, diese E-Mail findet dich gut!! Sehen Sie sich die beigef&uuml;gte Rechnungsnummer {bill_number} f&uuml;r Produkt/Service an.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Klicken Sie einfach auf den Button unten.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">F&uuml;hlen Sie sich frei, wenn Sie Fragen haben.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Vielen Dank,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Betrachtet,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'en' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hi, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Welcome to {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hope this email finds you well!! Please see attached bill number {bill_number} for product/service.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Simply click on the button below.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Feel free to reach out if you have any questions.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Thank You,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Regards,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'es' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hi,&nbsp;{bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Bienvenido a {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">&iexcl;Espero que este correo te encuentre bien!! Consulte el n&uacute;mero de factura adjunto {bill_number} para el producto/servicio.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Simplemente haga clic en el bot&oacute;n de abajo.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Si&eacute;ntase libre de llegar si usted tiene alguna pregunta.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Gracias,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Considerando,</span></p>
                    <p><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'fr' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Salut,&nbsp;{bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Bienvenue dans { app_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Jesp&egrave;re que ce courriel vous trouve bien ! ! Veuillez consulter le num&eacute;ro de facture {bill_number}&nbsp;associ&eacute; au produit / service.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Cliquez simplement sur le bouton ci-dessous.</span></p>
                    <p><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Nh&eacute;sitez pas &agrave; nous contacter si vous avez des questions.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Merci,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Regards,</span></p>
                    <p><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'it' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Ciao, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Benvenuti in {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Spero che questa email ti trovi bene!! Si prega di consultare il numero di fattura allegato {bill_number} per il prodotto/servizio.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Semplicemente clicca sul pulsante sottostante.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Sentiti libero di raggiungere se hai domande.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Grazie,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Riguardo,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'ja' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">こんにちは、 {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_name} へようこそ</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">この E メールによりよく検出されます !! 製品 / サービスの添付された請求番号 {bill_number} を参照してください。</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">以下のボタンをクリックしてください。</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">質問がある場合は、自由に連絡してください。</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">ありがとうございます</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">よろしく</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'nl' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hallo, { bill_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Welkom bij { app_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hoop dat deze e-mail je goed vindt!! Zie bijgevoegde factuurnummer { bill_number } voor product/service.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Klik gewoon op de knop hieronder.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ bill_url }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Voel je vrij om uit te reiken als je vragen hebt.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Dank U,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Betreft:</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ company_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ app_url }</span></p>',
                    'pl' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Witaj,&nbsp;{bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Witamy w aplikacji {app_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Mam nadzieję, że ta wiadomość e-mail znajduje Cię dobrze!! Zapoznaj się z załączonym numerem rachunku {bill_number } dla produktu/usługi.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Wystarczy kliknąć na przycisk poniżej.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Czuj się swobodnie, jeśli masz jakieś pytania.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Dziękuję,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">W odniesieniu do</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url }</span></p>',
                    'ru' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Привет, { bill_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Вас приветствует { app_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Надеюсь, это письмо найдет вас хорошо! См. прилагаемый номер счета { bill_number } для product/service.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Просто нажмите на кнопку внизу.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ bill_url }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Не стеснитесь, если у вас есть вопросы.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Спасибо.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">С уважением,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ company_name }</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{ app_url }</span></p>',
                    'pt' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Oi, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Bem-vindo a {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Espero que este e-mail encontre voc&ecirc; bem!! Por favor, consulte o n&uacute;mero de faturamento conectado {bill_number} para produto/servi&ccedil;o.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Basta clicar no bot&atilde;o abaixo.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Obrigado,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Considera,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'tr' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hi, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hoşgeldiniz {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Umarım bu e-posta sizi iyi bulur!! Lütfen ürün/hizmet için ekteki {bill_number} numaralı faturaya bakın.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Aşağıdaki butona tıklamanız yeterlidir.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Herhangi bir sorunuz varsa çekinmeden bize ulaşın.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Teşekkür ederim,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Saygılarımızla,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'zh' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">你好, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">欢迎来到 {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">希望这封电子邮件给您带来好处！请参阅随附的产品/服务帐单号 {bill_number}。</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">只需点击下面的按钮即可。</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">如果您有任何疑问，请随时与我们联系。</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">谢谢你，</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">问候,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'he' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">היי, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">ברוך הבא ל {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">מקווה שהמייל הזה ימצא אותך טוב!! ראה את מספר החשבון המצורף {bill_number} עבור מוצר/שירות.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">פשוט לחץ על הכפתור למטה.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">אל תהסס לפנות אם יש לך שאלות.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">תודה,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">בברכה,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'pt-br' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Oi, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Bem-vindo a {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Espero que este e-mail encontre voc&ecirc; bem!! Por favor, consulte o n&uacute;mero de faturamento conectado {bill_number} para produto/servi&ccedil;o.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Basta clicar no bot&atilde;o abaixo.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Obrigado,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Considera,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                ],
            ],
            'new_invoice_payment' => [
                'subject' => 'New Invoice Payment',
                'lang' => [
                    'ar' => '<p>مرحبا</p>
                    <p>مرحبا بك في { app_name }</p>
                    <p>عزيزي { payment_name }</p>
                    <p>لقد قمت باستلام المبلغ الخاص بك {payment_amount}&nbsp; لبرنامج { invoice_number } الذي تم تقديمه في التاريخ { payment_date }</p>
                    <p>مقدار الاستحقاق { invoice_number } الخاص بك هو {payment_dueAmount}</p>
                    <p>ونحن نقدر الدفع الفوري لكم ونتطلع إلى استمرار العمل معكم في المستقبل.</p>
                    <p>&nbsp;</p>
                    <p>شكرا جزيلا لكم ويوم جيد ! !</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'da' => '<p>Hej.</p>
                    <p>Velkommen til { app_name }</p>
                    <p>K&aelig;re { payment_name }</p>
                    <p>Vi har modtaget din m&aelig;ngde { payment_amount } betaling for { invoice_number } undert.d. p&aring; dato { payment_date }</p>
                    <p>Dit { invoice_number } Forfaldsbel&oslash;b er { payment_dueAmount }</p>
                    <p>Vi s&aelig;tter pris p&aring; din hurtige betaling og ser frem til fortsatte forretninger med dig i fremtiden.</p>
                    <p>Mange tak, og ha en god dag!</p>
                    <p>&nbsp;</p>
                    <p>Med venlig hilsen</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'de' => '<p>Hi,</p>
                    <p>Willkommen bei {app_name}</p>
                    <p>Sehr geehrter {payment_name}</p>
                    <p>Wir haben Ihre Zahlung {payment_amount} f&uuml;r {invoice_number}, die am Datum {payment_date} &uuml;bergeben wurde, erhalten.</p>
                    <p>Ihr {invoice_number} -f&auml;lliger Betrag ist {payment_dueAmount}</p>
                    <p>Wir freuen uns &uuml;ber Ihre prompte Bezahlung und freuen uns auf das weitere Gesch&auml;ft mit Ihnen in der Zukunft.</p>
                    <p>Vielen Dank und habe einen guten Tag!!</p>
                    <p>&nbsp;</p>
                    <p>Betrachtet,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'en' => '<p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">Hi,</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">Welcome to {app_name}</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">Dear {payment_name}</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">We have recieved your amount {payment_amount} payment for {invoice_number} submited on date {payment_date}</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">Your {invoice_number} Due amount is {payment_dueAmount}</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">We appreciate your prompt payment and look forward to continued business with you in the future.</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">Thank you very much and have a good day!!</span></span></p>
                    <p>&nbsp;</p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">Regards,</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">{company_name}</span></span></p>
                    <p><span style="color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;"><span style="font-size: 15px; font-variant-ligatures: common-ligatures;">{app_url}</span></span></p>',
                    'es' => '<p>Hola,</p>
                    <p>Bienvenido a {app_name}</p>
                    <p>Estimado {payment_name}</p>
                    <p>Hemos recibido su importe {payment_amount} pago para {invoice_number} submitado en la fecha {payment_date}</p>
                    <p>El importe de {invoice_number} Due es {payment_dueAmount}</p>
                    <p>Agradecemos su pronto pago y esperamos continuar con sus negocios con usted en el futuro.</p>
                    <p>Muchas gracias y que tengan un buen d&iacute;a!!</p>
                    <p>&nbsp;</p>
                    <p>Considerando,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'fr' => '<p>Salut,</p>
                    <p>Bienvenue dans { app_name }</p>
                    <p>Cher { payment_name }</p>
                    <p>Nous avons re&ccedil;u votre montant { payment_amount } de paiement pour { invoice_number } soumis le { payment_date }</p>
                    <p>Votre {invoice_number} Montant d&ucirc; est { payment_dueAmount }</p>
                    <p>Nous appr&eacute;cions votre rapidit&eacute; de paiement et nous attendons avec impatience de poursuivre vos activit&eacute;s avec vous &agrave; lavenir.</p>
                    <p>Merci beaucoup et avez une bonne journ&eacute;e ! !</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'it' => '<p>Ciao,</p>
                    <p>Benvenuti in {app_name}</p>
                    <p>Caro {payment_name}</p>
                    <p>Abbiamo ricevuto la tua quantit&agrave; {payment_amount} pagamento per {invoice_number} subita alla data {payment_date}</p>
                    <p>Il tuo {invoice_number} A somma cifra &egrave; {payment_dueAmount}</p>
                    <p>Apprezziamo il tuo tempestoso pagamento e non vedo lora di continuare a fare affari con te in futuro.</p>
                    <p>Grazie mille e buona giornata!!</p>
                    <p>&nbsp;</p>
                    <p>Riguardo,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'ja' => '<p>こんにちは。</p>
                    <p>{app_name} へようこそ</p>
                    <p>{ payment_name} に出れます</p>
                    <p>{ payment_date} 日付で提出された {請求書番号} の支払金額 } の金額を回収しました。 }</p>
                    <p>お客様の {請求書番号} 予定額は {payment_dueAmount} です</p>
                    <p>お客様の迅速な支払いを評価し、今後も継続してビジネスを継続することを期待しています。</p>
                    <p>ありがとうございます。良い日をお願いします。</p>
                    <p>&nbsp;</p>
                    <p>よろしく</p>
                    <p>{ company_name}</p>
                    <p>{app_url}</p>',
                    'nl' => '<p>Hallo,</p>
                    <p>Welkom bij { app_name }</p>
                    <p>Beste { payment_name }</p>
                    <p>We hebben uw bedrag ontvangen { payment_amount } betaling voor { invoice_number } ingediend op datum { payment_date }</p>
                    <p>Uw { invoice_number } verschuldigde bedrag is { payment_dueAmount }</p>
                    <p>Wij waarderen uw snelle betaling en kijken uit naar verdere zaken met u in de toekomst.</p>
                    <p>Hartelijk dank en hebben een goede dag!!</p>
                    <p>&nbsp;</p>
                    <p>Betreft:</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pl' => '<p>Witam,</p>
                    <p>Witamy w aplikacji {app_name }</p>
                    <p>Droga {payment_name }</p>
                    <p>Odebrano kwotę {payment_amount } płatności za {invoice_number } w dniu {payment_date }, kt&oacute;ry został zastąpiony przez użytkownika.</p>
                    <p>{invoice_number } Kwota należna: {payment_dueAmount }</p>
                    <p>Doceniamy Twoją szybką płatność i czekamy na kontynuację działalności gospodarczej z Tobą w przyszłości.</p>
                    <p>Dziękuję bardzo i mam dobry dzień!!</p>
                    <p>&nbsp;</p>
                    <p>W odniesieniu do</p>
                    <p>{company_name }</p>
                    <p>{app_url }</p>',
                    'ru' => '<p>Привет.</p>
                    <p>Вас приветствует { app_name }</p>
                    <p>Дорогая { payment_name }</p>
                    <p>Мы получили вашу сумму оплаты {payment_amount} для { invoice_number }, подавшей на дату { payment_date }</p>
                    <p>Ваша { invoice_number } Должная сумма-{ payment_dueAmount }</p>
                    <p>Мы ценим вашу своевременную оплату и надеемся на продолжение бизнеса с вами в будущем.</p>
                    <p>Большое спасибо и хорошего дня!!</p>
                    <p>&nbsp;</p>
                    <p>С уважением,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pt' => '<p>Oi,</p>
                    <p>Bem-vindo a {app_name}</p>
                    <p>Querido {payment_name}</p>
                    <p>N&oacute;s recibimos sua quantia {payment_amount} pagamento para {invoice_number} requisitado na data {payment_date}</p>
                    <p>Sua quantia {invoice_number} Due &eacute; {payment_dueAmount}</p>
                    <p>Agradecemos o seu pronto pagamento e estamos ansiosos para continuarmos os neg&oacute;cios com voc&ecirc; no futuro.</p>
                    <p>Muito obrigado e tenha um bom dia!!</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'tr' => '<p>Merhaba.</p>
                    <p>Hoşgeldiniz { app_name }</p>
                    <p>Canım { payment_name }</p>
                    <p>{ fatura_numarası } için { ödeme_amount } ödemenizi şu tarihe kadar aldık: { ödeme_tarihi } tarihinde</p>
                    <p>{ fatura numaranız } Ödenmesi Gereken Tutarınız: { vadesi gelen ödeme Tutarı }</p>
                    <p>Hızlı ödemeniz için teşekkür ederiz ve gelecekte sizinle iş yapmaya devam etmeyi dört gözle bekliyoruz.</p>
                    <p>Çok teşekkür ederim ve iyi günler!</p>
                    <p>&nbsp;</p>
                    <p>Saygılarımızla</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'zh' => '<p>你好.</p>
                    <p>欢迎来到 { app_name }</p>
                    <p>亲爱的 { payment_name }</p>
                    <p>我们已收到您针对 {invoice_number} 的 { payment_amount } 付款日期 { payment_date }</p>
                    <p>您的{发票号码}到期金额是{付款到期金额}</p>
                    <p>我们感谢您及时付款，并期待将来继续与您开展业务。</p>
                    <p>非常感谢您，祝您度过愉快的一天！</p>
                    <p>&nbsp;</p>
                    <p>最诚挚的问候</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'he' => '<p>שלום.</p>
                    <p>ברוך הבא ל { app_name }</p>
                    <p>יָקָר { payment_name }</p>
                    <p>קיבלנו את התשלום שלך ב-{ payment_amount } עבור { invoice_number } תחת בתאריך { payment_date }</p>
                    <p>{ חשבונית מספר } סכום התשלום שלך הוא { תשלום בשל סכום }</p>
                    <p>אנו מעריכים את התשלום המהיר שלך ומצפים להמשך העסקים איתך בעתיד.</p>
                    <p>תודה רבה ויום נעים!</p>
                    <p>&nbsp;</p>
                    <p>איחוליי הלבביים</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pt-br' => '<p>Oi.</p>
                    <p>Bem-vindo a { app_name }</p>
                    <p>Querido { payment_name }</p>
                    <p>N&oacute;s recibimos sua quantia {payment_amount} pagamento para {invoice_number} requisitado na data {payment_date}</p>
                    <p>Sua quantia {invoice_number} Due &eacute; {payment_dueAmount}</p>
                    <p>Agradecemos o seu pronto pagamento e estamos ansiosos para continuarmos os neg&oacute;cios com voc&ecirc; no futuro.</p>
                    <p>Muito obrigado e tenha um bom dia!!</p>
                    <p>&nbsp;</p>
                    <p>Considera</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                ],
            ],
            'invoice_sent' => [
                'subject' => 'Invoice Sent',
                'lang' => [
                    'ar' => '<p>مرحبا { invoice_name },</p>
                    <p>أتمنى أن يجدك هذا البريد الإلكتروني جيدا برجاء الرجوع الى رقم الفاتورة الملحقة { invoice_number } للخدمة / الخدمة.</p>
                    <p>ببساطة اضغط على الاختيار بأسفل</p>
                    <p>{ invoice_url }</p>
                    <p>إشعر بالحرية للوصول إلى الخارج إذا عندك أي أسئلة.</p>
                    <p>شكرا لعملك ! !</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'da' => '<p>Hallo { invoice_name },</p>
                    <p>H&aring;ber denne e-mail finder dig godt! Se vedlagte fakturanummer { invoice_number } for product/service.</p>
                    <p>Klik p&aring; knappen nedenfor</p>
                    <p>{ invoice_url }</p>
                    <p>Du er velkommen til at r&aelig;kke ud, hvis du har nogen sp&oslash;rgsm&aring;l.</p>
                    <p>Tak for din virksomhed!</p>
                    <p>&nbsp;</p>
                    <p>Med venlig hilsen</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'de' => '<p>Hello {invoice_name},</p>
                    <p>Hoffe, diese E-Mail findet dich gut! Bitte beachten Sie die beigef&uuml;gte Rechnungsnummer {invoice_number} f&uuml;r Produkt/Service.</p>
                    <p>Klicken Sie einfach auf den Button unten</p>
                    <p>{invoice_url}</p>
                    <p>F&uuml;hlen Sie sich frei, wenn Sie Fragen haben.</p>
                    <p>Vielen Dank f&uuml;r Ihr Unternehmen!!</p>
                    <p>&nbsp;</p>
                    <p>Betrachtet,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'en' => '<p>Hello {invoice_name},</p>
                    <p>Hope this email finds you well! Please see attached invoice number {invoice_number} for product/service.</p>
                    <p>Simply click on the button below</p>
                    <p>{invoice_url}</p>
                    <p>Feel free to reach out if you have any questions.</p>
                    <p>Thank you for your business!!</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{company_name}<br />{app_url}</p>
                    <p>&nbsp;</p>',
                    'es' => '<p>Hello {invoice_name},</p>
                    <p>&iexcl;Espero que este email le encuentre bien! Consulte el n&uacute;mero de factura adjunto {invoice_number} para el producto/servicio.</p>
                    <p>Simplemente haga clic en el bot&oacute;n de abajo</p>
                    <p>{invoice_url}</p>
                    <p>Si&eacute;ntase libre de llegar si usted tiene alguna pregunta.</p>
                    <p>&iexcl;Gracias por su negocio!!</p>
                    <p>&nbsp;</p>
                    <p>Considerando,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'fr' => '<p>Bonjour {invoice_name},</p>
                    <p>Jesp&egrave;re que ce courriel vous trouve bien ! Voir le num&eacute;ro de facture { invoice_number } pour le produit/service.</p>
                    <p>Cliquez simplement sur le bouton ci-dessous</p>
                    <p>{ invoice_url}</p>
                    <p>Nh&eacute;sitez pas &agrave; nous contacter si vous avez des questions.</p>
                    <p>Merci pour votre entreprise ! !</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>&nbsp;</p>
                    <p>{company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'it' => '<p>Ciao {invoice_name},</p>
                    <p>Spero che questa email ti trovi bene! Si prega di consultare il numero di fattura collegato {invoice_number} per il prodotto/servizio.</p>
                    <p>Semplicemente clicca sul pulsante sottostante</p>
                    <p>{invoice_url}</p>
                    <p>Sentiti libero di raggiungere se hai domande.</p>
                    <p>Grazie per il tuo business!!</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>Riguardo,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'ja' => '<p>こんにちは {invoice_name}、</p>
                    <p>この E メールでよくご確認ください。 製品 / サービスについては、添付された請求書番号 {invoice_number} を参照してください。</p>
                    <p>以下のボタンをクリックしてください。</p>
                    <p>{invoice_url}</p>
                    <p>質問がある場合は、自由に連絡してください。</p>
                    <p>お客様のビジネスに感謝します。</p>
                    <p>&nbsp;</p>
                    <p>よろしく</p>
                    <p>&nbsp;</p>
                    <p>{ company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'nl' => '<p>Hallo { invoice_name },</p>
                    <p>Hoop dat deze e-mail je goed vindt! Zie bijgevoegde factuurnummer { invoice_number } voor product/service.</p>
                    <p>Klik gewoon op de knop hieronder</p>
                    <p>{ invoice_url }</p>
                    <p>Voel je vrij om uit te reiken als je vragen hebt.</p>
                    <p>Dank u voor uw bedrijf!!</p>
                    <p>&nbsp;</p>
                    <p>Betreft:</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'pl' => '<p>Witaj {invoice_name },</p>
                    <p>Mam nadzieję, że ta wiadomość znajdzie Cię dobrze! Sprawdź załączoną fakturę numer {invoice_number } dla produktu/usługi.</p>
                    <p>Wystarczy kliknąć na przycisk poniżej</p>
                    <p>{invoice_url }</p>
                    <p>Czuj się swobodnie, jeśli masz jakieś pytania.</p>
                    <p>Dziękujemy za prowadzenie działalności!!</p>
                    <p>&nbsp;</p>
                    <p>W odniesieniu do</p>
                    <p>&nbsp;</p>
                    <p>{company_name }</p>
                    <p>{app_url }</p>
                    <p>&nbsp;</p>',
                    'ru' => '<p>Здравствуйте, { invice_name },</p>
                    <p>Надеюсь, это электронное письмо найдет вас хорошо! См. вложенный номер счета-фактуры { invoice_number } для производства/услуги.</p>
                    <p>Просто нажмите на кнопку ниже</p>
                    <p>{ invoice_url }</p>
                    <p>Не стеснитесь, если у вас есть вопросы.</p>
                    <p>Спасибо за ваше дело!</p>
                    <p>&nbsp;</p>
                    <p>С уважением,</p>
                    <p>&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'pt' => '<p>Olá {invoice_name},</p>
                    <p>Espero que este e-mail encontre voc&ecirc; bem! Por favor, consulte o n&uacute;mero da fatura anexa {invoice_number} para produto/servi&ccedil;o.</p>
                    <p>Basta clicar no botão abaixo</p>
                    <p>{invoice_url}</p>
                    <p>Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</p>
                    <p>Obrigado pelo seu neg&oacute;cio!!</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'tr' => '<p>Merhaba {invoice_name},</p>
                    <p>Umarım bu e-posta sizi iyi bulur! Lütfen ürün/hizmet için ekteki {invoice_number} numaralı faturaya bakın.</p>
                    <p>Aşağıdaki butona tıklamanız yeterli</p>
                    <p>{invoice_url}</p>
                    <p>Herhangi bir sorunuz varsa çekinmeden bize ulaşın.</p>
                    <p>İşiniz için teşekkür ederim!!</p>
                    <p>&nbsp;</p>
                    <p>Saygılarımızla,</p>
                    <p>{company_name}<br />{app_url}</p>
                    <p>&nbsp;</p>',
                    'zh' => '<p>你好 {invoice_name},</p>
                    <p>希望这封电子邮件能让您满意！请参阅随附的产品/服务发票编号 {invoice_number}。</p>
                    <p>只需点击下面的按钮</p>
                    <p>{invoice_url}</p>
                    <p>如果您有任何疑问，请随时与我们联系。</p>
                    <p>感谢您的业务！！</p>
                    <p>&nbsp;</p>
                    <p>问候,</p>
                    <p>{company_name}<br />{app_url}</p>
                    <p>&nbsp;</p>',
                    'he' => '<p>שלום {invoice_name},</p>
                    <p>מקווה שהמייל הזה ימצא אותך טוב! ראה את מספר החשבונית המצורפת {invoice_number} עבור מוצר/שירות.</p>
                    <p>פשוט לחץ על הכפתור למטה</p>
                    <p>{invoice_url}</p>
                    <p>אל תהסס לפנות אם יש לך שאלות.</p>
                    <p>תודה לך על העסק שלך!!</p>
                    <p>&nbsp;</p>
                    <p>בברכה,</p>
                    <p>{company_name}<br />{app_url}</p>
                    <p>&nbsp;</p>',
                    'pt-br' => '<p>Olá {invoice_name},</p>
                    <p>Espero que este e-mail o encontre bem! Consulte o número da fatura em anexo {invoice_number} para produto/serviço.</p>
                    <p>Basta clicar no botão abaixo</p>
                    <p>{invoice_url}</p>
                    <p>Sinta-se à vontade para entrar em contato se tiver alguma dúvida.</p>
                    <p>Agradeço pelos seus serviços!!</p>
                    <p>&nbsp;</p>
                    <p>Cumprimentos,</p>
                    <p>{company_name}<br />{app_url}</p>
                    <p>&nbsp;</p>',
                ],
            ],
            'payment_reminder' => [
                'subject' => 'Payment Reminder',
                'lang' => [
                    'ar' => '<p>عزيزي ، { payment_name }</p>
                    <p>آمل أن تكون بخير. هذا مجرد تذكير بأن الدفع على الفاتورة { invoice_number } الاجمالي { payment_dueAmount } ، والتي قمنا بارسالها على { payment_date } مستحق اليوم.</p>
                    <p>يمكنك دفع مبلغ لحساب البنك المحدد على الفاتورة.</p>
                    <p>أنا متأكد أنت مشغول ، لكني أقدر إذا أنت يمكن أن تأخذ a لحظة ونظرة على الفاتورة عندما تحصل على فرصة.</p>
                    <p>إذا كان لديك أي سؤال مهما يكن ، يرجى الرد وسأكون سعيدا لتوضيحها.</p>
                    <p>&nbsp;</p>
                    <p>شكرا&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'da' => '<p>K&aelig;re, { payment_name }</p>
                    <p>Dette er blot en p&aring;mindelse om, at betaling p&aring; faktura { invoice_number } i alt { payment_dueAmount}, som vi sendte til { payment_date }, er forfalden i dag.</p>
                    <p>Du kan foretage betalinger til den bankkonto, der er angivet p&aring; fakturaen.</p>
                    <p>Jeg er sikker p&aring; du har travlt, men jeg ville s&aelig;tte pris p&aring;, hvis du kunne tage et &oslash;jeblik og se p&aring; fakturaen, n&aring;r du f&aring;r en chance.</p>
                    <p>Hvis De har nogen sp&oslash;rgsm&aring;l, s&aring; svar venligst, og jeg vil med gl&aelig;de tydeligg&oslash;re dem.</p>
                    <p>&nbsp;</p>
                    <p>Tak.&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'de' => '<p>Sehr geehrte/r, {payment_name}</p>
                    <p>Ich hoffe, Sie sind gut. Dies ist nur eine Erinnerung, dass die Zahlung auf Rechnung {invoice_number} total {payment_dueAmount}, die wir gesendet am {payment_date} ist heute f&auml;llig.</p>
                    <p>Sie k&ouml;nnen die Zahlung auf das auf der Rechnung angegebene Bankkonto vornehmen.</p>
                    <p>Ich bin sicher, Sie sind besch&auml;ftigt, aber ich w&uuml;rde es begr&uuml;&szlig;en, wenn Sie einen Moment nehmen und &uuml;ber die Rechnung schauen k&ouml;nnten, wenn Sie eine Chance bekommen.</p>
                    <p>Wenn Sie irgendwelche Fragen haben, antworten Sie bitte und ich w&uuml;rde mich freuen, sie zu kl&auml;ren.</p>
                    <p>&nbsp;</p>
                    <p>Danke,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'en' => '<p>Dear, {payment_name}</p>
                    <p>I hope you&rsquo;re well.This is just a reminder that payment on invoice {invoice_number} total dueAmount {payment_dueAmount} , which we sent on {payment_date} is due today.</p>
                    <p>You can make payment to the bank account specified on the invoice.</p>
                    <p>I&rsquo;m sure you&rsquo;re busy, but I&rsquo;d appreciate if you could take a moment and look over the invoice when you get a chance.</p>
                    <p>If you have any questions whatever, please reply and I&rsquo;d be happy to clarify them.</p>
                    <p>&nbsp;</p>
                    <p>Thanks,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'es' => '<p>Estimado, {payment_name}</p>
                    <p>Espero que est&eacute;s bien. Esto es s&oacute;lo un recordatorio de que el pago en la factura {invoice_number} total {payment_dueAmount}, que enviamos en {payment_date} se vence hoy.</p>
                    <p>Puede realizar el pago a la cuenta bancaria especificada en la factura.</p>
                    <p>Estoy seguro de que est&aacute;s ocupado, pero agradecer&iacute;a si podr&iacute;as tomar un momento y mirar sobre la factura cuando tienes una oportunidad.</p>
                    <p>Si tiene alguna pregunta, por favor responda y me gustar&iacute;a aclararlas.</p>
                    <p>&nbsp;</p>
                    <p>Gracias,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'fr' => '<p>Cher, { payment_name }</p>
                    <p>Jesp&egrave;re que vous &ecirc;tes bien, ce nest quun rappel que le paiement sur facture {invoice_number}total { payment_dueAmount }, que nous avons envoy&eacute; le {payment_date} est d&ucirc; aujourdhui.</p>
                    <p>Vous pouvez effectuer le paiement sur le compte bancaire indiqu&eacute; sur la facture.</p>
                    <p>Je suis s&ucirc;r que vous &ecirc;tes occup&eacute;, mais je vous serais reconnaissant de prendre un moment et de regarder la facture quand vous aurez une chance.</p>
                    <p>Si vous avez des questions, veuillez r&eacute;pondre et je serais heureux de les clarifier.</p>
                    <p>&nbsp;</p>
                    <p>Merci,&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'it' => '<p>Caro, {payment_name}</p>
                    <p>Spero che tu stia bene, questo &egrave; solo un promemoria che il pagamento sulla fattura {invoice_number} totale {payment_dueAmount}, che abbiamo inviato su {payment_date} &egrave; dovuto oggi.</p>
                    <p>&Egrave; possibile effettuare il pagamento al conto bancario specificato sulla fattura.</p>
                    <p>Sono sicuro che sei impegnato, ma apprezzerei se potessi prenderti un momento e guardare la fattura quando avrai una chance.</p>
                    <p>Se avete domande qualunque, vi prego di rispondere e sarei felice di chiarirle.</p>
                    <p>&nbsp;</p>
                    <p>Grazie,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'ja' => '<p>ID、 {payment_name}</p>
                    <p>これは、 { payment_dueAmount} の合計 {payment_dueAmount } に対する支払いが今日予定されていることを思い出させていただきたいと思います。</p>
                    <p>請求書に記載されている銀行口座に対して支払いを行うことができます。</p>
                    <p>お忙しいのは確かですが、機会があれば、少し時間をかけてインボイスを見渡すことができればありがたいのですが。</p>
                    <p>何か聞きたいことがあるなら、お返事をお願いしますが、喜んでお答えします。</p>
                    <p>&nbsp;</p>
                    <p>ありがとう。&nbsp;</p>
                    <p>{ company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'nl' => '<p>Geachte, { payment_name }</p>
                    <p>Ik hoop dat u goed bent. Dit is gewoon een herinnering dat betaling op factuur { invoice_number } totaal { payment_dueAmount }, die we verzonden op { payment_date } is vandaag verschuldigd.</p>
                    <p>U kunt betaling doen aan de bankrekening op de factuur.</p>
                    <p>Ik weet zeker dat je het druk hebt, maar ik zou het op prijs stellen als je even over de factuur kon kijken als je een kans krijgt.</p>
                    <p>Als u vragen hebt, beantwoord dan uw antwoord en ik wil ze graag verduidelijken.</p>
                    <p>&nbsp;</p>
                    <p>Bedankt.&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'pl' => '<p>Drogi, {payment_name }</p>
                    <p>Mam nadzieję, że jesteś dobrze. To jest tylko przypomnienie, że płatność na fakturze {invoice_number } total {payment_dueAmount }, kt&oacute;re wysłaliśmy na {payment_date } jest dzisiaj.</p>
                    <p>Płatność można dokonać na rachunek bankowy podany na fakturze.</p>
                    <p>Jestem pewien, że jesteś zajęty, ale byłbym wdzięczny, gdybyś m&oacute;gł wziąć chwilę i spojrzeć na fakturę, kiedy masz szansę.</p>
                    <p>Jeśli masz jakieś pytania, proszę o odpowiedź, a ja chętnie je wyjaśniam.</p>
                    <p>&nbsp;</p>
                    <p>Dziękuję,&nbsp;</p>
                    <p>{company_name }</p>
                    <p>{app_url }</p>
                    <p>&nbsp;</p>',
                    'ru' => '<p>Уважаемый, { payment_name }</p>
                    <p>Я надеюсь, что вы хорошо. Это просто напоминание о том, что оплата по счету { invoice_number } всего { payment_dueAmount }, которое мы отправили в { payment_date }, сегодня.</p>
                    <p>Вы можете произвести платеж на банковский счет, указанный в счете-фактуре.</p>
                    <p>Я уверена, что ты занята, но я была бы признательна, если бы ты смог бы поглядеться на счет, когда у тебя появится шанс.</p>
                    <p>Если у вас есть вопросы, пожалуйста, ответьте, и я буду рад их прояснить.</p>
                    <p>&nbsp;</p>
                    <p>Спасибо.&nbsp;</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>',
                    'pt' => '<p>Querido, {payment_name}</p>
                    <p>Espero que voc&ecirc; esteja bem. Este &eacute; apenas um lembrete de que o pagamento na fatura {invoice_number} total {payment_dueAmount}, que enviamos em {payment_date} &eacute; devido hoje.</p>
                    <p>Voc&ecirc; pode fazer o pagamento &agrave; conta banc&aacute;ria especificada na fatura.</p>
                    <p>Eu tenho certeza que voc&ecirc; est&aacute; ocupado, mas eu agradeceria se voc&ecirc; pudesse tirar um momento e olhar sobre a fatura quando tiver uma chance.</p>
                    <p>Se voc&ecirc; tiver alguma d&uacute;vida o que for, por favor, responda e eu ficaria feliz em esclarec&ecirc;-las.</p>
                    <p>&nbsp;</p>
                    <p>Obrigado,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'tr' => '<p>Canım, {payment_name}</p>
                    <p>Umarım iyisindir. Bu, {payment_date} tarihinde gönderdiğimiz {invoice_number} toplam vade tutarı {payment_dueAmount} olan faturanın ödemesinin bugün sona ereceğini hatırlatma amaçlıdır.</p>
                    <p>Faturada belirtilen banka hesabına ödeme yapabilirsiniz.</p>
                    <p>Eminim meşgulsünüz ama fırsat bulduğunuzda bir dakikanızı ayırıp faturaya göz atarsanız sevinirim.</p>
                    <p>Herhangi bir sorunuz varsa, lütfen yanıtlayın; bunları açıklığa kavuşturmaktan memnuniyet duyarım.</p>
                    <p>&nbsp;</p>
                    <p>Teşekkürler,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'zh' => '<p>亲爱的, {payment_name}</p>
                    <p>希望您一切顺利。这只是一个提醒，我们于 { payment_date} 发送的发票 {invoice_number} 上的应付金额总计 { payment_dueAmount} 将于今天到期。</p>
                    <p>您可以向发票上指定的银行帐户付款。</p>
                    <p>我相信您很忙，但如果您有机会花点时间查看一下发票，我将不胜感激。</p>
                    <p>如果您有任何疑问，请回复，我很乐意予以澄清。</p>
                    <p>&nbsp;</p>
                    <p>谢谢,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'he' => '<p>יָקָר, {payment_name}</p>
                    <p>אני מקווה ששלומך טוב. זוהי רק תזכורת שהתשלום על החשבונית {invoice_number} total dueAmount {payment_dueAmount} , ששלחנו בתאריך {payment_date}, יבוא היום.</p>
                    <p>ניתן לבצע תשלום לחשבון הבנק המצוין בחשבונית.</p>
                    <p>אני בטוח שאתה עסוק, אבל אודה אם תוכל להקדיש רגע ולעיין בחשבונית כשתהיה לך הזדמנות.</p>
                    <p>אם יש לך שאלות כלשהן, אנא השב ואשמח להבהיר אותן.</p>
                    <p>&nbsp;</p>
                    <p>תודה,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                    'pt-br' => '<p>Querido, {payment_name}</p>
                    <p>Espero que você esteja bem. Este é apenas um lembrete de que o pagamento da fatura {invoice_number} total dueAmount {payment_dueAmount} , que enviamos em {payment_date} vence hoje.</p>
                    <p>Você pode fazer o pagamento na conta bancária especificada na fatura.</p>
                    <p>Tenho certeza de que você está ocupado, mas agradeceria se pudesse reservar um momento e dar uma olhada na fatura quando tiver uma chance.</p>
                    <p>Se você tiver alguma dúvida, responda e terei prazer em esclarecê-la.</p>
                    <p>&nbsp;</p>
                    <p>Obrigado,&nbsp;</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>',
                ],
            ],
            'proposal_sent' => [
                'subject' => 'Proposal Sent',
                'lang' => [
                    'ar' => '<p>مرحبا ، { proposal_name }</p>
                    <p>أتمنى أن يجدك هذا البريد الإلكتروني جيدا برجاء الرجوع الى رقم الاقتراح المرفق { proposal_number } للمنتج / الخدمة.</p>
                    <p>اضغط ببساطة على الاختيار بأسفل</p>
                    <p>{ proposal_url }</p>
                    <p>إشعر بالحرية للوصول إلى الخارج إذا عندك أي أسئلة.</p>
                    <p>شكرا لعملك ! !</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'da' => '<p>Hej, {proposal__name }</p>
                    <p>H&aring;ber denne e-mail finder dig godt! Se det vedh&aelig;ftede forslag nummer { proposal_number } for product/service.</p>
                    <p>klik bare p&aring; knappen nedenfor</p>
                    <p>{ proposal_url }</p>
                    <p>Du er velkommen til at r&aelig;kke ud, hvis du har nogen sp&oslash;rgsm&aring;l.</p>
                    <p>Tak for din virksomhed!</p>
                    <p>&nbsp;</p>
                    <p>Med venlig hilsen</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'de' => '<p>Hi, {proposal_name}</p>
                    <p>Hoffe, diese E-Mail findet dich gut! Bitte sehen Sie die angeh&auml;ngte Vorschlagsnummer {proposal_number} f&uuml;r Produkt/Service an.</p>
                    <p>Klicken Sie einfach auf den Button unten</p>
                    <p>{proposal_url}</p>
                    <p>F&uuml;hlen Sie sich frei, wenn Sie Fragen haben.</p>
                    <p>Vielen Dank f&uuml;r Ihr Unternehmen!!</p>
                    <p>&nbsp;</p>
                    <p>Betrachtet,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'en' => '<p>Hi, {proposal_name}</p>
                    <p>Hope this email ﬁnds you well! Please see attached proposal number {proposal_number} for product/service.</p>
                    <p>simply click on the button below</p>
                    <p>{proposal_url}</p>
                    <p>Feel free to reach out if you have any questions.</p>
                    <p>Thank you for your business!!</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'es' => '<p>Hi, {proposal_name}</p>
                    <p>&iexcl;Espero que este email le encuentre bien! Consulte el n&uacute;mero de propuesta adjunto {proposal_number} para el producto/servicio.</p>
                    <p>simplemente haga clic en el bot&oacute;n de abajo</p>
                    <p>{proposal_url}</p>
                    <p>Si&eacute;ntase libre de llegar si usted tiene alguna pregunta.</p>
                    <p>&iexcl;Gracias por su negocio!!</p>
                    <p>&nbsp;</p>
                    <p>Considerando,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'fr' => '<p>Salut, {proposal_name}</p>
                    <p>Jesp&egrave;re que ce courriel vous trouve bien ! Veuillez consulter le num&eacute;ro de la proposition jointe {proposal_number} pour le produit/service.</p>
                    <p>Il suffit de cliquer sur le bouton ci-dessous</p>
                    <p>{proposal_url}</p>
                    <p>Nh&eacute;sitez pas &agrave; nous contacter si vous avez des questions.</p>
                    <p>Merci pour votre entreprise ! !</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'it' => '<p>Ciao, {proposal_name}</p>
                    <p>Spero che questa email ti trovi bene! Si prega di consultare il numero di proposta allegato {proposal_number} per il prodotto/servizio.</p>
                    <p>semplicemente clicca sul pulsante sottostante</p>
                    <p>{proposal_url}</p>
                    <p>Sentiti libero di raggiungere se hai domande.</p>
                    <p>Grazie per il tuo business!!</p>
                    <p>&nbsp;</p>
                    <p>Riguardo,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'ja' => '<p>こんにちは、 {proposal_name}</p>
                    <p>この E メールでよくご確認ください。 製品 / サービスの添付されたプロポーザル番号 {proposal_number} を参照してください。</p>
                    <p>下のボタンをクリックするだけで</p>
                    <p>{proposal_url}</p>
                    <p>質問がある場合は、自由に連絡してください。</p>
                    <p>お客様のビジネスに感謝します。</p>
                    <p>&nbsp;</p>
                    <p>よろしく</p>
                    <p>{ company_name}</p>
                    <p>{app_url}</p>',
                    'nl' => '<p>Hallo, {proposal_name}</p>
                    <p>Hoop dat deze e-mail je goed vindt! Zie bijgevoegde nummer { proposal_number } voor product/service.</p>
                    <p>gewoon klikken op de knop hieronder</p>
                    <p>{ proposal_url }</p>
                    <p>Voel je vrij om uit te reiken als je vragen hebt.</p>
                    <p>Dank u voor uw bedrijf!!</p>
                    <p>&nbsp;</p>
                    <p>Betreft:</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pl' => '<p>Witaj, {proposal_name}</p>
                    <p>Mam nadzieję, że ta wiadomość znajdzie Cię dobrze! Proszę zapoznać się z załączonym numerem wniosku {proposal_number} dla produktu/usługi.</p>
                    <p>po prostu kliknij na przycisk poniżej</p>
                    <p>{proposal_url}</p>
                    <p>Czuj się swobodnie, jeśli masz jakieś pytania.</p>
                    <p>Dziękujemy za prowadzenie działalności!!</p>
                    <p>&nbsp;</p>
                    <p>W odniesieniu do</p>
                    <p>{company_name }</p>
                    <p>{app_url }</p>',
                    'ru' => '<p>Здравствуйте, { proposal_name }</p>
                    <p>Надеюсь, это электронное письмо найдет вас хорошо! См. вложенное предложение номер { proposal_number} для product/service.</p>
                    <p>просто нажмите на кнопку внизу</p>
                    <p>{ proposal_url}</p>
                    <p>Не стеснитесь, если у вас есть вопросы.</p>
                    <p>Спасибо за ваше дело!</p>
                    <p>&nbsp;</p>
                    <p>С уважением,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pt' => '<p>Oi, {proposal_name}</p>
                    <p>Espero que este e-mail encontre voc&ecirc; bem! Por favor, consulte o n&uacute;mero da proposta anexada {proposal_number} para produto/servi&ccedil;o.</p>
                    <p>basta clicar no bot&atilde;o abaixo</p>
                    <p>{proposal_url}</p>
                    <p>Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</p>
                    <p>Obrigado pelo seu neg&oacute;cio!!</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'tr' => '<p>MERHABA, {proposal_name}</p>
                    <p>Umarım bu e-posta sizi iyi bulur! Lütfen ürün/hizmet için ekteki {proposal_number} numaralı teklife bakın.</p>
                    <p>aşağıdaki butona tıklamanız yeterli</p>
                    <p>{proposal_url}</p>
                    <p>Herhangi bir sorunuz varsa çekinmeden bize ulaşın.</p>
                    <p>İşiniz için teşekkür ederim!!</p>
                    <p>&nbsp;</p>
                    <p>Saygılarımızla,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'zh' => '<p>你好, {proposal_name}</p>
                    <p>希望这封电子邮件能让您满意！请参阅随附的产品/服务提案编号 {proposal_number}。</p>
                    <p>只需点击下面的按钮</p>
                    <p>{proposal_url}</p>
                    <p>如果您有任何疑问，请随时与我们联系。</p>
                    <p>感谢您的业务！！</p>
                    <p>&nbsp;</p>
                    <p>问候,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'he' => '<p>היי, {proposal_name}</p>
                    <p>מקווה שהמייל הזה ימצא אותך טוב! ראה את מספר ההצעה המצורפת {proposal_number} עבור מוצר/שירות.</p>
                    <p>פשוט לחץ על הכפתור למטה</p>
                    <p>{proposal_url}</p>
                    <p>אל תהסס לפנות אם יש לך שאלות.</p>
                    <p>תודה לך על העסק שלך!!</p>
                    <p>&nbsp;</p>
                    <p>בברכה,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'pt-br' => '<p>Oi, {proposal_name}</p>
                    <p>Espero que este e-mail o encontre bem! Consulte o número da proposta em anexo {proposal_number} para produto/serviço.</p>
                    <p>Basta clicar no botão abaixo/p>
                    <p>{proposal_url}</p>
                    <p>Sinta-se à vontade para entrar em contato se tiver alguma dúvida.</p>
                    <p>TAgradeço pelos seus serviços!!</p>
                    <p>&nbsp;</p>
                    <p>Cumprimentos,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                ],
            ],
            'user_created' => [
                'subject' => 'User Created',
                'lang' => [
                    'ar' => '<p>مرحبا ، مرحبا بك في { app_name }.</p>
                    <p>البريد الالكتروني : { email }</p>
                    <p>كلمة السرية : { password }</p>
                    <p>{ app_url }</p>
                    <p>شكرا</p>
                    <p>{ app_name }</p>',
                    'da' => '<p>Hej,</p>
                    <p>velkommen til { app_name }.</p>
                    <p>E-mail: { email }</p>
                    <p>-kodeord: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>Tak.</p>
                    <p>{ app_name }</p>',
                    'de' => '<p>Hallo, Willkommen bei {app_name}.</p>
                    <p>E-Mail: {email}</p>
                    <p>Kennwort: {password}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>
                    <p>Danke,</p>
                    <p>{app_name}</p>',
                    'en' => '<p>Hello,&nbsp;<br />Welcome to {app_name}.</p>
                    <p><strong>Email&nbsp;</strong>: {email}<br /><strong>Password</strong>&nbsp;: {password}</p>
                    <p>{app_url}</p>
                    <p>Thanks,<br />{app_name}</p>',
                    'es' => '<p>Hola, Bienvenido a {app_name}.</p>
                    <p>Correo electr&oacute;nico: {email}</p>
                    <p>Contrase&ntilde;a: {password}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>
                    <p>Gracias,</p>
                    <p>{app_name}</p>',
                    'fr' => '<p>Bonjour, Bienvenue dans { app_name }.</p>
                    <p>E-mail: { email }</p>
                    <p>Mot de passe: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>Merci,</p>
                    <p>{ app_name }</p>',
                    'it' => '<p>Ciao, Benvenuti in {app_name}.</p>
                    <p>Email: {email}</p>
                    <p>Password: {password}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>
                    <p>Grazie,</p>
                    <p>{app_name}</p>',
                    'ja' => '<p>こんにちは、 {app_name}へようこそ。</p>
                    <p>E メール : {email}</p>
                    <p>パスワード : {password}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>
                    <p>ありがとう。</p>
                    <p>{app_name}</p>',
                    'nl' => '<p>Hallo, Welkom bij { app_name }.</p>
                    <p>E-mail: { email }</p>
                    <p>Wachtwoord: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>Bedankt.</p>
                    <p>{ app_name }</p>',
                    'pl' => '<p>Witaj, Witamy w aplikacji {app_name }.</p>
                    <p>E-mail: {email }</p>
                    <p>Hasło: {password }</p>
                    <p>{app_url }</p>
                    <p>&nbsp;</p>
                    <p>Dziękuję,</p>
                    <p>{app_name }</p>',
                    'ru' => '<p>Здравствуйте, Добро пожаловать в { app_name }.</p>
                    <p>Адрес электронной почты: { email }</p>
                    <p>Пароль: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>Спасибо.</p>
                    <p>{app_name }</p>',
                    'pt' => '<p>Ol&aacute;, Bem-vindo a {app_name}.</p>
                    <p>E-mail: {email}</p>
                    <p>Senha: {password}</p>
                    <p>{app_url}</p>
                    <p>&nbsp;</p>
                    <p>Obrigado,</p>
                    <p>{app_name}</p>',
                    'tr' => '<p>Merhaba,</p>
                    <p>Hoşgeldiniz { app_name }.</p>
                    <p>e-posta: { email }</p>
                    <p>-şifre: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>Teşekkürler.</p>
                    <p>{ app_name }</p>',
                    'zh' => '<p>你好,</p>
                    <p>欢迎来到 { app_name }.</p>
                    <p>电子邮件: { email }</p>
                    <p>-密码： { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>谢谢.</p>
                    <p>{ app_name }</p>',
                    'he' => '<p>שלום,</p>
                    <p>ברוך הבא ל { app_name }.</p>
                    <p>אימייל: { email }</p>
                    <p>-סיסמה: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>תודה.</p>
                    <p>{ app_name }</p>',
                    'pt-br' => '<p>Olá,</p>
                    <p>bem-vindo ao { app_name }.</p>
                    <p>E-mail: { email }</p>
                    <p>-senha: { password }</p>
                    <p>{ app_url }</p>
                    <p>&nbsp;</p>
                    <p>Obrigado.</p>
                    <p>{ app_name }</p>',

                ],
            ],
            'vendor_bill_sent' => [
                'subject' => 'Vendor Bill Sent',
                'lang' => [
                    'ar' => '<p>مرحبا ، { bill_name }</p>
                    <p>مرحبا بك في { app_name }</p>
                    <p>أتمنى أن يجدك هذا البريد الإلكتروني جيدا ! ! برجاء الرجوع الى رقم الفاتورة الملحقة { bill_number } للحصول على المنتج / الخدمة.</p>
                    <p>ببساطة اضغط على الاختيار بأسفل.</p>
                    <p>{ bill_url }</p>
                    <p>إشعر بالحرية للوصول إلى الخارج إذا عندك أي أسئلة.</p>
                    <p>شكرا لك</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'da' => '<p>Hej, { bill_name }</p>
                    <p>Velkommen til { app_name }</p>
                    <p>H&aring;ber denne e-mail finder dig godt! Se vedlagte fakturanummer } { bill_number } for product/service.</p>
                    <p>Klik p&aring; knappen nedenfor.</p>
                    <p>{ bill_url }</p>
                    <p>Du er velkommen til at r&aelig;kke ud, hvis du har nogen sp&oslash;rgsm&aring;l.</p>
                    <p>Tak.</p>
                    <p>&nbsp;</p>
                    <p>Med venlig hilsen</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'de' => '<p>Hi, {bill_name}</p>
                    <p>Willkommen bei {app_name}</p>
                    <p>Hoffe, diese E-Mail findet dich gut!! Sehen Sie sich die beigef&uuml;gte Rechnungsnummer {bill_number} f&uuml;r Produkt/Service an.</p>
                    <p>Klicken Sie einfach auf den Button unten.</p>
                    <p>{bill_url}</p>
                    <p>F&uuml;hlen Sie sich frei, wenn Sie Fragen haben.</p>
                    <p>Vielen Dank,</p>
                    <p>&nbsp;</p>
                    <p>Betrachtet,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'en' => '<p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hi, {bill_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Welcome to {app_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Hope this email finds you well!! Please see attached bill number {bill_number} for product/service.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Simply click on the button below.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{bill_url}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Feel free to reach out if you have any questions.</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Thank You,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">Regards,</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{company_name}</span></p>
                    <p style="line-height: 28px; font-family: Nunito,;"><span style="font-family: sans-serif;">{app_url}</span></p>',
                    'es' => '<p>Hi, {bill_name}</p>
                    <p>Bienvenido a {app_name}</p>
                    <p>&iexcl;Espero que este correo te encuentre bien!! Consulte el n&uacute;mero de factura adjunto {bill_number} para el producto/servicio.</p>
                    <p>Simplemente haga clic en el bot&oacute;n de abajo.</p>
                    <p>{bill_url}</p>
                    <p>Si&eacute;ntase libre de llegar si usted tiene alguna pregunta.</p>
                    <p>Gracias,</p>
                    <p>&nbsp;</p>
                    <p>Considerando,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'fr' => '<p>Salut, { bill_name }</p>
                    <p>Bienvenue dans { app_name }</p>
                    <p>Jesp&egrave;re que ce courriel vous trouve bien ! ! Veuillez consulter le num&eacute;ro de facture { bill_number } associ&eacute; au produit / service.</p>
                    <p>Cliquez simplement sur le bouton ci-dessous.</p>
                    <p>{bill_url }</p>
                    <p>Nh&eacute;sitez pas &agrave; nous contacter si vous avez des questions.</p>
                    <p>Merci,</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'it' => '<p>Ciao, {bill_name}</p>
                    <p>Benvenuti in {app_name}</p>
                    <p>Spero che questa email ti trovi bene!! Si prega di consultare il numero di fattura allegato {bill_number} per il prodotto/servizio.</p>
                    <p>Semplicemente clicca sul pulsante sottostante.</p>
                    <p>{bill_url}</p>
                    <p>Sentiti libero di raggiungere se hai domande.</p>
                    <p>Grazie,</p>
                    <p>&nbsp;</p>
                    <p>Riguardo,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'ja' => '<p>こんにちは、 {bill_name}</p>
                    <p>{app_name} へようこそ</p>
                    <p>この E メールによりよく検出されます !! 製品 / サービスの添付された請求番号 {bill_number} を参照してください。</p>
                    <p>以下のボタンをクリックしてください。</p>
                    <p>{bill_url}</p>
                    <p>質問がある場合は、自由に連絡してください。</p>
                    <p>ありがとうございます</p>
                    <p>&nbsp;</p>
                    <p>よろしく</p>
                    <p>{ company_name}</p>
                    <p>{app_url}</p>',
                    'nl' => '<p>Hallo, { bill_name }</p>
                    <p>Welkom bij { app_name }</p>
                    <p>Hoop dat deze e-mail je goed vindt!! Zie bijgevoegde factuurnummer { bill_number } voor product/service.</p>
                    <p>Klik gewoon op de knop hieronder.</p>
                    <p>{ bill_url }</p>
                    <p>Voel je vrij om uit te reiken als je vragen hebt.</p>
                    <p>Dank U,</p>
                    <p>&nbsp;</p>
                    <p>Betreft:</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pl' => '<p>Witaj, {bill_name }</p>
                    <p>Witamy w aplikacji {app_name }</p>
                    <p>Mam nadzieję, że ta wiadomość e-mail znajduje Cię dobrze!! Zapoznaj się z załączonym numerem rachunku {bill_number } dla produktu/usługi.</p>
                    <p>Wystarczy kliknąć na przycisk poniżej.</p>
                    <p>{bill_url}</p>
                    <p>Czuj się swobodnie, jeśli masz jakieś pytania.</p>
                    <p>Dziękuję,</p>
                    <p>&nbsp;</p>
                    <p>W odniesieniu do</p>
                    <p>{company_name }</p>
                    <p>{app_url }</p>',
                    'ru' => '<p>Привет, { bill_name }</p>
                    <p>Вас приветствует { app_name }</p>
                    <p>Надеюсь, это письмо найдет вас хорошо! См. прилагаемый номер счета { bill_number } для product/service.</p>
                    <p>Просто нажмите на кнопку внизу.</p>
                    <p>{ bill_url }</p>
                    <p>Не стеснитесь, если у вас есть вопросы.</p>
                    <p>Спасибо.</p>
                    <p>&nbsp;</p>
                    <p>С уважением,</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pt' => '<p>Oi, {bill_name}</p>
                    <p>Bem-vindo a {app_name}</p>
                    <p>Espero que este e-mail encontre voc&ecirc; bem!! Por favor, consulte o n&uacute;mero de faturamento conectado {bill_number} para produto/servi&ccedil;o.</p>
                    <p>Basta clicar no bot&atilde;o abaixo.</p>
                    <p>{bill_url}</p>
                    <p>Sinta-se &agrave; vontade para alcan&ccedil;ar fora se voc&ecirc; tiver alguma d&uacute;vida.</p>
                    <p>Obrigado,</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>{company_name}</p>
                    <p>{app_url}</p>',
                    'tr' => '<p>Merhaba, { bill_name }</p>
                    <p>Hoşgeldiniz { app_name }</p>
                    <p>Umarım bu e-posta sizi iyi bulur! Ürün/hizmet için ekteki fatura numarasına bakın } { fatura_numarası }.</p>
                    <p>Aşağıdaki düğmeyi tıklayın.</p>
                    <p>{ bill_url }</p>
                    <p>Herhangi bir sorunuz varsa çekinmeden bize ulaşın.</p>
                    <p>Teşekkürler.</p>
                    <p>&nbsp;</p>
                    <p>Saygılarımızla</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'zh' => '<p>你好, { bill_name }</p>
                    <p>欢迎来到 { app_name }</p>
                    <p>希望这封电子邮件能让您满意！请参阅随附的产品/服务发票编号 } { bill_number }。</p>
                    <p>单击下面的按钮。</p>
                    <p>{ bill_url }</p>
                    <p>Du er velkommen til at r&aelig;kke ud, hvis du har nogen sp&oslash;rgsm&aring;l.</p>
                    <p>谢谢.</p>
                    <p>&nbsp;</p>
                    <p>最诚挚的问候 </p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'he' => '<p>שלום, { bill_name }</p>
                    <p>ברוך הבא ל { app_name }</p>
                    <p>מקווה שהמייל הזה ימצא אותך טוב! ראה את מספר החשבונית המצורפת } { bill_number } למוצר/שירות.</p>
                    <p>לחץ על הכפתור למטה.</p>
                    <p>{ bill_url }</p>
                    <p>אל תהסס לפנות אם יש לך שאלות.</p>
                    <p>תודה.</p>
                    <p>&nbsp;</p>
                    <p>איחוליי הלבביים</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                    'pt-br' => '<p>Olá, { bill_name }</p>
                    <p>Bem-vindo ao { app_name }</p>
                    <p>Espero que este e-mail o encontre bem! Veja o número da fatura em anexo } { bill_number } para produto/serviço.</p>
                    <p>Clique no botão abaixo.</p>
                    <p>{ bill_url }</p>
                    <p>Sinta-se à vontade para entrar em contato se tiver alguma dúvida.</p>
                    <p>Obrigado.</p>
                    <p>&nbsp;</p>
                    <p>Com os melhores votos</p>
                    <p>{ company_name }</p>
                    <p>{ app_url }</p>',
                ],
            ],
            'new_contract' => [
                'subject' => 'New Contract',
                'lang' => [
                    'ar' => '<p>مرحبا { contract_customer }</p>
                    <p>موضوع العقد : { contract_subject }</p>
                    <p>نوع العقد : { contract_type }</p>
                    <p>قيمة العقد : { contract_value }</p>
                    <p>تاريخ البدء : { contract_start_date }</p>
                    <p>تاريخ الانتهاء : { contract_end_date }</p>
                    <p>. أتطلع لسماع منك</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>',
                    'da' => '<p>Hej { contract_customer }</p>
                    <p>Kontraktemne: { contract_subject }</p>
                    <p>Kontrakttype: { contract_type }</p>
                    <p>Kontraktv&aelig;rdi: { contract_value }</p>
                    <p>Startdato: { contract_start_date }</p>
                    <p>Slutdato: { contract_end_date }</p>
                    <p>Jeg gl&aelig;der mig til at h&oslash;re fra dig.</p>
                    <p>&nbsp;</p>
                    <p>Med venlig hilsen</p>
                    <p>{ company_name }</p>',
                    'de' => '<p>Hi {contract_customer}</p>
                    <p>Vertragsgegenstand: {contract_subject}</p>
                    <p>Vertragstyp: {contract_type}</p>
                    <p>Vertragswert: {contract_value}</p>
                    <p>Startdatum: {contract_start_date}</p>
                    <p>Enddatum: {contract_end_date}</p>
                    <p>Freuen Sie sich auf das H&ouml;ren von Ihnen.</p>
                    <p>&nbsp;</p>
                    <p>Betrachtet,</p>
                    <p>{company_name}</p>',
                    'es' => '<p>Hi {contract_customer}</p>
                    <p>Asunto del contrato: {contract_subject}</p>
                    <p>Tipo de contrato: {contract_type}</p>
                    <p>Valor de contrato: {contract_value}</p>
                    <p>Fecha de inicio: {contract_start_date}</p>
                    <p>Fecha de finalizaci&oacute;n: {contract_end_date}</p>
                    <p>Con ganas de escuchar de ti.</p>
                    <p>&nbsp;</p>
                    <p>Considerando,</p>
                    <p>{company_name}</p>',
                    'en' => '<p>Hi {contract_customer}</p>
                    <p>Contract Subject: {contract_subject}</p>
                    <p>Contract Type: {contract_type}</p>
                    <p>Contract Value: {contract_value}</p>
                    <p>Start Date: {contract_start_date}</p>
                    <p>End Date: {contract_end_date}</p>
                    <p>Looking forward to hear from you.</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{company_name}</p>',
                    'fr' => '<p>Bonjour { contract_customer }</p>
                    <p>Objet du contrat: { contract_subject }</p>
                    <p>Type de contrat: { contract_type }</p>
                    <p>Valeur du contrat: { contract_value }</p>
                    <p>Date de d&eacute;but: { contract_start_date }</p>
                    <p>Date de fin: { contract_end_date }</p>
                    <p>Vous avez h&acirc;te de vous entendre.</p>
                    <p>&nbsp;</p>
                    <p>Regards,</p>
                    <p>{ company_name }</p>',
                    'it' => '<p>Ciao {contract_customer}</p>
                    <p>Oggetto contratto: {contract_subject}</p>
                    <p>Tipo di contratto: {contract_type}</p>
                    <p>Valore contratto: {contract_value}</p>
                    <p>Data inizio: {contract_start_date}</p>
                    <p>Data di fine: {contract_end_date}</p>
                    <p>Non vedo lora di sentirti.</p>
                    <p>&nbsp;</p>
                    <p>Riguardo,</p>
                    <p>{company_name}</p>',
                    'ja' => '<p>こんにちは {contract_customer }</p>
                    <p>契約件名: {contract_subject}</p>
                    <p>契約タイプ: {contract_type}</p>
                    <p>契約値: {contract_value}</p>
                    <p>開始日: {contract_start_date}</p>
                    <p>終了日: {contract_end_date}</p>
                    <p>あなたからの便りを楽しみにしています</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>よろしく</p>
                    <p>{ company_name}</p>',
                    'nl' => '<p>Hallo { contract_customer }</p>
                    <p>Contractonderwerp: { contract_subject }</p>
                    <p>Contracttype: { contract_type }</p>
                    <p>Contractwaarde: { contract_value }</p>
                    <p>Begindatum: { contract_start_date }</p>
                    <p>Einddatum: { contract_end_date }</p>
                    <p>Ik kijk ernaar uit om van je te horen.</p>
                    <p>&nbsp;</p>
                    <p>Betreft:</p>
                    <p>{ company_name }</p>',
                    'pl' => '<p>Witaj {contract_customer }</p>
                    <p>Temat kontraktu: {contract_subject }</p>
                    <p>Typ kontraktu: {contract_type }</p>
                    <p>Wartość kontraktu: {contract_value }</p>
                    <p>Data rozpoczęcia: {contract_start_date }</p>
                    <p>Data zakończenia: {contract_end_date }</p>
                    <p>Nie mogę się doczekać, by usłyszeć od ciebie.</p>
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <p>W odniesieniu do</p>
                    <p>{company_name }</p>',
                    'pt' => '<p>Oi {contract_customer}</p>
                    <p>Assunto do Contrato: {contract_subject}</p>
                    <p>Tipo de Contrato: {contract_type}</p>
                    <p>Valor do Contrato: {contract_value}</p>
                    <p>Data de In&iacute;cio: {contract_start_date}</p>
                    <p>Data de encerramento: {contract_end_date}</p>
                    <p>Olhando para a frente para ouvir de voc&ecirc;.</p>
                    <p>&nbsp;</p>
                    <p>Considera,</p>
                    <p>{company_name}</p>',
                    'ru' => '<p>Здравствуйте { contract_customer }</p>
                    <p>Тема договора: { contract_subject }</p>
                    <p>Тип контракта: { contract_type }</p>
                    <p>Значение контракта: { contract_value }</p>
                    <p>Дата начала: { contract_start_date }</p>
                    <p>Дата окончания: { contract_end_date }</p>
                    <p>С нетерпением жду услышать от тебя.</p>
                    <p>&nbsp;</p>
                    <p>С уважением,</p>
                    <p>{ company_name }</p>',
                    'tr' => '<p>MERHABA {contract_customer}</p>
                    <p>Sözleşme Konusu: {contract_subject}</p>
                    <p>sözleşme tipi: {contract_type}</p>
                    <p>Sözleşme Değeri: {contract_value}</p>
                    <p>Başlangıç ​​tarihi: {contract_start_date}</p>
                    <p>Bitiş tarihi: {contract_end_date}</p>
                    <p>Sizden haber bekliyorum.</p>
                    <p>&nbsp;</p>
                    <p>Saygılarımızla,</p>
                    <p>{company_name}</p>',
                    'zh' => '<p>你好 {contract_customer}</p>
                    <p>合同主体: {contract_subject}</p>
                    <p>合同类型: {contract_type}</p>
                    <p>合约价值: {contract_value}</p>
                    <p>开始日期: {contract_start_date}</p>
                    <p>结束日期: {contract_end_date}</p>
                    <p>期待着听到您的意见。</p>
                    <p>&nbsp;</p>
                    <p>问候,</p>
                    <p>{company_name}</p>',
                    'he' => '<p>היי {contract_customer}</p>
                    <p>נושא החוזה: {contract_subject}</p>
                    <p>סוג חוזה: {contract_type}</p>
                    <p>ערך חוזה: {contract_value}</p>
                    <p>תאריך התחלה: {contract_start_date}</p>
                    <p>תאריך סיום: {contract_end_date}</p>
                    <p>מצפה לשמוע ממך.</p>
                    <p>&nbsp;</p>
                    <p>בברכה,</p>
                    <p>{company_name}</p>',
                    'pt-br' => '<p>Oi {contract_customer}</p>
                    <p>Assunto do Contrato: {contract_subject}</p>
                    <p>Tipo de Contrato: {contract_type}</p>
                    <p>Valor do Contrato: {contract_value}</p>
                    <p>Data de início: {contract_start_date}</p>
                    <p>Dados finais: {contract_end_date}</p>
                    <p>Ansioso para ouvir de você.</p>
                    <p>&nbsp;</p>
                    <p>Cumprimentos,</p>
                    <p>{company_name}</p>',
                ],

            ],
            'retainer_sent' => [
                'subject' => 'Retainer Sent',
                'lang' => [
                    'ar' => '<p>مرحبًا ، {retainer_name}</p><p>آمل أن يكون هذا البريد الإلكتروني جيدًا! يرجى الاطلاع على رقم التجنيب المرفق {retainer_number} للمنتج/الخدمة.</p><p>ببساطة انقر على الزر أدناه</p><p>{retainer_url}</p><p>لا تتردد في التواصل إذا كان لديك أي أسئلة.</p><p>شكرا لك على عملك!!</p><p>&nbsp;</p><p>يعتبر،</p><p>{company_name}</p><p>{app_url}</p>',
                    'da' => '<p>Hej, {retainer_name}</p><p>H&aring;ber denne e -mail finder dig godt! Se vedh&aelig;ftet indehavernummer {retainer_number} for produkt/service.</p><p>Klik blot p&aring; knappen nedenfor</p><p>{retainer_url}</p><p>Du er velkommen til at n&aring; ud, hvis du har sp&oslash;rgsm&aring;l.</p><p>Tak for din forretning!!</p><p>&nbsp;</p><p>Hilsen,</p><p>{company_name}</p><p>{app_url}</p>',
                    'de' => '<p>Hi, {retainer_name}</p><p>Ich hoffe, diese E -Mail findet Sie gut! Bitte beachten Sie die beigef&uuml;gte Retainer -Nummer {retainer_number} f&uuml;r Produkt/Dienstleistung.</p><p>Klicken Sie einfach auf die Schaltfl&auml;che unten</p><p>{retainer_url}</p><p>F&uuml;hlen Sie sich frei zu erreichen, wenn Sie Fragen haben.</p><p>Danke f&uuml;r dein Gesch&auml;ft !!</p><p>&nbsp;</p><p>Gr&uuml;&szlig;e,</p><p>{company_name}</p><p>{app_url}</p>',
                    'es' => '<p>Hola, {retainer_name}</p><p>&iexcl;Espero que este correo electr&oacute;nico te encuentre bien! Consulte el n&uacute;mero de retenci&oacute;n adjunto {retainer_number} para producto/servicio.</p><p>Simplemente haga clic en el bot&oacute;n de abajo</p><p>{retainer_url}</p><p>No dude en comunicarse si tiene alguna pregunta.</p><p>&iexcl;&iexcl;Gracias por hacer negocios!!</p><p>&nbsp;</p><p>Saludos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'en' => '<p>Hi, {retainer_name}</p><p>Hope this email ﬁnds you well! Please see attached retainer number {retainer_number} for product/service.</p><p>simply click on the button below</p><p>{retainer_url}</p><p>Feel free to reach out if you have any questions.</p><p>Thank you for your business!!</p><p>&nbsp;</p><p>Regards,</p><p>{company_name}</p><p>{app_url}</p>',
                    'fr' => '<p>Salut, {retainer_name}</p><p>J\'esp&egrave;re que cet e-mail vous trouve bien! Veuillez consulter le num&eacute;ro de dispositif ci-joint {retainer_number} pour le produit / service.</p><p>Cliquez simplement sur le bouton ci-dessous</p><p>{retainer_url}</p><p>N\'h&eacute;sitez pas &agrave; tendre la main si vous avez des questions.</p><p>Merci pour votre entreprise !!</p><p>&nbsp;</p><p>Salutations,</p><p>{company_name}</p><p>{app_url}</p>',
                    'it' => '<p>Ciao, {retainer_name}</p><p>Spero che questa e -mail ti faccia bene! Si prega di consultare il numero di fermo allegato {retainer_number} per prodotto/servizio.</p><p>Basta fare clic sul pulsante in basso</p><p>{retainer_url}</p><p>Sentiti libero di contattare se hai domande.</p><p>Grazie per il tuo business!!</p><p>&nbsp;</p><p>Saluti,</p><p>{company_name}</p><p>{app_url}</p>',
                    'ja' => '<p>こんにちは、{retainer_name}</p><p>この電子メールがあなたをうまく見つけることを願っています！製品/サービスについては、添付のリテーナー番号{retainer_number}を参照してください。</p><p>下のボタンをクリックするだけです</p><p>{retainer_url}</p><p>ご質問がある場合は、お気軽にご連絡ください。</p><p>お買い上げくださってありがとうございます！！</p><p>&nbsp;</p><p>よろしく、</p><p>{company_name}</p><p>{app_url}</p>',
                    'nl' => '<p>Hallo, {retainer_Name}</p><p>Ik hoop dat deze e -mail je goed vindt! Zie bijgevoegd bewaarnummer {retainer_number} voor product/service.</p><p>Klik eenvoudig op de onderstaande knop</p><p>{retainer_url}</p><p>Voel je vrij om contact op te nemen als je vragen hebt.</p><p>Bedankt voor uw zaken!!</p><p>&nbsp;</p><p>Groeten,</p><p>{company_name}</p><p>{app_url}</p>',
                    'pl' => '<p>Cześć, {retainer_name}</p><p>Mam nadzieję, że ten e -mail dobrze Cię znajdzie! Aby uzyskać produkt/usługę/usługi.</p><p>Po prostu kliknij przycisk poniżej</p><p>{retainer_url}</p><p>Możesz się skontaktować, jeśli masz jakieś pytania.</p><p>Dziękuję za Tw&oacute;j biznes !!</p><p>&nbsp;</p><p>Pozdrowienia,</p><p>{company_name}</p><p>{app_url}</p>',
                    'pt' => '<p>Oi, {retainer_name}</p><p>Espero que este e -mail o encontre bem! Consulte o n&uacute;mero do retentor anexado {retainer_number} para obter o produto/servi&ccedil;o.</p><p>Basta clicar no bot&atilde;o abaixo</p><p>{retainer_url}</p><p>Sinta -se &agrave; vontade para alcan&ccedil;ar se tiver alguma d&uacute;vida.</p><p>Agrade&ccedil;o pelos seus servi&ccedil;os!!</p><p>&nbsp;</p><p>Cumprimentos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'ru' => '<p>Привет, {retainer_name}</p><p>Надеюсь, что это электронное письмо вам хорошо найдет! Пожалуйста, см. Прикрепленный номер фиксатора {retainer_number} для продукта/услуги.</p><p>Просто нажмите на кнопку ниже</p><p>{retainer_url}</p><p>Не стесняйтесь обращаться, если у вас есть какие -либо вопросы.</p><p>Спасибо за ваш бизнес !!</p><p>&nbsp;</p><p>С уважением,</p><p>{company_name}</p><p>{app_url}</p>',
                    'tr' => '<p>Merhaba {retainer_name}</p><p>Umarım bu e-posta sizi bulur! Lütfen ürün/hizmet için ekteki hizmetli numarasına {retainer_number} bakın.</p><p>aşağıdaki düğmeyi tıklamanız yeterlidir</p><p>{retainer_url}</p><p>İsterseniz bize ulaşmaktan çekinmeyin herhangi bir sorunuz var.</p><p>İşletmeniz için teşekkür ederiz!</p><p> </p><p>Saygılarımızla,</p><p>{company_name}</p><p >{app_url}</p>',
                    'zh' => '<p>您好，{retainer_name}</p><p>希望这封电子邮件能让您满意！请参阅随附的产品/服务保留号 {retainer_number}。</p><p>只需点击下面的按钮</p><p>{retainer_url}</p><p>如果您有任何疑问。</p><p>感谢您的惠顾！！</p><p> </p><p>此致，</p><p>{company_name}</p><p >{app_url}</p>',
                    'he' => '<p>היי, {retainer_name}</p><p>מקווה שדוא"ל זה ימצא אותך היטב! ראה את מספר השומר המצורף {retainer_number} עבור מוצר/שירות.</p><p>פשוט לחץ על הלחצן למטה</p><p>{retainer_url}</p><p>אל תהסס לפנות אם אתה יש לך שאלות.</p><p>תודה על העסק שלך!</p><p> </p><p>בברכה,</p><p>{company_name}</p><p >{app_url}</p>',
                    'pt-br' => '<p>Olá, {retainer_name}</p><p>Espero que este e-mail o encontre bem! Consulte o número do retentor {retainer_number} em anexo para obter o produto/serviço.</p><p>basta clicar no botão abaixo</p><p>{retainer_url}</p><p>Sinta-se à vontade para entrar em contato se precisar tiver alguma dúvida.</p><p>Obrigado por sua visita!!</p><p> </p><p>Atenciosamente,</p><p>{company_name}</p><p >{app_url}</p>',
                ],

            ],
            'customer_retainer_sent' => [
                'subject' => 'Customer Retainer Sent',
                'lang' => [
                    'ar' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">مرحبًا ، {retainer_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">مرحبا بكم في {app_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">أتمنى حين تصلك رسالتي أن تكون بخير! يرجى الاطلاع على رقم التجنيب المرفق {retainer_number} للمنتج/الخدمة.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">ببساطة انقر على الزر أدناه.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{retainer_url}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">لا تتردد في التواصل إذا كان لديك أي أسئلة.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">شكرا لك،</span></span></p><p>&nbsp;</p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">يعتبر،</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                    'da' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Hej, {retainer_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Velkommen til {app_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">H&aring;ber denne e -mail finder dig godt! Se vedh&aelig;ftet indehavernummer {retainer_number} for produkt/service.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Klik blot p&aring; knappen nedenfor.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{retainer_url}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Du er velkommen til at n&aring; ud, hvis du har sp&oslash;rgsm&aring;l.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Tak skal du have,</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">&nbsp;</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Hilsen,</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                    'de' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Hi, {retainer_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Willkommen bei {app_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Ich hoffe diese email kommt bei dir an! Bitte beachten Sie die beigef&uuml;gte Retainer -Nummer {retainer_number} f&uuml;r Produkt/Dienstleistung.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Klicken Sie einfach auf die Schaltfl&auml;che unten.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{retainer_url}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">F&uuml;hlen Sie sich frei zu erreichen, wenn Sie Fragen haben.</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Danke,</span></span></p><p>&nbsp;</p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Gr&uuml;&szlig;e,</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                    'es' => '<p>Hola, {retainer_name}</p><p>Bienvenido a {app_name}</p><p>&iexcl;Espero que este mensaje te encuentre bien! Consulte el n&uacute;mero de retenci&oacute;n adjunto {retainer_number} para producto/servicio.</p><p>Simplemente haga clic en el bot&oacute;n de abajo.</p><p>{retainer_url}</p><p>No dude en comunicarse si tiene alguna pregunta.</p><p>Gracias,</p><p>&nbsp;</p><p>Saludos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'en' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Hi, {retainer_name}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Welcome to {app_name}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Hope this email finds you well! Please see attached retainer number {retainer_number} for product/service.</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Simply click on the button below.</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">{retainer_url}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Feel free to reach out if you have any questions.</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Thank You,</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Regards,</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">{company_name}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">{app_url}</span></p>',
                    'fr' => '<p>Hola, {retainer_name}</p><p>Bienvenido a {app_name}</p><p>&iexcl;Espero que este mensaje te encuentre bien! Consulte el n&uacute;mero de retenci&oacute;n adjunto {retainer_number} para producto/servicio.</p><p>Simplemente haga clic en el bot&oacute;n de abajo.</p><p>{retainer_url}</p><p>No dude en comunicarse si tiene alguna pregunta.</p><p>Gracias,</p><p>&nbsp;</p><p>Saludos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'it' => '<p>Ciao, {retainer_name}</p><p>Benvenuti in {app_name}</p><p>Spero che questa email ti trovi bene! Si prega di consultare il numero di fermo allegato {retainer_number} per prodotto/servizio.</p><p>Basta fare clic sul pulsante in basso.</p><p>{retainer_url}</p><p>Sentiti libero di contattare se hai domande.</p><p>Grazie,</p><p>&nbsp;</p><p>Saluti,</p><p>{company_name}</p><p>{app_url}</p>',
                    'ja' => '<p>こんにちは、{retainer_name}</p><p>{app_name}へようこそ</p><p>このメールは、あなたがよく見つけた願っています！製品/サービスについては、添付のリテーナー番号{retainer_number}を参照してください。</p><p>下のボタンをクリックするだけです。</p><p>{retainer_url}</p><p>ご質問がある場合は、お気軽にご連絡ください。</p><p>ありがとうございました、</p><p>&nbsp;</p><p>よろしく、</p><p>{company_name}</p><p>{app_url}</p>',
                    'nl' => '<p>Hallo, {retainer_name}</p><p>Welkom bij {app_name}</p><p>Ik hoop dat deze e-mail je goed vindt! Zie bijgevoegde houdernummer {retainer_number} voor product/service.</p><p>Klik eenvoudig op de onderstaande knop.</p><p>{retainer_url}</p><p>Neem gerust contact op als je vragen hebt.</p><p>Bedankt,</p><p>&nbsp;</p><p>Groeten,</p><p>{company_name}</p><p>{app_url}</p>',
                    'pl' => '<p>Cześć, {retainer_name}</p><p>Witamy w {app_name}</p><p>Mam nadzieję, że ten e-mail Cię dobrze odnajdzie! Zobacz załączony numer ustalający {retainer_number} dla produktu/usługi.</p><p>Po prostu kliknij poniższy przycisk.</p><p>{retainer_url}</p><p>Jeśli masz jakiekolwiek pytania, skontaktuj się z nami.</p><p>Dziękuję,</p><p>&nbsp;</p><p>Pozdrowienia,</p><p>{company_name}</p><p>{app_url}</p>',
                    'pt' => '<p>Ol&aacute;, {retainer_name}</p><p>Bem-vindo ao {app_name}</p><p>Espero que este e-mail o encontre bem! Consulte o n&uacute;mero de reten&ccedil;&atilde;o em anexo {retainer_number} para o produto/servi&ccedil;o.</p><p>Basta clicar no bot&atilde;o abaixo.</p><p>{retainer_url}</p><p>Sinta-se &agrave; vontade para entrar em contato se tiver alguma d&uacute;vida.</p><p>Obrigada,</p><p>&nbsp;</p><p>Cumprimentos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'ru' => '<p>Привет, {retainer_name}</p><p>Добро пожаловать в {app_name}</p><p>Надеюсь, это письмо найдет вас хорошо! Пожалуйста, смотрите прилагаемый номер клиента {retainer_number} для продукта/услуги.</p><p>Просто нажмите на кнопку ниже.</p><p>{retainer_url}</p><p>Не стесняйтесь обращаться, если у вас есть какие-либо вопросы.</p><p>Благодарю вас,</p><p>&nbsp;</p><p>С уважением,</p><p>{company_name}</p><p>{app_url}</p>',
                    'tr' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-bitişik harfler: ortak bitişik harfler; arka plan- color: #f8f8f8;\">Merhaba, {retainer_name}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">{app_name}</span></p><p><span style=\'a hoş geldiniz "color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-varyant-bitişik harfler: ortak bitişik harfler; arka plan rengi: #f8f8f8;\"> Umarım bu e-posta sizi iyi bulur! Lütfen ürün/hizmet için ekteki tutucu numarasına {retainer_number} bakın.</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Aşağıdaki düğmeyi tıklamanız yeterlidir.</span></p><p><span style =\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-varyant-bitişik harfler: ortak bitişik harfler; arka plan rengi: #f8f8f8;\ ">{retainer_url}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px ; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Sorularınız varsa bize ulaşmaktan çekinmeyin.</span></p><p><span style=\"color : #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Teşekkürler ,</span></p><p><span style=\"color: #1d1c1d; yazı tipi ailesi: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; yazı tipi boyutu: 15 piksel; font-varyant-bitişik harfler: ortak bitişik harfler; background-color: #f8f8f8;\">Saygılarımızla,</span></p><p><span style=\"color: #1d1c1d; yazı tipi ailesi: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; yazı tipi boyutu: 15 piksel; font-varyant-bitişik harfler: ortak bitişik harfler; background-color: #f8f8f8;\">{şirket_adı}</span></p><p><span style=\"color: #1d1c1d; yazı tipi ailesi: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; yazı tipi boyutu: 15 piksel; font-varyant-bitişik harfler: ortak bitişik harfler; arka plan rengi: #f8f8f8;\">{app_url}</span></p>',
                    'zh' => '<p><span style=\"颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：通用连字；背景-颜色：#f8f8f8;\">嗨，{retainer_name}</span></p><p><span style=\"颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans -serif; 字体大小: 15px; 字体变体连字: common-ligatures; 背景颜色: #f8f8f8;\">欢迎来到 {app_name}</span></p><p><span style=\ “颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：通用连字；背景颜色：#f8f8f8；\">希望这封电子邮件能让您满意！请参阅随附的产品/服务保留号 {retainer_number}。</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato、Slack-Fractions、appleLogo、sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures;background-color: #f8f8f8;\">只需点击下面的按钮即可。</span></p><p><span style =\“颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：通用连字；背景颜色：#f8f8f8；\ ">{retainer_url}</span></p><p><span style=\"color: #1d1c1d; 字体系列: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; 字体大小: 15px ; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">如果您有任何疑问，请随时与我们联系。</span></p><p><span style=\"color ：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：通用连字；背景颜色：#f8f8f8；\">谢谢,</span></p><p><span style=\"颜色:#1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：常见连字；背景颜色：#f8f8f8;\">问候，</span></p><p><span style=\"颜色：#1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：常见连字；背景颜色：#f8f8f8;\">{公司名称}</span></p><p><span style=\"颜色：#1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；字体大小：15px；字体变体连字：常见连字；背景颜色：#f8f8f8;\">{app_url}</span></p>',
                    'he' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background- color: #f8f8f8;\">היי, {retainer_name}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">ברוכים הבאים ל-{app_name}</span></p><p><span style=\ "color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\"> מקווה שהמייל הזה ימצא אותך טוב! אנא עיין במספר המצורף {retainer_number} למוצר/שירות.</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">פשוט לחץ על הכפתור למטה.</span></p><p><span style =\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\ ">{retainer_url}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px ; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">אל תהסס לפנות אם יש לך שאלות.</span></p><p><span style=\"color : #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">תודה לך ,</span></p><p><span style=\"color: #1d1c1d; משפחת גופנים: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; גודל גופן: 15px; גופן-variant-ligatures: ליגטורות נפוצות; background-color: #f8f8f8;\">בברכה,</span></p><p><span style=\"color: #1d1c1d; משפחת גופנים: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; גודל גופן: 15px; גופן-variant-ligatures: ליגטורות נפוצות; background-color: #f8f8f8;\">{company_name}</span></p><p><span style=\"color: #1d1c1d; משפחת גופנים: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; גודל גופן: 15px; גופן-variant-ligatures: ליגטורות נפוצות; רקע-צבע: #f8f8f8;\">{app_url}</span></p>',
                    'pt-br' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; tamanho da fonte: 15px; font-variant-ligatures: common-ligatures; background- color: #f8f8f8;\">Olá, {retainer_name}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Bem-vindo ao {app_name}</span></p><p><span style=\ "color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\"> Espero que este e-mail o encontre bem! Consulte o número do retentor anexado {retainer_number} para produto/serviço.</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans -serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Basta clicar no botão abaixo.</span></p><p><span style =\"cor: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; font-size: 15px; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\ ">{retainer_url}</span></p><p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; tamanho da fonte: 15px ; font-variant-ligatures: common-ligatures; background-color: #f8f8f8;\">Sinta-se à vontade para entrar em contato se tiver alguma dúvida.</span></p><p><span style=\"color : #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; tamanho da fonte: 15px; font-variant-ligatures: common-ligatures; cor de fundo: #f8f8f8;\">Obrigado ,</span></p><p><span style=\"cor: #1d1c1d; família de fontes: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; tamanho da fonte: 15px; ligaduras-variantes de fonte: ligaduras-comuns; background-color: #f8f8f8;\">Atenciosamente,</span></p><p><span style=\"color: #1d1c1d; família de fontes: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; tamanho da fonte: 15px; ligaduras-variantes de fonte: ligaduras-comuns; background-color: #f8f8f8;\">{company_name}</span></p><p><span style=\"color: #1d1c1d; família de fontes: Slack-Lato, Slack-Fractions, appleLogo, sans-serif; tamanho da fonte: 15px; ligaduras-variantes de fonte: ligaduras-comuns; background-color: #f8f8f8;\">{app_url}</span></p>',
                ],

            ],
            'new_retainer_payment' => [
                'subject' => 'New Retainer Payment',
                'lang' => [
                    'ar' => '<p>أهلاً،</p><p>مرحبًا بك في {app_name}</p><p>عزيزي {payment_name}</p><p>لقد استلمنا المبلغ {payment_amount} الخاص بك مقابل {retainer_number} تم إرساله بتاريخ {payment_date}</p><p>المبلغ المستحق {retainer_number} هو {payment_dueAmount}</p><p>نحن نقدر دفعك الفوري ونتطلع إلى استمرار العمل معك في المستقبل.</p><p>شكرا جزيلا لك ويوم سعيد !!</p><p>&nbsp;</p><p>يعتبر،</p><p>{company_name}</p><p>{app_url}</p>',
                    'da' => '<p>Hej,</p><p>Velkommen til {app_name}</p><p>K&aelig;re {payment_name}</p><p>Vi har modtaget dit bel&oslash;b {payment_amount} betaling for {retainer_number} indsendt p&aring; datoen {payment_date}</p><p>Dit forfaldne bel&oslash;b for {retainer_number} er {payment_dueAmount}</p><p>Vi s&aelig;tter pris p&aring; din hurtige betaling og ser frem til at forts&aelig;tte forretninger med dig i fremtiden.</p><p>Mange tak og god dag!!</p><p>&nbsp;</p><p>Med venlig hilsen</p><p>{company_name}</p><p>{app_url}</p>',
                    'de' => '<p>Hi,</p><p>Willkommen bei {app_name}</p><p>Sehr geehrte(r) {payment_name}</p><p>Wir haben Ihre Zahlung in H&ouml;he von {payment_amount} f&uuml;r {retainer_number} erhalten, die am {payment_date} eingereicht wurde</p><p>Ihr {retainer_number} f&auml;lliger Betrag betr&auml;gt {payment_dueAmount}</p><p>Wir wissen Ihre prompte Zahlung zu sch&auml;tzen und freuen uns auf die weitere Zusammenarbeit mit Ihnen in der Zukunft.</p><p>Vielen Dank und einen sch&ouml;nen Tag!!</p><p>&nbsp;</p><p>Gr&uuml;&szlig;e,</p><p>{company_name}</p><p>{app_url}</p>',
                    'es' => '<p>Hola,</p><p>Bienvenido a {app_name}</p><p>Estimado {payment_name}</p><p>Recibimos su pago de {payment_amount} por {retainer_number} enviado en la fecha {payment_date}</p><p>Su monto adeudado de {retainer_number} es {payment_dueAmount}</p><p>Agradecemos su pago puntual y esperamos seguir haciendo negocios con usted en el futuro.</p><p>&iexcl;&iexcl;Muchas gracias y buen d&iacute;a!!</p><p>&nbsp;</p><p>Saludos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'en' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Hi,</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Welcome to {app_name}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Dear {payment_name}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">We have recieved your amount {payment_amount} payment for {invoice_number} submited on date {payment_date}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Your {invoice_number} Due amount is {payment_dueAmount}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">We appreciate your prompt payment and look forward to continued business with you in the future.</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Thank you very much and have a good day!!</span></span></p>                    <p>&nbsp;</p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Regards,</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                    'fr' => '<p>Salut</p><p>Bienvenue sur {app_name}</p><p>Cher {payment_name}</p><p>Nous avons re&ccedil;u votre paiement d\'un montant de {payment_amount} pour {retainer_number} soumis le {payment_date}</p><p>Votre montant d&ucirc; de {retainer_number} est de {payment_dueAmount}</p><p>Nous appr&eacute;cions votre paiement rapide et esp&eacute;rons continuer &agrave; faire affaire avec vous &agrave; l\'avenir.</p><p>Merci beaucoup et bonne journ&eacute;e !!</p><p>&nbsp;</p><p>Salutations,</p><p>{company_name}</p><p>{app_url}</p>',
                    'it' => '<p>Ciao,</p><p>Benvenuti in {app_name}</p><p>Caro {payment_name}</p><p>Abbiamo ricevuto il tuo importo {payment_amount} pagamento per {retainer_number} inviato alla data {payment_date}</p><p>Il tuo {retainer_number} l\'importo dovuto &egrave; {payment_dueamount}</p><p>Apprezziamo il tuo rapido pagamento e non vediamo l\'ora di continuare a fare affari con te in futuro.</p><p>Grazie mille e buona giornata !!</p><p>&nbsp;</p><p>Saluti,</p><p>{company_name}</p><p>{app_url}</p>',
                    'ja' => '<p>やあ、</p><p>{app_name}へようこそ</p><p>親愛なる{payment_name}</p><p>{retainer_number}の金額{payment_amount}支払いを受け取りました{payment_date}に提出されました</p><p>あなたの{reterer_number}正当な金額は{payment_dueamount}です</p><p>私たちはあなたの迅速な支払いに感謝し、将来あなたとの継続的なビジネスを楽しみにしています。</p><p>どうもありがとうございました、そして良い一日を！</p><p>&nbsp;</p><p>よろしく、</p><p>{company_name}</p><p>{app_url}</p>',
                    'nl' => '<p>Hoi,</p><p>Welkom bij {app_name}</p><p>Beste {payment_Name}</p><p>We hebben uw bedrag ontvangen.</p><p>Uw {retainer_number} vervallen bedrag is {payment_dueAmount}</p><p>We waarderen uw snelle betaling en kijken uit naar voortdurende zaken met u in de toekomst.</p><p>Heel erg bedankt en een fijne dag fijn !!</p><p>&nbsp;</p><p>Groeten,</p><p>{company_name}</p><p>{app_url}</p>',
                    'pl' => '<p>Cześć,</p><p>Witamy w {app_name}</p><p>Drogi {payment_name}</p><p>Otrzymaliśmy twoją kwotę {payment_amount} płatność za {retainer_number} przesłany na datę {payment_date}</p><p>Twoja {retainer_number} należna kwota to {payment_dueAmount}</p><p>Doceniamy twoją szybką płatność i czekamy na dalszą działalność z Tobą w przyszłości.</p><p>Dziękuję bardzo i życzę miłego dnia !!</p><p>&nbsp;</p><p>Pozdrowienia,</p><p>{company_name}</p><p>{app_url}</p>',
                    'pt' => '<p>Oi,</p><p>Bem -vindo ao {app_Name}</p><p>Querido {retainer_name}</p><p>Recebemos seu valor {payment_amount} pagamento de {retainer_number} submetido na data {payment_date}</p><p>Seu {retainer_number} de vencimento &eacute; {payment_dueAmount}</p><p>Agradecemos seu pagamento imediato e esperamos os neg&oacute;cios cont&iacute;nuos com voc&ecirc; no futuro.</p><p>Muito obrigado e tenha um bom dia !!</p><p>&nbsp;</p><p>Cumprimentos,</p><p>{company_name}</p><p>{app_url}</p>',
                    'ru' => '<p>Привет,</p><p>Добро пожаловать в {app_name}</p><p>Дорогой {retainer_name}</p><p>Мы получили вашу сумму {payment_amount} платеж за {retainer_number}, представленную на дату {payment_date}</p><p>Ваша {retainer_number} Долженная сумма {payment_dueAmount}</p><p>Мы ценим вашу оперативную оплату и с нетерпением ждем продолжения бизнеса с вами в будущем.</p><p>Большое спасибо и хорошего дня !!</p><p>&nbsp;</p><p>С уважением,</p><p>{company_name}</p><p>{app_url}</p>',
                    'tr' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Hi,</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Welcome to {app_name}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Dear {payment_name}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">We have recieved your amount {payment_amount} payment for {invoice_number} submited on date {payment_date}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Your {invoice_number} Due amount is {payment_dueAmount}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">We appreciate your prompt payment and look forward to continued business with you in the future.</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Thank you very much and have a good day!!</span></span></p>                    <p>&nbsp;</p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Regards,</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p>                    <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                    'zh' => '<p><span style=\"颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif；\"><span style=\"字体大小：15px；字体变体-ligatures: common-ligatures;\">嗨，</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">欢迎使用 {app_name}</span></span></p > <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-变体连字：通用连字；\">亲爱的{付款名称}</span></span></p> <p><span style=\"color：#1d1c1d；字体系列：Slack-Lato、Slack -Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">我们已收到您为 {invoice_number} 支付的金额为 { payment_amount} 的付款于 { payment_date} 提交</span></span></p> <p><span style=\"color: #1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">您的 {invoice_number} 应付金额为 { payment_dueAmount}</span></span></p> <p><span style=\"color: #1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">我们感谢您及时付款，并期待将来继续与您开展业务。</span></span></p> <p><span style=\ “颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">非常感谢您，祝您有美好的一天！！</span></span></p> <p> </p> <p><span style= \“颜色：#1d1c1d；字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">问候，</span></span></p> <p><span style=\"color: #1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p> <p><span style=\"color: #1d1c1d;字体系列：Slack-Lato、Slack-Fractions、appleLogo、sans-serif;\"><span style=\"font-size: 15px;字体变体连字：通用连字；\">{app_url}</span></span></p>',
                    'he' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant -ligatures: common-ligatures;\">היי,</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">ברוכים הבאים אל {app_name}</span></span></p > <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font- variant-ligatures: common-ligatures;\">יקר {payment_name}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack -שברים, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">קיבלנו את הסכום שלך {payment_amount} תשלום עבור {invoice_number} הוגש בתאריך {payment_date}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">סכום התשלום שלך ב-{invoice_number} הוא {payment_dueAmount}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">אנו מעריכים את התשלום המהיר שלך ומצפים להמשך העסקים איתך בעתיד.</span></span></p> <p><span style=\ "color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">תודה רבה ויום טוב!!</span></span></p> <p> </p> <p><span style= \"צבע: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">בברכה,</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                    'pt-br' => '<p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant -ligatures: common-ligatures;\">Oi,</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Bem-vindo ao {app_name}</span></span></p > <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font- variant-ligatures: common-ligatures;\">Prezado {payment_name}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack -Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Recebemos seu pagamento de {payment_amount} por {invoice_number} enviado na data {payment_date}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Seu {invoice_number} Valor devido é {payment_dueAmount}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Agradecemos seu pagamento imediato e esperamos continuar a fazer negócios com você no futuro.</span></span></p> <p><span style=\ "cor: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Muito obrigado e tenha um bom dia!!</span></span></p> <p> </p> <p><span style= \"cor: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">Atenciosamente,</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{company_name}</span></span></p> <p><span style=\"color: #1d1c1d; font-family: Slack-Lato, Slack-Fractions, appleLogo, sans-serif;\"><span style=\"font-size: 15px; font-variant-ligatures: common-ligatures;\">{app_url}</span></span></p>',
                ],

            ],


        ];
       
        $email = EmailTemplate::all();

        foreach ($email as $e) {

            foreach ($defaultTemplate[$e->slug]['lang'] as $lang => $content) {
                $emailNoti = EmailTemplateLang::where('parent_id', $e->id)->where('lang', $lang)->count();
                if ($emailNoti == 0) {
                    EmailTemplateLang::create(
                        [
                            'parent_id' => $e->id,
                            'lang' => $lang,
                            'subject' => $defaultTemplate[$e->slug]['subject'],
                            'content' => $content,
                        ]
                    );
                }
            }
        }
    }
}
