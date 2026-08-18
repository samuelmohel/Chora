<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserCoupon;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Obydul\LaraSkrill\SkrillClient;
use Obydul\LaraSkrill\SkrillRequest;

class SkrillPaymentController extends Controller
{
    public $email;
    public $is_enabled;

    public function paymentConfig()
    {
        if (Auth::check()) {
            $user = Auth::user();
        }
        if (\Auth::user()->type == 'company') {
            $payment_setting = Utility::getAdminPaymentSetting();
        } else {
            $payment_setting = Utility::getCompanyPaymentSetting($user);
        }


        $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
        $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';

        return $this;
    }


    public function planPayWithSkrill(Request $request)
    {
        
        $payment    = $this->paymentConfig();
        $planID     = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
        $plan       = Plan::find($planID);
        $authuser   = Auth::user();
        $coupons_id = '';
        $admin = Utility::getAdminPaymentSetting();
        if ($plan) {
            $price = $plan->price;
            if (!empty($request->coupon)) {
                if (isset($request->coupon) && !empty($request->coupon)) {
                    $request->coupon = trim($request->coupon);
                    $coupons         = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                    if (!empty($coupons)) {
                        $usedCoupun             = $coupons->used_coupon();
                        $discount_value         = ($price / 100) * $coupons->discount;
                        $plan->discounted_price = $price - $discount_value;
                        $coupons_id             = $coupons->id;
                        if ($usedCoupun >= $coupons->limit) {
                            return redirect()->back()->with('error', __('This coupon code has expired.'));
                        }
                        $price = $price - $discount_value;
                    } else {
                        return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                    }
                }
            }
            if ($price <= 0) {
                $authuser->plan = $plan->id;
                $authuser->save();

                $assignPlan = $authuser->assignPlan($plan->id);

                if ($assignPlan['is_success'] == true && !empty($plan)) {

                    $orderID = time();
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
                            'price' => $price == null ? 0 : $price,
                            'price_currency' => $admin['currency'] ? $admin['currency'] : 'USD',
                            'txn_id' => '',
                            'payment_type' => __('Skrill'),
                            'payment_status' => 'succeeded',
                            'receipt' => null,
                            'user_id' => $authuser->id,
                        ]
                    );
                    $assignPlan = $authuser->assignPlan($plan->id);

                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->back()->with('error', __('Plan fail to upgrade.'));
                }
            }

            $tran_id             = md5(date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id');
            $skill               = new SkrillRequest();
            $skill->pay_to_email = $this->email;
            $skill->return_url   = route(
                'plan.skrill',
                [
                    $request->plan_id,
                    'tansaction_id=' . MD5($tran_id),
                    'coupon_id=' . $coupons_id,
                ]
            );
            $skill->cancel_url   = route('plan.skrill', [$request->plan_id]);

            // create object instance of SkrillRequest
            $skill->transaction_id  = MD5($tran_id); // generate transaction id
            $skill->amount          = $price;
            $skill->currency        = $admin['currency'] ? $admin['currency'] : 'USD';
            $skill->language        = 'EN';
            $skill->prepare_only    = '1';
            $skill->merchant_fields = 'site_name, customer_email';
            $skill->site_name       = \Auth::user()->name;
            $skill->customer_email  = \Auth::user()->email;

            // create object instance of SkrillClient
            $client = new SkrillClient($skill);
            $sid    = $client->generateSID(); //return SESSION ID

            // handle error
            $jsonSID = json_decode($sid);
            if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST") {
                return redirect()->back()->with('error', $jsonSID->message);
            }


