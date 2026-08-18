<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Utility;
use App\Models\Plan;
use App\Models\Invoice;
use App\Models\UserCoupon;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\RetainerPayment;
use App\Models\Retainer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\PayTab\paypage;

class PaytabController extends Controller
{
    public $paytab_profile_id, $paytab_server_key, $paytab_region, $is_enabled, $invoiceData;

    public function paymentConfig()
    {
        if (\Auth::check()) {
            $payment_setting = Utility::getAdminPaymentSetting();
            $admin = Utility::getAdminPaymentSetting();

            config([
                'paytabs.profile_id' => isset($payment_setting['paytab_profile_id']) ? $payment_setting['paytab_profile_id'] : '',
                'paytabs.server_key' => isset($payment_setting['paytab_server_key']) ? $payment_setting['paytab_server_key'] : '',
                'paytabs.region' => isset($payment_setting['paytab_region']) ? $payment_setting['paytab_region'] : '',
                'paytabs.currency' => $admin['currency'] ? $admin['currency'] : 'INR',
            ]);
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting(!empty($this->invoiceData) ? $this->invoiceData->created_by : 0);
            config([
                'paytabs.profile_id' => isset($payment_setting['paytab_profile_id']) ? $payment_setting['paytab_profile_id'] : '',
                'paytabs.server_key' => isset($payment_setting['paytab_server_key']) ? $payment_setting['paytab_server_key'] : '',
                'paytabs.region' => isset($payment_setting['paytab_region']) ? $payment_setting['paytab_region'] : '',
                'paytabs.currency' => 'INR',
            ]);
        }
    }
    public function planPayWithpaytab(Request $request)
    {
        try {
            $planID = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
            $plan = Plan::find($planID);
            $this->paymentconfig();
            $user = Auth::user();
            $admin = Utility::getAdminPaymentSetting();
            if($admin['currency'] == 'INR' ){
                if ($plan) {
                    $get_amount = $plan->price;
    
                    if (!empty($request->coupon)) {
                        $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                        if (!empty($coupons)) {
                            $usedCoupun = $coupons->used_coupon();
                            $discount_value = ($plan->price / 100) * $coupons->discount;
                            $get_amount = $plan->price - $discount_value;
    
                            if ($coupons->limit == $usedCoupun) {
                                return redirect()->back()->with('error', __('This coupon code has expired.'));
                            }
                            if ($get_amount <= 0) {
                                $authuser = Auth::user();
                                $authuser->plan = $plan->id;
                                $authuser->save();
                                $assignPlan = $authuser->assignPlan($plan->id);
                                if ($assignPlan['is_success'] == true && !empty($plan)) {
                                    if (!empty($authuser->payment_subscription_id) && $authuser->payment_subscription_id != '') {
                                        try {
                                            $authuser->cancel_subscription($authuser->id);
                                        } catch (\Exception $exception) {
                                            \Log::debug($exception->getMessage());
                                        }
                                    }
                                    $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                                    $userCoupon = new UserCoupon();
                                    $userCoupon->user = $authuser->id;
                                    $userCoupon->coupon = $coupons->id;
                                    $userCoupon->order = $orderID;
                                    $userCoupon->save();
                                    Order::create(
                                        [
                                            'order_id' => $orderID,
                                            'name' => null,
                                            'email' => null,
                                            'card_number' => null,
                                            'card_exp_month' => null,
                                            'card_exp_year' => null,
                                            'plan_name' => $plan->name,
                                            'plan_id' => $plan->id,
                                            'price' => $get_amount == null ? 0 : $get_amount,
                                            'price_currency' => $admin['currency'] ? $admin['currency'] : 'USD',
                                            'txn_id' => '',
                                            'payment_type' => 'Paytab',
                                            'payment_status' => 'success',
                                            'receipt' => null,
                                            'user_id' => $authuser->id,
                                        ]
                                    );
                                    $assignPlan = $authuser->assignPlan($plan->id);
                                    return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                                }
                            }
                        } else {
                            return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                        }
                    }
                    $coupon = (empty($request->coupon)) ? "0" : $request->coupon;
                    $paypage = new paypage();
                    $pay = $paypage->sendPaymentCode('all')
                        ->sendTransaction('sale')
                        ->sendCart(1, $get_amount, 'plan payment')
                        ->sendCustomerDetails(isset($user->name) ? $user->name : "", isset($user->email) ? $user->email : '', '', '', '', '', '', '', '')
                        ->sendURLs(
                            route('plan.paytab.success', ['success' => 1, 'data' => $request->all(), 'plan_id' => $plan->id, 'amount' => $get_amount, 'coupon' => $coupon]),
                            route('plan.paytab.success', ['success' => 0, 'data' => $request->all(), 'plan_id' => $plan->id, 'amount' => $get_amount, 'coupon' => $coupon])
                        )
                        ->sendLanguage('en')
                        ->sendFramed($on = false)
                        ->create_pay_page();
                    return $pay;
                } else {
                    return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
                }
            }else{
                return redirect()->back()->with('error', __('Currency not supported.'));
            }
           
        } catch (Exception $e) {
            return redirect()->route('plans.index')->with('error', __($e->getMessage()));
        }
    }

