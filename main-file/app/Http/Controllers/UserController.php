<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Mail\UserCreate;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserCompany;
use App\Models\Utility;
use Auth;
use File;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Session;
use Spatie\Permission\Models\Role;
use Lab404\Impersonate\Impersonate;


class UserController extends Controller
{

    public function index()
    {
        $user = \Auth::user();
        if (\Auth::user()->can('manage user')) {
            if (\Auth::user()->type == 'super admin') {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '=', 'company')->get();
            } else {
                $users = User::where('created_by', '=', $user->creatorId())->where('type', '!=', 'client')->get();
            }

            return view('user.index')->with('users', $users);
        } else {
            return redirect()->back();
        }
    }


    public function create()
    {
        $customFields = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

        $user  = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->get()->pluck('name', 'id');
        // echo"<pre>"; print_r($roles); die("=====");
        if (\Auth::user()->can('create user')) {
            return view('user.create', compact('roles', 'customFields'));
        } else {
            return redirect()->back();
        }
    }

    public function store(Request $request)
    {
        if (\Auth::user()->can('create user')) {
            $default_language = DB::table('settings')->select('value')->where('name', 'default_language')->first();
            $company_default_language = DB::table('settings')->select('value')->where('name', 'company_default_language')->first();
            // $date = DB::table('settings')->select('value')->where('name', 'email_verification')->first();

            $userpassword               = $request->input('password');
            if (\Auth::user()->type == 'super admin') {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users',
                    ]
                );
                if ($validator->fails()) {
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

                $user               = new User();
                $user['name']       = $request->name;
                $user['email']      = $request->email;
                $user['email_verified_at'] = date('Y-m-d H:i:s');
                $psw                = $request->password;
                $user['password'] = !empty($userpassword) ? \Hash::make($userpassword) : null;
                $user['type']       = 'company';
                $user['lang']       = !empty($default_language) ? $default_language->value : '';
                $user['created_by'] = \Auth::user()->creatorId();
                $user['plan']       = Plan::first()->id;
                $user['is_enable_login'] = $enableLogin;
                $user['referral_code'] = Utility::generateReferralCode();
                $user->save();

                CustomField::saveData($user, $request->customField);

                $role_r = Role::findByName('company');
                $user->assignRole($role_r);

                $user->userDefaultDataRegister($user->id);
                Utility::chartOfAccountTypeData($user->id);
                Utility::chartOfAccountData1($user->id);


                // $uArr = [
                //     'email' => $user->email,
                //     'password' => $psw,
                // ];

               
            } else {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:120',
                        'email' => 'required|unique:users',
                        'role' => 'required',
                    ]
                );
                if ($validator->fails()) {
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

                $objUser    = \Auth::user();
                $total_user = $objUser->countUsers();
                $plan       = Plan::find($objUser->plan);
                if ($total_user < $plan->max_users || $plan->max_users == -1) {
                    $user               = new User();
                    $user['name']       = $request->name;
                    $user['email']      = $request->email;
                    $role_r             = Role::findById($request->role);
                    $psw                = $request->password;
                    $user['password'] = !empty($userpassword) ? Hash::make($userpassword) : null;
                    $user['type']       = $role_r->name;
                    $user['lang']       = !empty($company_default_language) ? $company_default_language->value : 'en';
                    $user['created_by'] = \Auth::user()->creatorId();
                    $user['email_verified_at'] = date('Y-m-d H:i:s');
                    $user['plan']       = Plan::first()->id;
                    $user['is_enable_login'] = $enableLogin;

                    $user->save();
                    CustomField::saveData($user, $request->customField);

                    $user->assignRole($role_r);
                } else {
                    return redirect()->back()->with('error', __('Your user limit is over, Please upgrade plan.'));
                }
            }
            $uArr = [
                'email' => $user->email,
                'password' => $psw,
            ];

            try {
                $resp = Utility::sendEmailTemplate('user_created', [$user->id => $user->email], $uArr);
                // dd('hi',$resp );
            } catch (\Exception $e) {
                // dd($e);
                $smtp_error = __('E-Mail has been not sent due to SMTP configuration');
            }


            if (\Auth::user()->type == 'super admin') {
                return redirect()->route('users.index')->with('success', __('Company successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
            } else {
                return redirect()->route('users.index')->with('success', __('User successfully created.') . ((!empty($resp) && $resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));

            }
            // return redirect()->route('users.index')->with('success', __('User successfully added.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
        } else {
            return redirect()->back();
        }
    }

    public function edit($id)
    {

        $user  = \Auth::user();
        $roles = Role::where('created_by', '=', $user->creatorId())->get()->pluck('name', 'id');
        if (\Auth::user()->can('edit user')) {
            $user              = User::findOrFail($id);
            $user->customField = CustomField::getData($user, 'user');
            $customFields      = CustomField::where('created_by', '=', \Auth::user()->creatorId())->where('module', '=', 'user')->get();

            return view('user.edit', compact('user', 'roles', 'customFields'));
        } else {
            return redirect()->back();
        }
    }


    public function update(Request $request, $id)
    {

        if (\Auth::user()->can('edit user')) {
            if (\Auth::user()->type == 'super admin') {
                $user = User::findOrFail($id);

                $validator = \Validator::make(
                    $request->all(),
                    [
                        'name' => 'required|max:120',
                        'email' => 'required|email|unique:users,email,' . $id,
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $input = $request->all();
                $user->fill($input)->save();
                CustomField::saveData($user, $request->customField);

                return redirect()->route('users.index')->with(
                    'success',
                    'User successfully updated.'
                );
            } else {
                $user = User::findOrFail($id);
                $this->validate(
                    $request,
                    [
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
                    'success',
                    'User successfully updated.'
                );
            }
        } else {
            return redirect()->back();
        }
    }


    public function destroy($id)
    {
        if (\Auth::user()->can('delete user')) {
            $user = User::find($id);

            if ($user) {
                if (\Auth::user()->type == 'super admin') {

                    User::where('created_by', $user->id)->delete();

                    $user->delete();

                    return redirect()->back()->with('success' , __('Company Successfully deleted'));

                    // if ($user->delete_status == 0) {
                    //     $user->delete_status = 1;
                    // } else {
                    //     $user->delete_status = 0;
                    // }
                    // $user->save();
                } else {
                    $user->delete();
                }

                return redirect()->route('users.index')->with('success', __('User successfully deleted .'));
            } else {
                return redirect()->back()->with('error', __('Something is wrong.'));
            }
        } else {
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
            $request,
            [
                'name' => 'required|max:120',
                'email' => 'required|email|unique:users,email,' . $userDetail['id'],
            ]
        );

        if ($request->hasFile('profile')) {
            if (\Auth::user()->type = 'super admin') {
                $file_path = $user['avatar'];
                $filenameWithExt = $request->file('profile')->getClientOriginalName();
                $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                $extension       = $request->file('profile')->getClientOriginalExtension();
                $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                $settings = Utility::getStorageSetting();

                if ($settings['storage_setting'] == 'local') {
                    $dir        = 'uploads/avatar/';
                } else {
                    $dir        = 'uploads/avatar';
                }
                $image_path = $dir . $userDetail['avatar'];

                $url = '';
                // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
                // dd($path);
                $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
                // dd($path);
                if ($path['flag'] == 1) {
                    $url = $path['url'];
                } else {
                    return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
                }
            } else {
                $file_path = $user['avatar'];
                $image_size = $request->file('profile')->getSize();
                $result = Utility::updateStorageLimit(\Auth::user()->creatorId(), $image_size);

                if ($result == 1) {

                    Utility::changeStorageLimit(\Auth::user()->creatorId(), $file_path);
                    $filenameWithExt = $request->file('profile')->getClientOriginalName();
                    $filename        = pathinfo($filenameWithExt, PATHINFO_FILENAME);
                    $extension       = $request->file('profile')->getClientOriginalExtension();
                    $fileNameToStore = $filename . '_' . time() . '.' . $extension;
                    $settings = Utility::getStorageSetting();

                    if ($settings['storage_setting'] == 'local') {
                        $dir        = 'uploads/avatar/';
                    } else {
                        $dir        = 'uploads/avatar';
                    }
                    $image_path = $dir . $userDetail['avatar'];

                    $url = '';
                    // $path = $request->file('profile')->storeAs('uploads/avatar/', $fileNameToStore);
                    // dd($path);
                    $path = Utility::upload_file($request, 'profile', $fileNameToStore, $dir, []);
                    // dd($path);
                    if ($path['flag'] == 1) {
                        $url = $path['url'];
                    } else {
                        return redirect()->route('profile', \Auth::user()->id)->with('error', __($path['msg']));
                    }
                } else {
                    return redirect()->back()->with('error', $result);
                }
            }
        }

        if (!empty($request->profile)) {
            $user['avatar'] =  $url;
        }
        $user['name']  = $request['name'];
        $user['email'] = $request['email'];
        $user->save();
        CustomField::saveData($user, $request->customField);

        return redirect()->back()->with(
            'success',
            __('Profile successfully updated.') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : '')
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
                $obj_user           = User::find($user_id);
                $obj_user->password = Hash::make($request_data['new_password']);;
                $obj_user->save();

                return redirect()->route('profile', $objUser->id)->with('success', __('Password successfully updated.'));
            } else {
                return redirect()->route('profile', $objUser->id)->with('error', __('Please enter correct current password.'));
            }
        } else {
            return redirect()->route('profile', \Auth::user()->id)->with('error', __('Something is wrong.'));
        }
    }

    public function upgradePlan($user_id)
    {
        $user = User::find($user_id);

        $plans = Plan::get();

        return view('user.plan', compact('user', 'plans'));
    }

    public function activePlan($user_id, $plan_id)
    {

        $user       = User::find($user_id);
        $assignPlan = $user->assignPlan($plan_id);
        $plan       = Plan::find($plan_id);
        if ($assignPlan['is_success'] == true && !empty($plan)) {
            $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
            Order::create(
                [
                    'order_id' => $orderID,
                    'name' => null,
                    'card_number' => null,
                    'card_exp_month' => null,
                    'card_exp_year' => null,
                    'plan_name' => $plan->name,
                    'plan_id' => $plan->id,
                    'price' => $plan->price,
                    'price_currency' => isset(\Auth::user()->planPrice()['currency']) ? \Auth::user()->planPrice()['currency'] : '',
                    'txn_id' => '',
                    'payment_status' => 'succeeded',
                    'receipt' => null,
                    'user_id' => $user->id,
                ]
            );

            return redirect()->back()->with('success', 'Plan successfully upgraded.');
        } else {
            return redirect()->back()->with('error', 'Plan fail to upgrade.');
        }
    }

    // change mode 'dark or light'
    public function changeMode()
    {
        $usr = Auth::user();
        if ($usr->mode == 'light') {
            $usr->mode      = 'dark';
        } else {
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
            $request->all(),
            [
                'password' => 'required|confirmed|same:password_confirmation',
            ]
        );

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }


        $user                 = User::where('id', $id)->first();
        $user->forceFill([
            'password' => Hash::make($request->password),
            'is_enable_login' => 1,
        ])->save();

        return redirect()->route('users.index')->with(
            'success',
            'User Password successfully updated.'
        );
    }

    public function LoginWithCompany(Request $request,   $id)
    {
        // dd($request,  $request->user(), $id);
        $user = User::find($id);
        if ($user && auth()->check()) {
            Impersonate::take($request->user(), $user);
            return redirect('/');
        }
    }

    public function ExitCompany(Request $request)
    {
        \Auth::user()->leaveImpersonation($request->user());
        return redirect('/');
    }

    public function CompnayInfo($id)
    {
        if (!empty($id)) {
            $data = $this->Counter($id);
            if ($data['is_success']) {
                $users_data = $data['response']['users_data'];
                return view('user.companyinfo', compact('id', 'users_data'));
            }
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }

    public function UserUnable(Request $request)
    {
        if (!empty($request->id) && !empty($request->company_id)) {
            if ($request->name == 'user') {
                User::where('id', $request->id)->update(['is_disable' => $request->is_disable]);
                $data = $this->Counter($request->company_id);
            }

            if ($data['is_success']) {
                $users_data = $data['response']['users_data'];
            }
            if ($request->is_disable == 1) {

                return response()->json(['success' => __('Successfully Enable.'), 'users_data' => $users_data]);
            } else {
                return response()->json(['success' => __('Successfull Disable.'), 'users_data' => $users_data]);
            }
        }
        return response()->json('error');
    }


    public function Counter($id)
    {
        $response = [];
        if (!empty($id)) {

            $users = User::where('created_by', $id)->selectRaw('COUNT(*) as total_users, SUM(CASE WHEN is_disable = 0 THEN 1 ELSE 0 END) as disable_users, SUM(CASE WHEN is_disable = 1 THEN 1 ELSE 0 END) as active_users')->first();

            $users_data[$users->name] = [
                'total_users' => !empty($users->total_users) ? $users->total_users : 0,
                'disable_users' => !empty($users->disable_users) ? $users->disable_users : 0,
                'active_users' => !empty($users->active_users) ? $users->active_users : 0,
            ];

            $response['users_data'] = $users_data;

            return [
                'is_success' => true,
                'response' => $response,
            ];
        }
        return [
            'is_success' => false,
            'error' => 'Plan is deleted.',
        ];
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
