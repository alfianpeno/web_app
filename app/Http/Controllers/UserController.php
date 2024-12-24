<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Mail\UserCreate;
use App\Models\User;
use App\Models\UserCompany;
use Auth;
use File;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Session;
use Spatie\Permission\Models\Role;
use App\Models\Utility;


class UserController extends Controller
{

    public function index()
    {
        $user = \Auth::user();
        if(\Auth::user()->can('manage user'))
        {
            if(\Auth::user()->type == 'super admin')
            {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->get();
            }
            else
            {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '!=', 'client')->get();
            }

            return view('user.index')->with('users', $users);
        }
        else
        {
            return redirect()->back();
        }

    }


    public function create()
    {
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

        $user  = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->get()->pluck('name', 'id');
        if(\Auth::user()->can('create user'))
        {
            return view('user.create', compact('roles', 'customFields'));
        }
        else
        {
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {

        if(\Auth::user()->can('create user'))
        {
            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            $validator = \Validator::make(
                $request->all(), [
                                   'name' => 'required|max:120',
                                   'email' => 'required',
                                                Rule::unique('users')->where(function ($query) {
                                                    return $query->where('created_by', \Auth::user()->creatorId());
                                                }),
                                   'role' => 'required',
                               ]
            );
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
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

            $role_r                = Role::findById($request->role);
            $psw                   = $request->password;
            $request['password']      = !empty($userpassword) ? \Hash::make($userpassword) : null;
            $request['type']       = $role_r->name;
            $request['lang']       = !empty($default_language) ? $default_language->value : 'en';
            $request['is_enable_login'] = $enableLogin;
            $request['created_by'] = \Auth::user()->creatorId();

            $user = User::create($request->all());
            CustomField::saveData($user, $request->customField);
            $user->assignRole($role_r);

            $user->userDefaultDataRegister($user->id);
          
            $uArr = [
                'email' => $user->email,
                'password' => $psw,
            ];
            try
            {
                $resp = Utility::sendEmailTemplate('user_created', [$user->id => $user->email], $uArr);
            }
            catch(\Exception $e)
            {

                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }

            return redirect()->route('users.index')->with('success', __('User successfully added.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));


        }
        else
        {
            return redirect()->back();
        }

    }

    public function edit($id)
    {

        $user  = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->get()->pluck('name', 'id');
        if(\Auth::user()->can('edit user'))
        {
            $user              = User::findOrFail($id);
            $user->customField = CustomField::getData($user, 'user');
            $customFields      = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

            return view('user.edit', compact('user', 'roles', 'customFields'));
        }
        else
        {
            return redirect()->back();
        }

    }


    public function update(Request $request, $id)
    {

        if(\Auth::user()->can('edit user'))
        {
            if(\Auth::user()->type == 'super admin')
            {
                $user = User::findOrFail($id);

                $validator = \Validator::make(
                    $request->all(), [
                                       'name' => 'required|max:120',
                                       'email' => 'required|email|unique:users,email,' . $id,
                                   ]
                );
                if($validator->fails())
                {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $input = $request->all();
                $user->fill($input)->save();
                CustomField::saveData($user, $request->customField);

                return redirect()->route('users.index')->with(
                    'success', 'User successfully updated.'
                );
            }
            else
            {
                $user = User::findOrFail($id);
                $this->validate(
                    $request, [
                                'name' => 'required|max:120',
                                'email' => 'required|email|unique:users,email,' . $id,
                                'role' => 'required',
                            ]
                );

                $role          = Role::findById($request->role);
                $input         = $request->all();
                $input['type'] = $role->name;
                $user->fill($input)->save();

                CustomField::saveData($user, $request->customField);

                $roles[] = $request->role;
                $user->roles()->sync($roles);

                return redirect()->route('users.index')->with(
                    'success', 'User successfully updated.'
                );
            }
        }
        else
        {
            return redirect()->back();
        }
    }


    public function destroy($id)
    {
        if(\Auth::user()->can('delete user'))
        {
            $user = User::find($id);
            if($user)
            {
                if(\Auth::user()->type == 'super admin')
                {
                    if($user->delete_status == 0)
                    {
                        $user->delete_status = 1;
                    }
                    else
                    {
                        $user->delete_status = 0;
                    }
                    $user->save();
                }
                else
                {
                    $user->delete();

                }

                return redirect()->route('users.index')->with('success', __('User successfully deleted .'));
            }
            else
            {
                return redirect()->back()->with('error', __('Something is wrong.'));
            }
        }
        else
        {
            return redirect()->back();
        }
    }

    public function profile()
    {
        $userDetail              = \Auth::user();
        $userDetail->customField = CustomField::getData($userDetail, 'user');
        $customFields            = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

        return view('user.profile', compact('userDetail', 'customFields'));
    }


    public function editprofile(Request $request)
    {

        $userDetail = \Auth::user();
        $user       = User::findOrFail($userDetail['id']);
        $this->validate(
            $request, [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users,email,' . $userDetail['id'],
                    ]
        );
        if($request->hasFile('profile'))
        {
            $filenameWithExt = $request->file('profile')->getClientOriginalName();
            $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension       = $request->file('profile')->getClientOriginalExtension();
            $fileNameToStore = $filename . '_' . time() . '.' . $extension;

            $settings = Utility::settings();

            if($settings['storage_setting']=='local'){
                $dir        = 'uploads/avatar/';
            }
            else{
                    $dir        = 'uploads/avatar';
                }
            $image_path = $dir . $userDetail['avatar'];

            if(File::exists($image_path))
            {
                File::delete($image_path);
            }

                // if(!file_exists($dir))
                // {
                //     mkdir($dir, 0777, true);
                // }
            $url = '';
            // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
            $path = Utility::upload_file($request,'profile',$fileNameToStore,$dir,[]);
            // dd($path);
            if($path['flag'] == 1){
                $url = $path['url'];
            }else{
                return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
            }

        // dd($path);
            // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);

        }

        if(!empty($request->profile))
        {
            $user['avatar'] =  $url;
        }
        $user['name']  = $request['name'];
        $user['email'] = $request['email'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success', 'Profile successfully updated.'
        );
    }





    public function updatePassword(Request $request)
    {

        if(\Auth::Check())
        {

            $request->validate(
                [
                    'current_password' => 'required',
                    'new_password' => 'required|min:6',
                    'confirm_password' => 'required|same:new_password',
                ]
            );
            $objUser          = \Auth::user();
            $request_data     = $request->All();
            $current_password = $objUser->password;
            if(Hash::check($request_data['current_password'], $current_password))
            {
                $user_id            = \Auth::User()->id;
                $obj_user           = User::find($user_id);
                $obj_user->password = Hash::make($request_data['new_password']);
                $obj_user->save();

                return redirect()->route('profile', $objUser->id)->with('success', __('Password successfully updated.'));
            }
            else
            {
                return redirect()->route('profile', $objUser->id)->with('error', __('Please enter correct current password.'));
            }
        }
        else
        {
            return redirect()->route('profile', \Auth::user()->id)->with('error', __('Something is wrong.'));
        }
    }


    // change mode 'dark or light'
    public function changeMode()
    {
        $usr = Auth::user();
        if($usr->mode == 'light')
        {
            $usr->mode      = 'dark';
        }
        else
        {
            $usr->mode      = 'light';
        }
        $usr->save();
        return redirect()->back();
    }
    public function userPassword($id)
    {
        $eId        = \Crypt::decrypt($id);
        $user = User::find($eId);

        return view('user.reset', compact('user'));

    }

    public function userPasswordReset(Request $request, $id)
    {
        $validator = \Validator::make(
            $request->all(), [
                               'password' => 'required|confirmed|same:password_confirmation',
                           ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }


        $user                 = User::where('id', $id)->first();
        $user->forceFill([
                             'password' => Hash::make($request->password),
                             'is_enable_login' => 1,
                         ])->save();

        return redirect()->route('users.index')->with(
            'success', 'User Password successfully updated.'
        );


    }

    public function LoginManage($id)
    {
        $eId        = \Crypt::decrypt($id);
        $user = User::find($eId);
        if($user->is_enable_login == 1)
        {
            $user->is_enable_login = 0;
            $user->save();
            return redirect()->back()->with('success', __('User login disable successfully.'));
        }
        else
        {
            $user->is_enable_login = 1;
            $user->save();
            return redirect()->back()->with('success', __('User login enable successfully.'));
        }
    }

}
