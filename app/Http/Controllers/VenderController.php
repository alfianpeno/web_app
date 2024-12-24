<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CustomField;
use App\Models\Mail\UserCreate;
use App\Models\Transaction;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\ProductServiceCategory;
use Auth;
use File;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VenderExport;
use App\Imports\VenderImport;

class VenderController extends Controller
{

    public function dashboard()
    {
        $data['billChartData'] = \Auth::user()->billChartData();

        return view('vender.dashboard', $data);
    }

    public function index()
    {
        if (\Auth::user()->can('manage vender')) {
            $venders = Vender::where('created_by', \Auth::user()->creatorId())->get();

            return view('vender.index', compact('venders'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create vender')) {
            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

            return view('vender.create', compact('customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function store(Request $request)
    {
        if (\Auth::user()->can('create vender')) {
            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^\+\d{1,3}\d{9,13}$/',
                'email' => 'required',
                        Rule::unique('venders')->where(function ($query) {
                            return $query->where('created_by', \Auth::user()->creatorId());
                        }),
            ];


            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('vender.index')->with('error', $messages->first());
            }

            $enableLogin       = 0;
            if(!empty($request->password_switch) && $request->password_switch == 'on')
            {
                $enableLogin   = 1;
                $validator = \Validator::make(
                    $request->all(), ['password' => 'required|min:6']
                );

                if($validator->fails())
                {
                    return redirect()->back()->with('error', $validator->errors()->first());
                }
            }
            $userpassword               = $request->input('password');


            $default_language         = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            $vender                   = new Vender();
            $vender->vender_id        = $this->venderNumber();
            $vender->name             = $request->name;
            $vender->contact          = $request->contact;
            $vender->email            = $request->email;
            $vender->tax_number       = $request->tax_number;
            $request['password'] = !empty($userpassword) ? \Hash::make($userpassword) : null;
                if(!empty($request['password'])){
                    $vender->password        = $request['password'] ?? null;
                }
            $vender->created_by       = \Auth::user()->creatorId();
            $vender->billing_name     = $request->billing_name;
            $vender->billing_country  = $request->billing_country;
            $vender->billing_state    = $request->billing_state;
            $vender->billing_city     = $request->billing_city;
            $vender->billing_phone    = $request->billing_phone;
            $vender->billing_zip      = $request->billing_zip;
            $vender->billing_address  = $request->billing_address;
            $vender->shipping_name    = $request->shipping_name;
            $vender->shipping_country = $request->shipping_country;
            $vender->shipping_state   = $request->shipping_state;
            $vender->shipping_city    = $request->shipping_city;
            $vender->shipping_phone   = $request->shipping_phone;
            $vender->shipping_zip     = $request->shipping_zip;
            $vender->shipping_address = $request->shipping_address;
            $vender->lang             = !empty($default_language) ? $default_language->value : '';
            $vender->is_enable_login =  $enableLogin;
            $vender->save();
            CustomField::saveData($vender, $request->customField);


            $role_r = Role::where('name', '=', 'vender')->firstOrFail();
            $vender->assignRole($role_r); //Assigning role to user

            $uArr = [
                'email' => $vender->email,
                'password' => $request->password,
            ];
            try {
                $resp = Utility::sendEmailTemplate('user_created', [$vender->id => $vender->email], $uArr);
            } catch (\Exception $e) {
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            //Twilio Notification
            $setting  = Utility::settings(\Auth::user()->creatorId());
            if(isset($setting['vender_notification']) && $setting['vender_notification'] ==1)
            {
                $uArr = [
                    'vender_name' => $request->name,
                    'email' => $request->email,
                    'password' => $request->password,
                ];
                Utility::send_twilio_msg($request->contact, 'new_vendor', $uArr);
            }

            // webhook
            $module = 'New Vendor';
            $webhook =  Utility::webhookSetting($module);
            if ($webhook) {
                $parameter = json_encode($vender);
                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method
                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                if ($status == true) {
                    return redirect()->route('vender.index')->with('success', __('Vendor successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                } else {
                    return redirect()->back()->with('error', __('Webhook call failed.'));
                }
            }

            return redirect()->route('vender.index')->with('success', __('Vendor successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function show($ids)
    {
        $id     = \Crypt::decrypt($ids);
        $vendor = Vender::find($id);

        return view('vender.show', compact('vendor'));
    }


    public function edit($id)
    {
        if (\Auth::user()->can('edit vender')) {
            $vender              = Vender::find($id);
            $vender->customField = CustomField::getData($vender, 'vendor');

            $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

            return view('vender.edit', compact('vender', 'customFields'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function update(Request $request, Vender $vender)
    {
        if (\Auth::user()->can('edit vender')) {

            $rules = [
                'name' => 'required',
                'contact' => 'required|regex:/^\+\d{1,3}\d{9,13}$/',
            ];


            $validator = \Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->route('vender.index')->with('error', $messages->first());
            }

            $vender->name             = $request->name;
            $vender->contact          = $request->contact;
            $vender->tax_number       = $request->tax_number;
            $vender->created_by       = \Auth::user()->creatorId();
            $vender->billing_name     = $request->billing_name;
            $vender->billing_country  = $request->billing_country;
            $vender->billing_state    = $request->billing_state;
            $vender->billing_city     = $request->billing_city;
            $vender->billing_phone    = $request->billing_phone;
            $vender->billing_zip      = $request->billing_zip;
            $vender->billing_address  = $request->billing_address;
            $vender->shipping_name    = $request->shipping_name;
            $vender->shipping_country = $request->shipping_country;
            $vender->shipping_state   = $request->shipping_state;
            $vender->shipping_city    = $request->shipping_city;
            $vender->shipping_phone   = $request->shipping_phone;
            $vender->shipping_zip     = $request->shipping_zip;
            $vender->shipping_address = $request->shipping_address;
            $vender->save();
            CustomField::saveData($vender, $request->customField);

            return redirect()->route('vender.index')->with('success', __('Vendor successfully updated.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(Vender $vender)
    {
        if (\Auth::user()->can('delete vender')) {
            if ($vender->created_by == \Auth::user()->creatorId()) {
                $vender->delete();

                return redirect()->route('vender.index')->with('success', __('Vendor successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    function venderNumber()
    {
        $latest = Vender::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->vender_id + 1;
    }

    public function venderLogout(Request $request)
    {
        \Auth::guard('vender')->logout();

        $request->session()->invalidate();

        return redirect()->route('vender.login');
    }

    public function payment(Request $request)
    {

        if (\Auth::user()->can('manage vender payment')) {
            // $category = [
            //     'Bill' => 'Bill',
            //     'Deposit' => 'Deposit',
            //     'Sales' => 'Sales',
            // ];

            $category = ProductServiceCategory::where('created_by',\Auth::user()->creatorId())->where('type',2)->get()->pluck('name','id');
            // $category->prepend('Bill','');

            $query = Transaction::where('user_id', \Auth::user()->id)->where('created_by', \Auth::user()->creatorId())->where('user_type', 'Vender')->where('type', 'Payment');
            if (isset($request->date) && !empty($request->date)) {
                $time = strtotime($request->date);
                $month = date("m", $time);

                $query = $query->whereMonth('date', $month);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $payments = $query->get();


            return view('vender.payment', compact('payments', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function transaction(Request $request)
    {

        if (\Auth::user()->can('manage vender transaction')) {


            // $category = [
            //     'Bill' => 'Bill',
            //     'Deposit' => 'Deposit',
            //     'Sales' => 'Sales',
            // ];
            $category = ProductServiceCategory::where('created_by',\Auth::user()->creatorId())->where('type',2)->get()->pluck('name','id');
            $query = Transaction::where('user_id', \Auth::user()->id)->where('user_type', 'Vender');

            if (isset($request->date) && !empty($request->date)) {
                $time = strtotime($request->date);
                $month = date("m", $time);

                $query = $query->whereMonth('date', $month);
            }

            if (!empty($request->category)) {
                $query->where('category', '=', $request->category);
            }
            $transactions = $query->get();


            return view('vender.transaction', compact('transactions', 'category'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'vendor');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'vendor')->get();

        return view('vender.profile', compact('userDetail', 'customFields'));
    }

    public function editprofile(Request $request)
    {

        $userDetail = \Auth::user();
        $user       = Vender::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'name' => 'required|max:120',
                // 'contact' => 'required',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );
        if ($request->hasFile('profile')) {

            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $dir        = storage_path('uploads/avatar/');
            $image_path = $dir . $userDetail['avatar'];

            if (File::exists($image_path)) {
                File::delete($image_path);
            }

            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }

            $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
        }

        if (!empty($request->profile)) {
            $user['avatar'] = $fileNameToStore;
        }
        $user['name']    = $request['name'];
        $user['email']   = $request['email'];
        $user['contact'] = $request['contact'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editBilling(Request $request)
    {

        $userDetail = \Auth::user();
        $user       = Vender::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'billing_name' => 'required',
                'billing_country' => 'required',
                'billing_state' => 'required',
                'billing_city' => 'required',
                'billing_phone' => 'required',
                'billing_zip' => 'required',
                'billing_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function editShipping(Request $request)
    {
        $userDetail = \Auth::user();
        $user       = Vender::findOrFail($userDetail['id']);
        $this->validate(
            $request,
            [
                'shipping_name' => 'required',
                'shipping_country' => 'required',
                'shipping_state' => 'required',
                'shipping_city' => 'required',
                'shipping_phone' => 'required',
                'shipping_zip' => 'required',
                'shipping_address' => 'required',
            ]
        );
        $input = $request->all();
        $user->fill($input)->save();

        return redirect()->back()->with(
            'success',
            'Profile successfully updated.'
        );
    }

    public function updatePassword(Request $request)
    {

        if (Auth::Check()) {
            $request->validate(
                [
                    'current_password' => 'required',
                    'new_password' => 'required|min:6',
                    'confirm_password' => 'required|same:new_password',
                ]
            );
            $objUser          = Auth::user();
            $request_data     = $request->All();
            $current_password = $objUser->password;
            if (Hash::check($request_data['current_password'], $current_password)) {
                $user_id            = Auth::User()->id;
                $obj_user           = Vender::find($user_id);
                $obj_user->password = Hash::make($request_data['new_password']);;
                $obj_user->save();

                return redirect()->back()->with('success', __('Password successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Please enter correct current password.'));
            }
        } else {
            return redirect()->back()->with('error', __('Something is wrong.'));
        }
    }

    public function changeLanquage($lang)
    {
        $user       = Auth::user();
        $user->lang = $lang;
        $user->save();
        if ($user->lang == 'ar' || $user->lang == 'he') {
            $value = 'on';
        } else {
            $value = 'off';
        }
        if ($user->type == 'super admin') {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $value,
                    'SITE_RTL',
                    $user->creatorId(),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        } else {
            \DB::insert(
                'insert into settings (`value`, `name`,`created_by`,`created_at`,`updated_at`) values (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`) ',
                [
                    $value,
                    'SITE_RTL',
                    $user->creatorId(),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s'),
                ]
            );
        }

        return redirect()->back()->with('success', __('Language change successfully.'));
    }

    public function export()
    {
        $name = 'vendor_' . date('Y-m-d i:h:s');
        $data = Excel::download(new VenderExport(), $name . '.xlsx');

        return $data;
    }

    public function importFile()
    {
        return view('vender.import');
    }

    public function import(Request $request)
    {

        $rules = [
            'file' => 'required|mimes:csv,txt,xls',
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $vendors = (new VenderImport())->toArray(request()->file('file'))[0];

        $totalCustomer = count($vendors) - 1;
        $errorArray    = [];
        for ($i = 1; $i <= count($vendors) - 1; $i++) {
            $vendor = $vendors[$i];

            $vendorByEmail = Vender::where('email', $vendor[1])->first();

            if (!empty($vendorByEmail)) {
                $vendorData = $vendorByEmail;
            } else {
                $vendorData            = new Vender();
                $vendorData->vender_id = $this->venderNumber();
            }


            $vendorData->name               = $vendor[0] ?? "";
            $vendorData->email              = $vendor[1] ?? "";
            $vendorData->password           = Hash::make($vendor[2]);
            $vendorData->contact            = $vendor[3] ?? "";
            $vendorData->is_active          = 1;
            $vendorData->billing_name       = $vendor[5] ?? "";
            $vendorData->billing_country    = $vendor[6] ?? "";
            $vendorData->billing_state      = $vendor[7] ?? "";
            $vendorData->billing_city       = $vendor[8] ?? "";
            $vendorData->billing_phone      = $vendor[9] ?? "";
            $vendorData->billing_zip        = $vendor[10] ?? "";
            $vendorData->billing_address    = $vendor[11] ?? "";
            $vendorData->shipping_name      = $vendor[12] ?? "";
            $vendorData->shipping_country   = $vendor[13] ?? "";
            $vendorData->shipping_state     = $vendor[14] ?? "";
            $vendorData->shipping_city      = $vendor[15] ?? "";
            $vendorData->shipping_phone     = $vendor[16] ?? "";
            $vendorData->shipping_zip       = $vendor[17] ?? "";
            $vendorData->shipping_address   = $vendor[18] ?? "";
            $vendorData->balance            = $vendor[19] ?? "";
            $vendorData->lang               = 'en';
            $vendorData->created_by         = \Auth::user()->creatorId();

            if (empty($vendorData)) {
                $errorArray[] = $vendorData;
            } else {
                $vendorData->save();
            }
        }

        $errorRecord = [];
        if (empty($errorArray)) {
            $data['status'] = 'success';
            $data['msg']    = __('Record successfully imported');
        } else {
            $data['status'] = 'error';
            $data['msg']    = count($errorArray) . ' ' . __('Record imported fail out of' . ' ' . $totalCustomer . ' ' . 'record');


            foreach ($errorArray as $errorData) {

                $errorRecord[] = implode(',', $errorData);
            }

            \Session::put('errorArray', $errorRecord);
        }

        return redirect()->back()->with($data['status'], $data['msg']);
    }

    public function statement(Request $request,$id)
    {
        $vendor = Vender::find($id);
        $vendorDetail       = Vender::findOrFail($vendor['id']);
        $settings = Utility::settings();

        $bill= Bill::where('created_by','=',\Auth::user()->creatorId())->where('vender_id','=',$vendor->id)->get()->pluck('id');

        $bill_payment =BillPayment::whereIn('bill_id',$bill);
        if(!empty($request->from_date)&& !empty($request->until_date))
        {
            $bill_payment->whereBetween('date',  [$request->from_date, $request->until_date]);
            $data['from_date']  = $request->from_date;
            $data['until_date'] = $request->until_date;
        }
        else
        {
            $data['from_date']  = $request->from_date;
            $data['until_date'] = $request->until_date;
            $bill_payment->whereBetween('date',  [$data['from_date'], $data['until_date']]);
        }
        $bill_payment=$bill_payment->get();



        $user = \Auth::user();
        $logo         = asset(Storage::url('uploads/logo/'));
        $company_logo = Utility::getValByName('company_logo_dark');
        $img          = asset($logo . '/' . (isset($company_logo) && !empty($company_logo) ? $company_logo : 'logo-dark.png'));


        return view('vender.statement',compact('vendor','vendorDetail','img','user','settings','bill_payment','data'));
    }

    public function venderPassword($id)
    {
        $eId        = \Crypt::decrypt($id);
        $vender = Vender::find($eId);

        return view('vender.reset', compact('vender'));
    }

    public function vendorPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $vender                 = Vender::where('id', $id)->first();
        $vender->forceFill([
            'password' => Hash::make($request->password),
            'is_enable_login' => 1,
        ])->save();

        return redirect()->route('vender.index')->with(
            'success',
            'Vender Password successfully updated.'
        );
    }

}
