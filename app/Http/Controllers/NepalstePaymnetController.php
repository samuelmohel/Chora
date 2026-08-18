<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\UserCoupon;
use App\Models\Utility;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Retainer;
use App\Models\RetainerPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NepalstePaymnetController extends Controller
{
    public function planPayWithNepalste(Request $request)
    {

        $authuser           = Auth::user();
        $payment_setting    = Utility::getAdminPaymentSetting();
        $api_key            = isset($payment_setting['nepalste_public_key']) ? $payment_setting['nepalste_public_key'] : '';
        $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $planID             = Crypt::decrypt($request->plan_id);

        $plan       = Plan::find($planID);
        $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
        $user       = Auth::user();

        if ($plan) {

            $plan_amount    = $plan->price;
            $order_id       = strtoupper(str_replace('.', '', uniqid('', true)));
            $user           = Auth::user();

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun         = $coupons->used_coupon();
                    $discount_value     = ($plan->price / 100) * $coupons->discount;
                    $plan_amount        = $plan->price - $discount_value;

                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }
            if ($plan_amount <= 0) {
                $authuser = \Auth::user();
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
                    $userCoupon->user   = $authuser->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order  = $orderID;
                    $userCoupon->save();

                    Order::create(
                        [
                            'order_id'          => $orderID,
                            'name'              => null,
                            'email'             => null,
                            'card_number'       => null,
                            'card_exp_month'    => null,
                            'card_exp_year'     => null,
                            'plan_name'         => $plan->name,
                            'plan_id'           => $plan->id,
                            'price'             => $plan_amount == null ? 0 : $plan_amount,
                            'price_currency'    => !empty($payment_setting['currency']) ? $payment_setting['currency'] : 'USD',
                            'txn_id'            => '',
                            'payment_type'      => 'Nepalste',
                            'payment_status'    => 'Succeeded',
                            'receipt'           => null,
                            'user_id'           => $authuser->id,
                        ]
                    );
                    $assignPlan = $authuser->assignPlan($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                }
            }
        }

        if (!empty($request->coupon))
        {
            $response = ['plan_amount' => $plan_amount, 'plan' => $plan , 'coupon_id'=>$coupons->id];
        }
        else{
            $response = ['plan_amount' => $plan_amount, 'plan' => $plan];
        }

        $parameters = [
            'identifier'        => 'DFU80XZIKS',
            'currency'          => $currency,
            'amount'            => $plan_amount,
            'details'           => $plan->name,
            'ipn_url'           => route('plan.nepalste.status',$response),
            'cancel_url'        => route('plan.nepalste.cancel'),
            'success_url'       => route('plan.nepalste.status',$response),
            'public_key'        => $api_key,
            'site_logo'         => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
            'checkout_theme'    => 'dark',
            'customer_name'     => $authuser->name,
            'customer_email'    => $authuser->email,
        ];

        //live end point
        $liveUrl    = "https://nepalste.com.np/payment/initiate";
        //test end point
        $sandboxUrl = "https://nepalste.com.np/sandbox/payment/initiate";

        $url = $payment_setting['nepalste_mode'] == 'live' ? $liveUrl : $sandboxUrl ;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($result, true);

        if(isset($result['success'])){
            return redirect($result['url']);
        }else{
            return redirect()->back()->with('error',__('Something went wrong.'));
        }
    }

    public function planGetNepalsteStatus(Request $request)
    {
        $payment_setting = Utility::getAdminPaymentSetting();

        $currency   = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
        $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
        $getAmount  = $request->plan_amount;
        $authuser   = \Auth::user();
        $plan       = Plan::find($request->plan);

        Utility::referralTransaction($plan);
    
        $order = new Order();
        $order->order_id        = $orderID;
        $order->name            = $authuser->name;
        $order->card_number     = '';
        $order->card_exp_month  = '';
        $order->card_exp_year   = '';
        $order->plan_name       = $plan->name;
        $order->plan_id         = $plan->id;
        $order->price           = $getAmount;
        $order->price_currency  = $currency;
        $order->txn_id          = $orderID;
        $order->payment_type    = __('Neplaste');
        $order->payment_status  = 'success';
        $order->txn_id          = '';
        $order->receipt         = '';
        $order->user_id         = $authuser->id;
        $order->save();

        $assignPlan = $authuser->assignPlan($plan->id);

        if ($assignPlan['is_success'])
        {
            return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
        } else
        {
            return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
        }
    }

    public function planGetNepalsteCancel(Request $request)
    {
        return redirect()->back()->with('error',__('Transaction has failed'));
    }

    public function invoicePayWithNepalste(Request $request, $invoice_id)
    {
        try {
            $invoice        = Invoice::find($invoice_id);
            $customers      = Customer::find($invoice->customer_id);
            $comapnysetting = Utility::getCompanyPaymentSetting($invoice->created_by);
            $api_key        = isset($comapnysetting['nepalste_public_key']) ? $comapnysetting['nepalste_public_key'] : '';
            $get_amount     = $request->amount;
            $setting        = Utility::settingsById($invoice->created_by);
            $order_id       = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            $parameters = [
                'identifier'        => 'DFU80XZIKS',
                'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                'amount'            => $get_amount,
                'details'           => $invoice->id,
                'ipn_url'           => route('invoice.nepalste.status',[$invoice_id, $get_amount]),
                'cancel_url'        => route('plan.nepalste.cancel'),
                'success_url'       => route('invoice.nepalste.status',[$invoice_id, $get_amount]),
                'public_key'        => $api_key,
                'site_logo'         => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
                'checkout_theme'    => 'dark',
                'customer_name'     => $customers->name,
                'customer_email'    => $customers->email,
            ];
    
            //live end point
            $liveUrl    = "https://nepalste.com.np/payment/initiate";
            //test end point
            $sandboxUrl = "https://nepalste.com.np/sandbox/payment/initiate";
    
            $url = $comapnysetting['nepalste_mode'] == 'live' ? $liveUrl : $sandboxUrl ;
    
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  $parameters);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
    
            $result = json_decode($result, true);
    
            if(isset($result['success'])){
                return redirect($result['url']);
            }else{
                return redirect()->back()->with('error',__($result['message']));
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceGetNepalsteStatus(Request $request, $invoice_id , $getAmount )
    {
        $invoice    = Invoice::find($invoice_id);
        $setting    = Utility::settingsById($invoice->created_by);
        $order_id   = strtoupper(str_replace('.', '', uniqid('', true)));

        try{

            $payments = InvoicePayment::create(
                [
                    'invoice_id'        => $invoice->id,
                    'date'              => date('Y-m-d'),
                    'amount'            => $getAmount,
                    'account_id'        => 0,
                    'payment_method'    => 0,
                    'order_id'          => $order_id,
                    'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                    'txn_id'            => '',
                    'payment_type'      => __('Nepalste'),
                    'receipt'           => '',
                    'reference'         => '',
                    'description'       => 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id),
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
            $invoicePayment->type        = 'Nepalste';
            $invoicePayment->created_by  = Auth::check() ? Auth::user()->id : $invoice->customer_id;
            $invoicePayment->payment_id  = $payments->id;
            $invoicePayment->category    = 'Invoice';
            $invoicePayment->amount      = $getAmount;
            $invoicePayment->date        = date('Y-m-d');
            $invoicePayment->created_by  = Auth::check() ? \Auth::user()->creatorId() : $invoice->created_by;
            $invoicePayment->description = 'Invoice ' . Utility::invoiceNumberFormat($setting, $invoice->invoice_id);
            $invoicePayment->account     = 0;

            \App\Models\Transaction::addTransaction($invoicePayment);

            Utility::updateUserBalance('customer', $invoice->customer_id, $getAmount, 'debit');

            Utility::bankAccountBalance($request->account_id, $getAmount, 'credit');

            //Twilio Notification
            $customer = $objUser = Customer::find($invoice->customer_id);
            $setting  = Utility::settingsById($objUser->creatorId());
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'invoice_id'        => $invoice->id,
                    'payment_name'      => isset($customer->name) ? $customer->name : '',
                    'payment_amount'    => $getAmount,
                    'payment_date'      => $objUser->dateFormat($request->date),
                    'type'              => 'Nepalste',
                    'user_name'         => $objUser->name,
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
            return redirect()->back()->with('success', __('Transaction has been success'));

        } catch (\Exception $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerPayWithNepalste(Request $request, $retainer_id)
    {
        try {
            $retainer       = Retainer::find($retainer_id);
            $customers      = Customer::find($retainer->customer_id);
            $comapnysetting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $api_key        = isset($comapnysetting['nepalste_public_key']) ? $comapnysetting['nepalste_public_key'] : '';
            $get_amount     = $request->amount;
            $setting        = Utility::settingsById($retainer->created_by);
            $order_id       = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            $parameters = [
                'identifier'        => 'DFU80XZIKS',
                'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                'amount'            => $get_amount,
                'details'           => $retainer->id,
                'ipn_url'           => route('retainer.nepalste.status', [$retainer_id, $get_amount]),
                'cancel_url'        => route('plan.nepalste.cancel'),
                'success_url'       => route('retainer.nepalste.status', [$retainer_id, $get_amount]),
                'public_key'        => $api_key,
                'site_logo'         => 'https://nepalste.com.np/assets/images/logoIcon/logo.png',
                'checkout_theme'    => 'dark',
                'customer_name'     => $customers->name,
                'customer_email'    => $customers->email,
            ];

            // Live endpoint
            $liveUrl    = "https://nepalste.com.np/payment/initiate";
            // Sandbox endpoint
            $sandboxUrl = "https://nepalste.com.np/sandbox/payment/initiate";

            $url = $comapnysetting['nepalste_mode'] == 'live' ? $liveUrl : $sandboxUrl ;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POSTFIELDS,  $parameters);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($result, true);

            if(isset($result['success'])){
                return redirect($result['url']);
            }else{
                return redirect()->back()->with('error',__($result['message']));
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerGetNepalsteStatus(Request $request, $retainer_id , $getAmount)
    {
        $retainer       = Retainer::find($retainer_id);
        $objUser        = User::where('id', $retainer->created_by)->first();
        $getAmount      = $getAmount;
        $setting        = Utility::settingsById($retainer->created_by);
        $comapnysetting = Utility::getCompanyPaymentSetting($retainer->created_by);


        try {

            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));
            $payments = RetainerPayment::create(
                [

                    'retainer_id'       => $retainer->id,
                    'date'              => date('Y-m-d'),
                    'amount'            => $getAmount,
                    'account_id'        => 0,
                    'payment_method'    => 0,
                    'order_id'          => $order_id,
                    'currency'          => $setting['site_currency'],
                    'txn_id'            => $getAmount,
                    'payment_type'      => __('Nepalste'),
                    'receipt'           => '',
                    'reference'         => '',
                    'description'       => 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id),
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
            $retainerPayment->type        = 'Nepalste';
            $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
            $retainerPayment->payment_id  = $retainerPayment->id;
            $retainerPayment->category    = 'Retainer';
            $retainerPayment->amount      = $getAmount;
            $retainerPayment->date        = date('Y-m-d');
            $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->creatorId() : $retainer->created_by;
            $retainerPayment->payment_id  = $payments->id;
            $retainerPayment->description = 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id);
            $retainerPayment->account     = 0;

            \App\Models\Transaction::addTransaction($retainerPayment);

            Utility::updateUserBalance('customer', $retainer->customer_id, $getAmount, 'debit');

            Utility::bankAccountBalance($request->account_id, $getAmount, 'credit');

            //Twilio Notification
            $setting  = Utility::settingsById($objUser->creatorId());
            $customer = Customer::find($retainer->customer_id);
            if (isset($setting['payment_notification']) && $setting['payment_notification'] == 1) {
                $uArr = [
                    'retainer_id'       => $payments->id,
                    'payment_name'      => $customer->name,
                    'payment_amount'    => $getAmount,
                    'payment_date'      => $objUser->dateFormat($request->date),
                    'type'              => 'Nepalste',
                    'user_name'         => $objUser->name,
                ];

                Utility::send_twilio_msg($customer->contact, 'new_payment', $uArr, $retainer->created_by);
            }

            // webhook\
            $module = 'New Payment';

            $webhook =  Utility::webhookSetting($module, $retainer->created_by);

            if ($webhook) {

                $parameter = json_encode($retainer);

                // 1 parameter is  URL , 2 parameter is data , 3 parameter is method

                $status = Utility::WebhookCall($webhook['url'], $parameter, $webhook['method']);
            }
            return redirect()->back()->with('success', __('Transaction has been success'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

}
