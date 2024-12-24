<?php

namespace App\Http\Controllers\Auth;

use App\Events\VerifyReCaptchaToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Utility;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
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
        $langList = Utility::langList();
        $lang = array_key_exists($lang, $langList) ? $lang : 'en';

        if ($lang == '') {
            $lang = Utility::getValByName('default_language');
        }

        \App::setLocale($lang);
        return view('auth.forgot-password', compact('lang'));
    }

    /**
     * Handle an incoming password reset link request.
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


        $request->validate([
                               'email' => 'required|email',
                           ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try
        {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            return $status == Password::RESET_LINK_SENT ? back()->with('status', __($status)) : back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }
        catch(\Exception $e)
        {
            return redirect()->back()->withErrors('E-Mail has been not sent due to SMTP configuration');
        }
    }
}