    public function PaytabGetPayment(Request $request)
    {
        $planId = $request->plan_id;
        $couponCode = $request->coupon;
        $getAmount = $request->amount;
        $admin = Utility::getAdminPaymentSetting();

        if ($couponCode != 0) {
            $coupons = Coupon::where('code', strtoupper($couponCode))->where('is_active', '1')->first();
            $request['coupon_id'] = $coupons->id;
        } else {
            $coupons = null;
        }

        $plan = Plan::find($planId);
        $user = auth()->user();
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        try {
            if ($request->success == "1") {
                Utility::referralTransaction($plan);
                $order = new Order();
                $order->order_id = $orderID;
                $order->name = $user->name;
                $order->card_number = '';
                $order->card_exp_month = '';
                $order->card_exp_year = '';
                $order->plan_name = $plan->name;
                $order->plan_id = $plan->id;
                $order->price = $getAmount;
                $order->price_currency = $admin['currency'] ? $admin['currency'] : 'USD';
                $order->payment_type = __('Paytab');
                $order->payment_status = 'success';
                $order->txn_id = '';
                $order->receipt = '';
                $order->user_id = $user->id;
                $order->save();
                $assignPlan = $user->assignPlan($plan->id);
                $coupons = Coupon::find($request->coupon_id);
                if (!empty($request->coupon_id)) {
                    if (!empty($coupons)) {
                        $userCoupon = new UserCoupon();
                        $userCoupon->user = $user->id;
                        $userCoupon->coupon = $coupons->id;
                        $userCoupon->order = $orderID;
                        $userCoupon->save();
                        $usedCoupun = $coupons->used_coupon();
                        if ($coupons->limit <= $usedCoupun) {
                            $coupons->is_active = 0;
                            $coupons->save();
                        }
                    }
                }

                if ($assignPlan['is_success']) {
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
                } else {
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Transaction is fail please try again'));
            }
        } catch (Exception $e) {
            return redirect()->route('plans.index')->with('error', __($e->getMessage()));
        }
    }

    public function invoicePayWithpaytab(Request $request, $invoice_id)
    {
        $invoice = Invoice::find($invoice_id);

        $companyPaymentSetting = Utility::getCompanyPaymentSettingWithOutAuth($invoice->created_by);

        if (\Auth::check()) {

            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
        }

        $get_amount = $request->amount;

        $request->validate(['amount' => 'required|numeric|min:0']);

        if ($invoice && $get_amount != 0) {
            if ($get_amount > $invoice->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {
                config([
                    'paytabs.profile_id' => isset($companyPaymentSetting['paytab_profile_id']) ? $companyPaymentSetting['paytab_profile_id'] : '',
                    'paytabs.server_key' => isset($companyPaymentSetting['paytab_server_key']) ? $companyPaymentSetting['paytab_server_key'] : '',
                    'paytabs.region' => isset($companyPaymentSetting['paytab_region']) ? $companyPaymentSetting['paytab_region'] : '',
                    'paytabs.currency' => 'INR',
                ]);

                $paypage = new paypage();
                $pay = $paypage->sendPaymentCode('all')
                    ->sendTransaction('sale')
                    ->sendCart(1, $get_amount, 'invoice payment')
                    ->sendCustomerDetails(isset($user->name) ? $user->name : "", isset($user->email) ? $user->email : '', '', '', '', '', '', '', '')
                    ->sendURLs(
                        route('invoice.paytab', ['success' => 1, 'data' => $request->all(), $invoice->id, 'amount' => $get_amount]),
                        route('invoice.paytab', ['success' => 0, 'data' => $request->all(), $invoice->id, 'amount' => $get_amount])
                    )
                    ->sendLanguage('en')
                    ->sendFramed($on = false)
                    ->create_pay_page();
                return $pay;
            }
        }
    }

    public function PaytabGetPaymentStatus(Request $request, $invoice_id, $amount)
    {

        if (!empty($invoice_id)) {
            $invoice    = Invoice::find($invoice_id);
            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
            $setting = Utility::settingsById($invoice->created_by);

            if (Auth::check()) {
                $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
                $objUser     = \Auth::user();
                $payment_setting = Utility::getAdminPaymentSetting();
            } else {
                $user = User::where('id', $invoice->created_by)->first();
                $settings = Utility::settingById($invoice->created_by);
                $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
                $objUser = $user;
            }

            if ($invoice) {
                try {
                    $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
                    if ($request->success == "1") {
                        $payments = InvoicePayment::create(
                            [
                                'invoice_id' => $invoice->id,
                                'date' => date('Y-m-d'),
                                'amount' => $amount,
                                'account_id' => 0,
                                'payment_method' => 0,
                                'order_id' => $order_id,
                                'currency' => $setting['site_currency'],
                                'txn_id' => $order_id,
                                'payment_type' => __('paytab'),
                                'receipt' => '',
                                'reference' => '',
                                'description' => 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
                            ]
                        );
                        if ($invoice->getDue() <= 0) {
                            $invoice->status = 4;
                            $invoice->save();
                        } elseif (($invoice->getDue() - $payments->amount) == 0) {
                            $invoice->status = 4;
                            $invoice->save();
                        } elseif ($invoice->getDue() > 0) {
                            $invoice->status = 3;
                            $invoice->save();
                        } else {
                            $invoice->status = 2;
                            $invoice->save();
                        }

                        $invoicePayment              = new \App\Models\Transaction();
                        $invoicePayment->user_id     = $invoice->customer_id;
                        $invoicePayment->user_type   = 'Customer';
                        $invoicePayment->type        = 'Paytab';
                        $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->id : $invoice->customer_id;
                        $invoicePayment->payment_id  = $invoicePayment->id;
                        $invoicePayment->category    = 'Invoice';
                        $invoicePayment->amount      = $amount;
                        $invoicePayment->date        = date('Y-m-d');
                        $invoicePayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
                        $invoicePayment->payment_id  = $payments->id;
                        $invoicePayment->description = 'Invoice ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id);
                        $invoicePayment->account     = 0;

                        \App\Models\Transaction::addTransaction($invoicePayment);

                        Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                        Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                        //Twilio Notification
                        $setting  = Utility::settingsById($objUser->creatorId());
                        $customer = Customer::find($invoice->customer_id);
                        if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                            $uArr = [
                                'invoice_id' => $payments->id,
                                'payment_name' => $customer->name,
                                'payment_amount' => $amount,
                                'payment_date' => $objUser->dateFormat($request->date),
                                'type' => 'Pyatab',
                                'user_name' => $objUser->name,
                            ];

                            Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $invoice->created_by);
                        }

                        // webhook
                        $module = 'New Payment';

                        $webhook =  Utility::webhookSetting($module, $invoice->created_by);

                        if ($webhook) {

                            $parameter = json_encode($invoice);

                            // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                            $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                        }

                        if (Auth::check()) {
                            return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                        } else {
                            return redirect()->back()->with('success', __(' Payment successfully added.'));
                        }
                    } else {
                        if (Auth::user()) {
                            return redirect()->route('invoice.show', $invoice_id)->with('error', __('Transaction fail!'));
                        } else {
                            return redirect()->back()->with('error', __('Transaction fail!'));
                        }
                    }
                } catch (\Exception $e) {
                    return redirect()->route('invoice.show', $invoice_id)->with('error', __($e->getMessage()));
                }
            } else {
                if (Auth::user()) {
                    return redirect()->route('invoice.show', $invoice_id)->with('error', __('Invoice not found'));
                } else {
                    $id = \Crypt::encrypt($invoice_id);
                    return redirect()->back()->with('error', __('Transaction fail!'));
                }
            }
        } else {
            return redirect()->route('invoice.index')->with('error', __('Invoice not found.'));
        }
    }

    public function retainerpaywithpaytab(Request $request, $retainer_id)
    {
        $retainer = Retainer::find($retainer_id);
        $companyPaymentSetting = Utility::getCompanyPaymentSetting($retainer->created_by);

        if (\Auth::check()) {

            $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $user     = \Auth::user();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
        }

        $get_amount = $request->amount;

        $request->validate(['amount' => 'required|numeric|min:0']);

        if ($retainer && $get_amount != 0) {
            if ($get_amount > $retainer->getDue()) {
                return redirect()->back()->with('error', __('Invalid amount.'));
            } else {
                config([
                    'paytabs.profile_id' => isset($companyPaymentSetting['paytab_profile_id']) ? $companyPaymentSetting['paytab_profile_id'] : '',
                    'paytabs.server_key' => isset($companyPaymentSetting['paytab_server_key']) ? $companyPaymentSetting['paytab_server_key'] : '',
                    'paytabs.region' => isset($companyPaymentSetting['paytab_region']) ? $companyPaymentSetting['paytab_region'] : '',
                    'paytabs.currency' => 'INR',
                ]);
                $paypage = new paypage();
                $pay = $paypage->sendPaymentCode('all')
                    ->sendTransaction('sale')
                    ->sendCart(1, $get_amount, 'invoice payment')
                    ->sendCustomerDetails(isset($user->name) ? $user->name : "", isset($user->email) ? $user->email : '', '', '', '', '', '', '', '')
                    ->sendURLs(
                        route('retainer.paytab', ['success' => 1, 'data' => $request->all(), $retainer->id, 'amount' => $get_amount]),
                        route('retainer.paytab', ['success' => 0, 'data' => $request->all(), $retainer->id, 'amount' => $get_amount])
                    )
                    ->sendLanguage('en')
                    ->sendFramed($on = false)
                    ->create_pay_page();

                return $pay;
            }
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {
        if (!empty($retainer_id)) {
            $retainer    = Retainer::find($retainer_id);
            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
            $setting = Utility::settingsById($retainer->created_by);


            if (Auth::check()) {
                $settings = \DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
                $objUser     = \Auth::user();
                $payment_setting = Utility::getAdminPaymentSetting();
            } else {
                $user = User::where('id', $retainer->created_by)->first();
                $settings = Utility::settingById($retainer->created_by);
                $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
                $objUser = $user;
            }

            if ($retainer) {
                try {
                    $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

                    if ($request->success == "1") {
                        $payments = RetainerPayment::create(
                            [

                                'retainer_id' => $retainer->id,
                                'date' => date('Y-m-d'),
                                'amount' => $amount,
                                'account_id' => 0,
                                'payment_method' => 0,
                                'order_id' => $order_id,
                                'currency' => $setting['site_currency'],
                                'txn_id' => $order_id,
                                'payment_type' => __('Paytab'),
                                'receipt' => '',
                                'reference' => '',
                                'description' => 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
                            ]
                        );

                        if ($retainer->getDue() <= 0) {
                            $retainer->status = 4;
                            $retainer->save();
                        } elseif (($retainer->getDue() - $payments->amount) == 0) {
                            $retainer->status = 4;
                            $retainer->save();
                        } else {
                            $retainer->status = 3;
                            $retainer->save();
                        }

                        $retainerPayment              = new \App\Models\Transaction();
                        $retainerPayment->user_id     = $retainer->customer_id;
                        $retainerPayment->user_type   = 'Customer';
                        $retainerPayment->type        = 'Paytab';
                        $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
                        $retainerPayment->payment_id  = $retainerPayment->id;
                        $retainerPayment->category    = 'Retainer';
                        $retainerPayment->amount      = $amount;
                        $retainerPayment->date        = date('Y-m-d');
                        $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
                        $retainerPayment->payment_id  = $payments->id;
                        $retainerPayment->description = 'Retainer ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id);
                        $retainerPayment->account     = 0;

                        \App\Models\Transaction::addTransaction($retainerPayment);

                        Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

                        Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                        //Twilio Notification
                        $setting  = Utility::settingsById($objUser->creatorId());
                        $customer = Customer::find($retainer->customer_id);
                        if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                            $uArr = [
                                'retainer_id' => $payments->id,
                                'payment_name' => $customer->name,
                                'payment_amount' => $amount,
                                'payment_date' => $objUser->dateFormat($request->date),
                                'type' => 'Pyatab',
                                'user_name' => $objUser->name,
                            ];

                            Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $retainer->created_by);
                        }

                        // webhook
                        $module = 'New Payment';

                        $webhook =  Utility::webhookSetting($module, $retainer->created_by);

                        if ($webhook) {

                            $parameter = json_encode($retainer);

                            // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                            $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
                        }

                        if (Auth::check()) {
                            return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
                        } else {
                            return redirect()->back()->with('success', __(' Payment successfully added.'));
                        }
                    } else {
                        if (Auth::user()) {
                            return redirect()->route('retainer.show', $retainer_id)->with('error', __('Transaction fail!'));
                        } else {
                            return redirect()->back()->with('error', __('Transaction fail!'));
                        }
                    }
                } catch (\Exception $e) {
                    return redirect()->route('retainer.show', $retainer_id)->with('error', __($e->getMessage()));
                }
            } else {
                if (Auth::user()) {
                    return redirect()->route('retainer.show', $retainer_id)->with('error', __('Invoice not found'));
                } else {
                    $id = \Crypt::encrypt($retainer_id);
                    return redirect()->back()->with('error', __('Transaction fail!'));
                }
            }
        } else {
            return redirect()->route('retainer.index')->with('error', __('Invoice not found.'));
        }
    }
}
