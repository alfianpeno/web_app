<?php

namespace App\Http\Controllers\Auth;

use App\Events\VerifyReCaptchaToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Utility;
use Spatie\Permission\Models\Role;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */

    public function create()
    {

    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        // ReCpatcha
        $settings = Utility::settings();

        $validation = [];

        if(isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes')
        {
            if($settings['google_recaptcha_version'] == 'v2-checkbox'){
                $validation['g-recaptcha-response'] = 'required';
            }
            elseif($settings['google_recaptcha_version'] == 'v3')
            {
                $result = event(new VerifyReCaptchaToken($request));

                if (!isset($result[0]['status']) || $result[0]['status'] != true) {
                    $key = 'g-recaptcha-response';
                    $request->merge([$key => null]); // Set the key to null

                    $validation['g-recaptcha-response'] = 'required';
                }
            }else{
                $validation = [];
            }
        }else{
            $validation = [];
        }
        $this->validate($request, $validation);


        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required',   'string',
                         'min:8','confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'lang' => Utility::getValByName('default_language'),
            'created_by' => 1,
        ]);


        $role_r = Role::findByName('company');

        $user->assignRole($role_r);
        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }


    public function showRegistrationForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if($lang == '')
        {
            $lang = \App\Models\Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.register', compact('lang'));
    }


}
