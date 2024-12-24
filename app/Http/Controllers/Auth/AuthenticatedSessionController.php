<?php

namespace App\Http\Controllers\Auth;

use App\Events\VerifyReCaptchaToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Customer;
use App\Models\Utility;
use App\Models\Vender;
use App\Models\LoginDetail;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */

    public function __construct()
    {
        if (!file_exists(storage_path() . "/installed")) {
            header('location:install');
            die;
        }

        $settings = Utility::settings();

        if ($settings['recaptcha_module'] == 'yes') {
            config(['captcha.secret' => $settings['google_recaptcha_secret']]);
            config(['captcha.sitekey' => $settings['google_recaptcha_key']]);
        }
    }

    public function create($lang = '')
    {
        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);
        return view('auth.login', compact('lang'));
    }

    public function username()
    {
        return 'email';
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $user = User::where('email',$request->email)->first();
        if($user != null && $user->is_enable_login == 0 && $user->type != 'super admin')
        {
            return redirect()->back()->with('status', __('Your Account is disable from company.'));
        }
        // ReCpatcha
        $settings = Utility::settings();

        // if ($settings['recaptcha_module'] == 'yes') {
        //     $validation['g-recaptcha-response'] = 'required|captcha';
        // } else {
        //     $validation = [];
        // }
        // $this->validate($request, $validation);
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

        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        $ip = $_SERVER['REMOTE_ADDR']; // your ip address here

        // $ip = '49.36.83.154'; // This is static ip address

        $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
        if (isset($query['status']) &&  $query['status'] != 'fail')
        {

        $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
        if ($whichbrowser->device->type == 'bot') {
            return;
        }
        $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

        /* Detect extra details about the user */
        $query['browser_name'] = $whichbrowser->browser->name ?? null;
        $query['os_name'] = $whichbrowser->os->name ?? null;
        $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
        $query['device_type'] = get_device_type($_SERVER['HTTP_USER_AGENT']);
        $query['referrer_host'] = !empty($referrer['host']);
        $query['referrer_path'] = !empty($referrer['path']);

        isset($query['timezone'])?date_default_timezone_set($query['timezone']): '' ;

        $json = json_encode($query);
        if ($user->type != 'company') {
            $login_detail = new LoginDetail();
            $login_detail->user_id = Auth::user()->id;
            $login_detail->ip = $ip;
            $login_detail->date = date('Y-m-d H:i:s');
            $login_detail->Details = $json;
            $login_detail->type = 'user';
            $login_detail->created_by = \Auth::user()->creatorId();
            $login_detail->save();
        }
        }

        if($user->delete_status == 0)
        {
            auth()->logout();
        }

        if($user->is_active == 0)
        {
            auth()->logout();
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }



    public function showCustomerLoginForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.customer_login', compact('lang'));
    }

    public function customerLogin(Request $request)
    {

        $customer = Customer::where('email',$request->email)->first();

        if($customer != null && $customer->is_enable_login == 0 && $customer->type != 'super admin')
        {
            return redirect()->back()->with('status', __('Your Account is disable from customer.'));
        }

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

        $this->validate(
            $request, [
                        'email' => 'required|email',
                        'password' => 'required|min:6',
                    ]
        );

        if(\Auth::guard('customer')->attempt(
            [
                'email' => $request->email,
                'password' => $request->password,
            ], $request->get('remember')
        ))
        {
            if(\Auth::guard('customer')->user()->is_active == 0)
            {
                \Auth::guard('customer')->logout();
            }

            $Customer = Customer::where('email', $request->email)->first();
            $customer->last_login_at = date('Y-m-d H:i:s');
            $customer->save();
            
            $ip = $_SERVER['REMOTE_ADDR']; // your ip address here

            // $ip = '49.36.83.154'; // This is static ip address

            $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
            if (isset($query['status']) &&  $query['status'] != 'fail')
        {
            $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
            if ($whichbrowser->device->type == 'bot') {
                return;
            }
            $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

            /* Detect extra details about the user */
            $query['browser_name'] = $whichbrowser->browser->name ?? null;
            $query['os_name'] = $whichbrowser->os->name ?? null;
            $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
            $query['device_type'] = get_device_type($_SERVER['HTTP_USER_AGENT']);
            $query['referrer_host'] = !empty($referrer['host']);
            $query['referrer_path'] = !empty($referrer['path']);

            isset($query['timezone'])?date_default_timezone_set($query['timezone']): '' ;

            $json = json_encode($query);
            $Customer = Customer::where('email',$request->email)->first();
            $login_detail = new LoginDetail();
            $login_detail->user_id = $Customer->id;
            $login_detail->ip = $ip;
            $login_detail->date = date('Y-m-d H:i:s');
            $login_detail->Details = $json;
            $login_detail->type = 'customer';
            $login_detail->created_by = $Customer->created_by;
            $login_detail->save();


        }
        return redirect()->route('customer.dashboard');
    }

        return $this->sendFailedLoginResponse($request);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('These credentials do not match our records.')],]);
    }

    public function showVenderLoginForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.vender_login', compact('lang'));
    }

    public function venderLogin(Request $request)
    {
        $vender = Vender::where('email',$request->email)->first();
        if($vender != null && $vender->is_enable_login == 0 && $vender->type != 'super admin')
        {
            return redirect()->back()->with('status', __('Your Account is disable from vendor.'));
        }

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


        $this->validate(
            $request, [
                        'email' => 'required|email',
                        'password' => 'required|min:6',
                    ]
        );
        if(\Auth::guard('vender')->attempt(
            [
                'email' => $request->email,
                'password' => $request->password,
            ], $request->get('remember')
        ))
        {
            if(\Auth::guard('vender')->user()->is_active == 0)
            {
                \Auth::guard('vender')->logout();
            }

            $vender = Vender::where('email', $request->email)->first();
            $vender->last_login_at = date('Y-m-d H:i:s');
            $vender->save();

            $ip = $_SERVER['REMOTE_ADDR']; // your ip address here

            // $ip = '49.36.83.154'; // This is static ip address

            $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));
            if (isset($query['status']) &&  $query['status'] != 'fail')
        {
            $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
            if ($whichbrowser->device->type == 'bot') {
                return;
            }
            $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

            /* Detect extra details about the user */
            $query['browser_name'] = $whichbrowser->browser->name ?? null;
            $query['os_name'] = $whichbrowser->os->name ?? null;
            $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
            $query['device_type'] = get_device_type($_SERVER['HTTP_USER_AGENT']);
            $query['referrer_host'] = !empty($referrer['host']);
            $query['referrer_path'] = !empty($referrer['path']);

            isset($query['timezone'])?date_default_timezone_set($query['timezone']): '' ;

            $json = json_encode($query);
            $vender = Vender::where('email',$request->email)->first();
            $login_detail = new LoginDetail();
            $login_detail->user_id = $vender->id;
            $login_detail->ip = $ip;
            $login_detail->date = date('Y-m-d H:i:s');
            $login_detail->Details = $json;
            $login_detail->type = 'vender';
            $login_detail->created_by = $vender->created_by;
            $login_detail->save();
        }
        return redirect()->route('vender.dashboard');
    }

        return $this->sendFailedLoginResponse($request);
    }

    public function showLoginForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.login', compact('lang'));
    }

    public function showLinkRequestForm($lang = '')
    {

        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }


        \App::setLocale($lang);

        return view('auth.forgot-password', compact('lang'));
    }

    public function showCustomerLoginLang($lang = '')
    {
        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.customer_login', compact('lang'));
    }

    public function showVenderLoginLang($lang = '')
    {
        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.vender_login', compact('lang'));
    }

    //    ---------------------------------Customer ----------------------------------
    public function showCustomerLinkRequestForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.customerEmail', compact('lang'));
    }

    public function postCustomerEmail(Request $request)
    {


        $request->validate(
            [
                'email' => 'required|email|exists:customers',
            ]
        );

        $token = \Str::random(60);

        DB::table('password_resets')->insert(
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        // dd(settings);

        Mail::send(
            'auth.customerVerify',
            ['token' => $token],
            function ($message) use ($request) {
                $settings = Utility::settings();

                $message->from($settings['mail_username'] ,$settings['mail_from_name']);
                $message->to($request->email);
                $message->subject('Reset Password Notification');
            }
        );

        return back()->with('status', 'We have e-mailed your password reset link!');
    }

    public function showResetForm(Request $request, $token = null)
    {

        $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
        $lang             = !empty($default_language) ? $default_language->value : 'en';

        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.passwords.reset')->with(
            [
                'token' => $token,
                'email' => $request->email,
                'lang' => $lang,
            ]
        );
    }

    public function getCustomerPassword($token)
    {

        return view('auth.passwords.customerReset', ['token' => $token]);
    }

    public function updateCustomerPassword(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:customers',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',

            ]
        );

        $updatePassword = DB::table('password_resets')->where(
            [
                'email' => $request->email,
                'token' => $request->token,
            ]
        )->first();

        if(!$updatePassword)
        {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = Customer::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return redirect('/login')->with('message', 'Your password has been changed.');

    }

    //    ----------------------------Vendor----------------------------------------------------
    public function showVendorLinkRequestForm($lang = '')
    {
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if($lang == '')
        {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);

        return view('auth.vendorEmail', compact('lang'));
    }

    public function postVendorEmail(Request $request)
    {

        $request->validate(
            [
                'email' => 'required|email|exists:venders',
            ]
        );

        $token = \Str::random(60);

        DB::table('password_resets')->insert(
            [
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        Mail::send(
            'auth.vendorVerify',
            ['token' => $token],
            function ($message) use ($request) {
                $settings = Utility::settings();
                $message->from($settings['mail_username'] ,$settings['mail_from_name']);
                $message->to($request->email);
                $message->subject('Reset Password Notification');
            }
        );

        return back()->with('status', 'We have e-mailed your password reset link!');
    }

    public function getVendorPassword($token)
    {

        return view('auth.passwords.vendorReset', ['token' => $token]);
    }

    public function updateVendorPassword(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:venders',
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required',

            ]
        );

        $updatePassword = DB::table('password_resets')->where(
            [
                'email' => $request->email,
                'token' => $request->token,
            ]
        )->first();

        if(!$updatePassword)
        {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = Vender::where('email', $request->email)->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return redirect('/login')->with('message', 'Your password has been changed.');

    }

}

function get_device_type($user_agent)
{
    $mobile_regex = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
    $tablet_regex = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';
    if (preg_match_all($mobile_regex, $user_agent)) {
        return 'mobile';
    } else {
        if (preg_match_all($tablet_regex, $user_agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
}