            // do the payment
            $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url
            if ($tran_id) {
                $data = [
                    'amount' => $price,
                    'trans_id' => MD5($request['transaction_id']),
                    'currency' => $admin['currency'] ? $admin['currency'] : 'USD',
                ];
                session()->put('skrill_data', $data);
            }
            return redirect($redirectUrl);
        } else {
            return redirect()->back()->with('error', 'Plan is deleted.');
        }
    }

    public function getPaymentStatus(Request $request, $plan)
    {
        $this->paymentConfig();
        $planID  = \Illuminate\Support\Facades\Crypt::decrypt($plan);
        $plan    = Plan::find($planID);
        $user    = \Auth::user();
        $orderID = time();
        $admin = Utility::getAdminPaymentSetting();
        if ($plan) {
            try {
                if (session()->has('skrill_data')) {
                    $get_data = session()->get('skrill_data');

                    if ($request->has('coupon_id') && $request->coupon_id != '') {
                        $coupons = Coupon::find($request->coupon_id);
                        if (!empty($coupons)) {
                            $userCoupon         = new UserCoupon();
                            $userCoupon->user   = $user->id;
                            $userCoupon->coupon = $coupons->id;
                            $userCoupon->order  = $orderID;
                            $userCoupon->save();


                            $usedCoupun = $coupons->used_coupon();
                            if ($coupons->limit <= $usedCoupun) {
                                $coupons->is_active = 0;
                                $coupons->save();
                            }
                        }
                    }
                    Utility::referralTransaction($plan);
                    $order                 = new Order();
                    $order->order_id       = $orderID;
                    $order->name           = $user->name;
                    $order->card_number    = '';
                    $order->card_exp_month = '';
                    $order->card_exp_year  = '';
                    $order->plan_name      = $plan->name;
                    $order->plan_id        = $plan->id;
                    $order->price          = isset($get_data['amount']) ? $get_data['amount'] : 0;
                    $order->price_currency = $admin['currency'] ? $admin['currency'] : 'USD';
                    $order->txn_id         = isset($request->transaction_id) ? $request->transaction_id : '';
                    $order->payment_type   = __('Skrill');
                    $order->payment_status = 'success';
                    $order->receipt        = '';
                    $order->user_id        = $user->id;
                    $order->save();

                    $assignPlan = $user->assignPlan($plan->id, $request->payment_frequency);
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                } else {
                    return redirect()->route('plans.index')->with('error', __('Transaction has been failed! '));
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', __('Plan not found!'));
            }
        }
    }

    public function retainerPayWithSkrill(Request $request)
    {
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($request->retainer_id);

        $retainer   = Retainer::find($retainerID);
        $setting = Utility::settingsById($retainer->created_by);
        if ($retainer) {


            if (Auth::check()) {
                $payment  = $this->paymentConfig();
                $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
                $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
                $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
                $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
                $settings = Utility::settingsById($retainer->created_by);
            }



            $result    = array();

            $price = $request->amount;


            if ($price > 0) {

                $tran_id             = md5(date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id');


                $skill               = new SkrillRequest();

                $skill->pay_to_email = $this->email;

                $skill->return_url   = route(
                    'retainer.skrill',
                    [
                        $request->retainer_id,
                        $price,
                        'tansaction_id=' . MD5($tran_id),
                    ]
                );


                $skill->cancel_url   = route(
                    'retainer.skrill',
                    [
                        $request->retainer_id,
                        $price,
                    ]
                );

                // create object instance of SkrillRequest
                $skill->transaction_id  = MD5($tran_id); // generate transaction id
                $skill->amount          = $price;
                $skill->currency        = $setting['site_currency'];
                $skill->language        = 'EN';
                $skill->prepare_only    = '1';
                $skill->merchant_fields = 'site_name, customer_email';
                $skill->site_name       = $retainer->customer->name;
                $skill->customer_email  =  $retainer->customer->email;


                // create object instance of SkrillClient
                $client = new SkrillClient($skill);

                $sid    = $client->generateSID(); //return SESSION ID

                // handle error
                $jsonSID = json_decode($sid);

                if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST") {
                    return redirect()->back()->with('error', $jsonSID->message);
                }


                // do the payment
                $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url

                if ($tran_id) {
                    $data = [
                        'amount' => $price,
                        'trans_id' => MD5($request['transaction_id']),
                        'currency' => $setting['site_currency'],
                    ];
                    session()->put('skrill_data', $data);
                }

                return redirect($redirectUrl);
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('customer.retainer')->with('error', __('Retainer is deleted.'));
        }
    }

    public function getRetainerPaymentStatus(Request $request, $retainer_id, $amount)
    {
        $retainerID = \Illuminate\Support\Facades\Crypt::decrypt($retainer_id);
        $retainer   = Retainer::find($retainerID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment  = $this->paymentConfig();
            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($retainer->created_by);
            $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
            $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
            $settings = Utility::settingsById($retainer->created_by);
            $objUser = $user;
        }



        $result    = array();

        if ($retainer) {
            try {

                if (session()->has('skrill_data')) {
                    $get_data = session()->get('skrill_data');


                    $payments = RetainerPayment::create(
                        [
                            'retainer' => $retainer->id,
                            'date' => date('Y-m-d'),
                            'amount' => $amount,
                            'payment_method' => 1,
                            'transaction' => $orderID,
                            'payment_type' => __('Skrill'),
                            'receipt' => '',
                            'notes' => __('Retainer') . ' ' . Utility::retainerNumberFormat($settings, $retainer->retainer_id),
                        ]
                    );

                    $retainer = Retainer::find($retainer->id);

                    if ($retainer->getDue() <= 0.0) {
                        Retainer::change_status($retainer->id, 4);
                    } elseif ($retainer->getDue() > 0) {
                        Retainer::change_status($retainer->id, 3);
                    } else {
                        Retainer::change_status($retainer->id, 2);
                    }

                    Utility::updateUserBalance('customer', $retainer->customer_id, $request->amount, 'debit');

                    Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');

                    //Twilio Notification
                    $setting  = Utility::settingsById($objUser->creatorId());

                    $customer = Customer::find($retainer->customer_id);
                    if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                        $uArr = [
                            'invoice_id' => $payments->id,
                            'payment_name' => $customer->name,
                            'payment_amount' => $request->amount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'Skrill',
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

                        // if ($status == true) {
                        //     return redirect()->route('payment.index')->with('success', __('Payment successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                        // } else {
                        //     return redirect()->back()->with('error', __('Webhook call failed.'));
                        // }
                    }


                    if (Auth::check()) {
                        return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
                    } else {
                        return redirect()->back()->with('success', __(' Payment successfully added.'));
                    }
                } else {
                    if (Auth::check()) {
                        return redirect()->route('customer.retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been ' . $status));
                    } else {
                        return redirect()->back()->with('success', __('Transaction succesfull'));
                    }
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Retainer not found!'));
            }
        }
    }

    public function invoicePayWithSkrill(Request $request)
    {
        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($request->invoice_id);
        $invoice   = Invoice::find($invoiceID);
        $setting = Utility::settingsById($invoice->created_by);
        
        if ($invoice) {

            if (Auth::check()) {
                $payment  = $this->paymentConfig();
                $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
                $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            } else {
                $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
                $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
                $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
                $settings = Utility::settingsById($invoice->created_by);
            }



            $result    = array();

            $price = $request->amount;
            if ($price > 0) {

                $tran_id             = md5(date('Y-m-d') . strtotime('Y-m-d H:i:s') . 'user_id');
                $skill               = new SkrillRequest();
                $skill->pay_to_email = $this->email;
                $skill->return_url   = route(
                    'customer.invoice.skrill',
                    [
                        $request->invoice_id,
                        $price,
                        'tansaction_id=' . MD5($tran_id),
                    ]
                );
                $skill->cancel_url   = route(
                    'customer.invoice.skrill',
                    [
                        $request->invoice_id,
                        $price,
                    ]
                );

                // create object instance of SkrillRequest
                $skill->transaction_id  = MD5($tran_id); // generate transaction id
                $skill->amount          = $price;
                $skill->currency        = $setting['site_currency'];
                $skill->language        = 'EN';
                $skill->prepare_only    = '1';
                $skill->merchant_fields = 'site_name, customer_email';
                $skill->site_name       = $invoice->customer->name;
                $skill->customer_email  =  $invoice->customer->email;

                // create object instance of SkrillClient
                $client = new SkrillClient($skill);
                $sid    = $client->generateSID(); //return SESSION ID

                // handle error
                $jsonSID = json_decode($sid);

                if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST") {
                    return redirect()->back()->with('error', $jsonSID->message);
                }


                // do the payment
                $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url
                if ($tran_id) {
                    $data = [
                        'amount' => $price,
                        'trans_id' => MD5($request['transaction_id']),
                        'currency' => $setting['site_currency'],
                    ];
                    session()->put('skrill_data', $data);
                }

                return redirect($redirectUrl);
            } else {
                $res['msg']  = __("Enter valid amount.");
                $res['flag'] = 2;

                return $res;
            }
        } else {
            return redirect()->route('invoice.index')->with('error', __('Invoice is deleted.'));
        }
    }

    public function getInvoicePaymentStatus(Request $request, $invoice_id, $amount)
    {

        $invoiceID = \Illuminate\Support\Facades\Crypt::decrypt($invoice_id);
        $invoice   = Invoice::find($invoiceID);
        if (Auth::check()) {
            $objUser = \Auth::user();
            $payment  = $this->paymentConfig();
            $orderID  = strtoupper(str_replace('.', '', uniqid('', true)));
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $payment_setting = Utility::getNonAuthCompanyPaymentSetting($invoice->created_by);
            $this->email      = isset($payment_setting['skrill_email']) ? $payment_setting['skrill_email'] : '';
            $this->is_enabled = isset($payment_setting['is_skrill_enabled']) ? $payment_setting['is_skrill_enabled'] : 'off';
            $settings = Utility::settingsById($invoice->created_by);
            $objUser = $user;
        }



        $result    = array();

        if ($invoice) {
            try {

                if (session()->has('skrill_data')) {
                    $get_data = session()->get('skrill_data');


                    $payments = InvoicePayment::create(
                        [
                            'invoice' => $invoice->id,
                            'date' => date('Y-m-d'),
                            'amount' => $amount,
                            'payment_method' => 1,
                            'transaction' => $orderID,
                            'payment_type' => __('Skrill'),
                            'receipt' => '',
                            'notes' => __('Invoice') . ' ' . Utility::invoiceNumberFormat($settings, $invoice->invoice_id),
                        ]
                    );

                    $invoice = Invoice::find($invoice->id);

                    if ($invoice->getDue() <= 0.0) {
                        Invoice::change_status($invoice->id, 4);
                    } elseif ($invoice->getDue() > 0) {
                        Invoice::change_status($invoice->id, 3);
                    } else {
                        Invoice::change_status($invoice->id, 2);
                    }

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
                            'type' => 'Skrill',
                            'user_name' => $objUser->name,
                        ];

                        Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $invoice->created_by);
                    }

                    Utility::updateUserBalance('customer', $invoice->customer_id, $request->amount, 'debit');

                    Utility::bankAccountBalance($request->account_id, $request->amount, 'credit');


                    // webhook
                    $module = 'New Payment';

                    $webhook =  Utility::webhookSetting($module, $invoice->created_by);

                    if ($webhook) {

                        $parameter = json_encode($invoice);

                        // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                        $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);

                        // if ($status == true) {
                        //     return redirect()->route('payment.index')->with('success', __('Payment successfully created.') . ((isset($smtp_error)) ? '<br> <span class="text-danger">' . $smtp_error . '</span>' : ''));
                        // } else {
                        //     return redirect()->back()->with('error', __('Webhook call failed.'));
                        // }
                    }


                    if (Auth::check()) {
                        return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
                    } else {
                        return redirect()->back()->with('success', __(' Payment successfully added.'));
                    }
                } else {
                    if (Auth::check()) {
                        return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been ' . $status));
                    } else {
                        return redirect()->back()->with('success', __('Transaction succesfull'));
                    }
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Invoice not found!'));
            }
        }
    }
}
