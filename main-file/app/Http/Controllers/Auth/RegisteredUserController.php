<?php

namespace App\Http\Controllers\Auth;

use App\Events\VerifyReCaptchaToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Utility;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
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


    public function store(Request $request)
    {
        if (isset($request->plan)) {
            try {
                $plan = \Illuminate\Support\Facades\Crypt::decrypt($request->plan);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $plan = 0;
            }
        }

        // ReCpatcha
        $settings = Utility::settings();

        $validation = [];

        if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'yes') {
            if ($settings['google_recaptcha_version'] == 'v2-checkbox') {
                $validation['g-recaptcha-response'] = 'required';
            } elseif ($settings['google_recaptcha_version'] == 'v3') {
                $result = event(new VerifyReCaptchaToken($request));

                if (!isset($result[0]['status']) || $result[0]['status'] != true) {
                    $key = 'g-recaptcha-response';
                    $request->merge([$key => null]); // Set the key to null

                    $validation['g-recaptcha-response'] = 'required';
                }
            } else {
                $validation = [];
            }
        } else {
            $validation = [];
        }
        $this->validate($request, $validation);


        $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'  => ['required', 'confirmed', Rules\Password::defaults()],
            'password_confirmation' => ['required'],
        ]);


        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'type'              => 'company',
            'lang'              => \App\Models\Utility::getValByName('default_language'),
            'plan'              => 1,
            'created_by'        => 1,
            'referral_code'         => Utility::generateReferralCode(),
            'used_referral_code'    => isset($request->ref_code) ? $request->ref_code : 0,
        ]);

        Auth::login($user);

        if($settings['email_verification'] == 'off') {
            try {
                $uArr = [
                    'email'     => $request->email,
                    'password'  => $request->password,
                ];
                Utility::sendEmailTemplate('user_created', [$user->id => $user->email], $uArr);
            } catch (\Throwable $th) {
            }
        }
        if (Utility::getValByName('email_verification') == 'on') {

            try {
                Utility::getSMTPDetails(1);
                $user->sendEmailVerificationNotification();
                $role_r = Role::findByName('company');
                $user->assignRole($role_r);

                $user->userDefaultDataRegister($user->id);
                Utility::chartOfAccountTypeData($user->id);
                Utility::chartOfAccountData1($user->id);

            } catch (\Exception $e) {
                $user->delete();
                return redirect('/register/lang?')->with('status', __('Email SMTP settings does not configure so please contact to your site admin.'));
            }

            if (isset($plan) && !empty($plan)) {
                return redirect()->route('stripe', \Illuminate\Support\Facades\Crypt::encrypt($plan));
            }else {
                return redirect(RouteServiceProvider::HOME);
            }

            // return view('auth.verify-email');
        } else {
            $user->email_verified_at = date('h:i:s');
            $user->save();

            $role_r = Role::findByName('company');

            $user->assignRole($role_r);
            $user->userDefaultDataRegister($user->id);
            Utility::chartOfAccountTypeData($user->id);
            Utility::chartOfAccountData1($user->id);

            $userArr = [
                'email' => $user->email,
                'password' => $user->password,
            ];

            $resp = Utility::sendUserEmailTemplate('new_user', [$user->id => $user->email], $userArr);

            if (isset($plan) && !empty($plan)) {
                return redirect()->route('stripe', \Illuminate\Support\Facades\Crypt::encrypt($plan));
            }

            return redirect(RouteServiceProvider::HOME);
        }
    }

    public function showRegistrationForm(Request $request, $ref = '', $lang = '')
    {
        $settings = Utility::settings();
        if ($settings['enable_signup'] == 'on') {
            $langList = Utility::langList();
            $lang = array_key_exists($lang, $langList) ? $lang : 'en';

            if ($lang == '') {
                $lang = Utility::getValByName('default_language');
            }

            \App::setLocale($lang);

            if ($ref == '') {
                $ref = 0;
            }
            $plan = null;

            $refCode = User::where('referral_code', '=', $ref)->first();
            if ($refCode == null ||  $refCode->referral_code != $ref) {
                return view('auth.register', compact('lang', 'ref', 'plan'));
            }

            if (!empty($request->value)) {
                $plan = isset($request->value) ? $request->value : null;
            }
            return view('auth.register', compact('lang', 'ref', 'plan'));
        } else {
            return redirect('login');
        }
    }
}
