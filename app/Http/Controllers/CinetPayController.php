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

class CinetPayController extends Controller
{
   
    public function planPayWithCinetPay(Request $request)
    {
        $authuser           = Auth::user();
        $payment_setting    = Utility::getAdminPaymentSetting();
        $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $planID             = Crypt::decrypt($request->plan_id);

        $plan       = Plan::find($planID);
        $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
        $user       = Auth::user();

        if ($plan) {

            $plan_amount    = 100;
            // $order_id       = strtoupper(str_replace('.', '', uniqid('', true)));

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun     = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $plan_amount     = $plan->price - $discount_value;

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

                    $userCoupon = new UserCoupon();
                    $userCoupon->user = $authuser->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
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
                            'price_currency'    => $currency,
                            'txn_id'            => '',
                            'payment_type'      => 'Cinetpay',
                            'payment_status'    => 'success',
                            'receipt'           => null,
                            'user_id'           => $authuser->id,
                        ]
                    );
                    // $assignPlan = $authuser->assignPlan($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                }
            }

            try {

                if (
                    $currency != 'XOF' &&
                    $currency != 'CDF' &&
                    $currency != 'USD' &&
                    $currency != 'KMF' &&
                    $currency != 'GNF'
                ) {
                    return redirect()->route('plans.index')->with('error', __('Availabe currencies: XOF, CDF, USD, KMF, GNF'));
                }

                $cinetpay_data =  [
                    "amount"            => $plan_amount,
                    "currency"          => $currency,
                    "apikey"            => $payment_setting['cinetpay_api_key'],
                    "site_id"           => $payment_setting['cinetpay_site_id'],
                    "transaction_id"    => $orderID,
                    "description"       => "Plan Subscription",
                    "return_url"        => route('plan.cinetpay.return'),
                    "notify_url"        => route('plan.cinetpay.notify'),
                    "metadata"          => "user001",
                    'customer_name'     => isset($user->name )? $user->name : '',
                    'customer_surname'  => isset($user->name )? $user->name : '',
                    'customer_email'    => isset($user->email )? $user->email : '',
                    'customer_phone_number' => '',
                    'customer_address'  => '',
                    'customer_city'     => 'texas',
                    'customer_country'  => 'BF',
                    'customer_state'    => 'USA',
                    'customer_zip_code' => '',
                ];

                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 45,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                    CURLOPT_SSL_VERIFYPEER => 0,
                    CURLOPT_HTTPHEADER => array(
                        "content-type:application/json"
                    ),
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                //On recupère la réponse de CinetPay
                $response_body = json_decode($response, true);
                if (isset($response_body['code']) && $response_body['code'] == '201') {
                    $cinetpaySession = [
                        'order_id'      => $orderID,
                        'plan_id'       => $plan->id,
                        'plan_amount'   => $plan_amount,
                        'coupon_code'   => !empty($request->coupon_code) ? $request->coupon_code :'',
                    ];

                    $request->session()->put('cinetpaySession', $cinetpaySession);

                    $payment_link = $response_body["data"]["payment_url"]; // Retrieving the payment URL
                    return redirect($payment_link);
                } else {
                    return back()->with('error', isset($response_body["description"]) ? $response_body["description"] :'Something Went Wrong!!!');
                }
            } catch (\Exception $e) {
                return redirect()->route('plans.index')->with('error', $e->getMessage());
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Plan is deleted.'));
        }
    }

    public function planCinetPayReturn(Request $request)
    {
        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');

        $payment_setting = Utility::getAdminPaymentSetting();
        $currency        = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $plan            = Plan::find($request->plan_id);
        
        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey"            => $payment_setting['cinetpay_api_key'],
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);

            if ($response_body['code'] == '00') {

                Order::create(
                    [
                        'order_id' => $request->order_id,
                        'name' => null,
                        'email' => null,
                        'card_number' => null,
                        'card_exp_month' => null,
                        'card_exp_year' => null,
                        'plan_name' => !empty($plan->name) ? $plan->name : 'Basic Plan',
                        'plan_id' => $plan->id,
                        'price' => !empty($request->plan_amount) ? $request->plan_amount : 0,
                        'price_currency' => $currency,
                        'txn_id' => '',
                        'payment_type' => __('Cinetpay'),
                        'payment_status' => 'success',
                        'receipt' => null,
                        'user_id' => $authuser->id,
                    ]
                );

                $Order = Order::where('order_id', $request->order_id)->first();
                $Order->payment_status = 'success';
                $Order->save();

                $plan = Plan::find($request->plan_id);
                Utility::referralTransaction($plan);
                $assignPlan = $authuser->assignPlan($plan->id);

                if ($assignPlan['is_success']) {
                    return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                } else {
                    return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                }
            } else {

                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        } else {
            return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
        }
    }

    public function planCinetPayNotify(Request $request , $id= null)
    {
        /* 1- Recovery of parameters posted on the URL by CinetPay
         * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#les-etapes-pour-configurer-lurl-de-notification
         * */
        if (isset($request->cpm_trans_id)) {
            // Using your transaction identifier, check that the order has not yet been processed
            $VerifyStatusCmd = "1"; // status value to retrieve from your database
            if ($VerifyStatusCmd == '00') {
                //The order has already been processed
                // Scarred you script
                die();
            }
            if($id == null){

                $payment_setting = Utility::getAdminPaymentSetting();

            }else{

                $comapnysetting = Utility::getCompanyPaymentSetting($id);

            }

            /* 2- Otherwise, we check the status of the transaction in the event of a payment attempt on CinetPay
            * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#2-verifier-letat-de-la-transaction */
            $cinetpay_check = [
                "apikey" => $payment_setting['cinetpay_api_key'],
                "site_id" => $payment_setting['cinetpay_site_id'],
                "transaction_id" => $request->cpm_trans_id
            ];

            $response = $this->getPayStatus($cinetpay_check); // call query function to retrieve status

            //We get the response from CinetPay
            $response_body = json_decode($response, true);
            // if ($response_body['code'] == '00') {
            //     /* correct, on délivre le service
            //      * https://docs.cinetpay.com/api/1.0-fr/checkout/notification#3-delivrer-un-service*/
            //     echo 'Congratulations, your payment has been successfully completed';
            // } else {
            //     // transaction a échoué
            //     echo 'Failure, code:' . $response_body['code'] . ' Description' . $response_body['description'] . ' Message: ' . $response_body['message'];
            // }
            // Update the transaction in your database
            /*  $order->update(); */
        } else {
            // print("cpm_trans_id non found");
        }
    }

    public function getPayStatus($data)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment/check',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => array(
                "content-type:application/json"
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err)
            return redirect()->route('plans.index')->with('error', __('Something went wrong!'));

        else
            return ($response);
    }

    public function invoicePayWithCinetPay(Request $request, $invoice_id)
    {
        try {
            $invoice            = Invoice::find($invoice_id);
            $customer           = Customer::find($invoice->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $api_key            = isset($payment_setting['cinetpay_public_key']) ? $payment_setting['cinetpay_public_key'] : '';
            $get_amount         = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            if (
                $currency != 'XOF' &&
                $currency != 'CDF' &&
                $currency != 'USD' &&
                $currency != 'KMF' &&
                $currency != 'GNF'
            ) {
                return redirect()->route('plans.index')->with('error', __('Availabe currencies: XOF, CDF, USD, KMF, GNF'));
            }

            $cinetpay_data =  [
                "amount"            => $get_amount,
                "currency"          => $currency,
                "apikey"            => $api_key,
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $order_id,
                "description"       => "Invoice Payment",
                "return_url"        => route('invoice.cinetpay.return', [$invoice_id, $get_amount]),
                "notify_url"        => route('plan.cinetpay.notify', $invoice_id),
                "metadata"          => $invoice->id,
                'customer_name'     => $customer->name,
                'customer_email'    => $customer->email,
                // Add other customer details if required
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => array(
                    "content-type:application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            $response_body = json_decode($response, true);
            if (isset($response_body['code']) && $response_body['code'] == '201') {
                // Store CinetPay session data if needed
                // Redirect to CinetPay payment URL
                $payment_link = $response_body["data"]["payment_url"];
                return redirect($payment_link);
            } else {
                return redirect()->back()->with('error', isset($response_body["description"]) ? $response_body["description"] : 'Something Went Wrong!!!');
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function invoiceCinetPayReturn(Request $request , $invoice_id, $get_amount)
    {
        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');

        $payment_setting = Utility::getAdminPaymentSetting();
        $currency        = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $invoice         = Invoice::find($invoice_id);

        
        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey"            => $payment_setting['cinetpay_api_key'],
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);

            if ($response_body['code'] == '00') {

                $setting    = Utility::settingsById($invoice->created_by);

                try{

                    $payments = InvoicePayment::create(
                        [
                            'invoice_id'        => $invoice->id,
                            'date'              => date('Y-m-d'),
                            'amount'            => $getAmount,
                            'account_id'        => 0,
                            'payment_method'    => 0,
                            'order_id'          => $request->transaction_id,
                            'currency'          => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                            'txn_id'            => '',
                            'payment_type'      => __('CinetPay'),
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
                    $invoicePayment->type        = 'CinetPay';
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
                            'invoice_id' => $invoice->id,
                            'payment_name' => isset($customer->name) ? $customer->name : '',
                            'payment_amount' => $getAmount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'CinetPay',
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
                    return redirect()->back()->with('success', __('Transaction has been success'));
            
                } catch (\Throwable $e) {
                    return redirect()->back()->with('error', __($e->getMessage()));
                }
            } else {
                return redirect()->route('plans.index')->with('error', __('Your Payment has failed!'));
            }
        }
    }

    public function retainerPayWithCinetPay(Request $request, $retainer_id)
    {
        try {
            $retainer           = Retainer::find($retainer_id);
            $customer           = Customer::find($retainer->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($retainer->created_by);
            $currency           = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
            $api_key            = isset($payment_setting['cinetpay_public_key']) ? $payment_setting['cinetpay_public_key'] : '';
            $get_amount         = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            if (
                $currency != 'XOF' &&
                $currency != 'CDF' &&
                $currency != 'USD' &&
                $currency != 'KMF' &&
                $currency != 'GNF'
            ) {
                return redirect()->route('retainers.index')->with('error', __('Available currencies: XOF, CDF, USD, KMF, GNF'));
            }

            $cinetpay_data =  [
                "amount"            => $get_amount,
                "currency"          => $currency,
                "apikey"            => $api_key,
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $order_id,
                "description"       => "Retainer Payment",
                "return_url"        => route('retainer.cinetpay.return', [$retainer_id, $get_amount]),
                "notify_url"        => route('plan.cinetpay.notify', $retainer_id),
                "metadata"          => $retainer->id,
                'customer_name'     => $customer->name,
                'customer_email'    => $customer->email,
                // Add other customer details if required
            ];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api-checkout.cinetpay.com/v2/payment',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($cinetpay_data),
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_HTTPHEADER => array(
                    "content-type:application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            $response_body = json_decode($response, true);
            if (isset($response_body['code']) && $response_body['code'] == '201') {
                // Store CinetPay session data if needed
                // Redirect to CinetPay payment URL
                $payment_link = $response_body["data"]["payment_url"];
                return redirect($payment_link);
            } else {
                return redirect()->back()->with('error', isset($response_body["description"]) ? $response_body["description"] : 'Something Went Wrong!!!');
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    public function retainerCinetPayReturn(Request $request, $retainer_id, $get_amount)
    {
        $cinetpaySession = $request->session()->get('cinetpaySession');
        $request->session()->forget('cinetpaySession');

        $payment_setting = Utility::getAdminPaymentSetting();
        $currency        = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $retainer        = Retainer::find($retainer_id);

        if (isset($request->transaction_id) || isset($request->token)) {

            $cinetpay_check = [
                "apikey"            => $payment_setting['cinetpay_api_key'],
                "site_id"           => $payment_setting['cinetpay_site_id'],
                "transaction_id"    => $request->transaction_id
            ];

            $response = $this->getPayStatus($cinetpay_check);

            $response_body = json_decode($response, true);

            if ($response_body['code'] == '00') {

                $setting = Utility::settingsById($retainer->created_by);

                try{

                    $payments = RetainerPayment::create(
                        [
                            'retainer_id'      => $retainer->id,
                            'date'             => date('Y-m-d'),
                            'amount'           => $getAmount,
                            'account_id'       => 0,
                            'payment_method'   => 0,
                            'order_id'         => $request->transaction_id,
                            'currency'         => isset($setting['site_currency']) ? $setting['site_currency'] : 'USD',
                            'txn_id'           => '',
                            'payment_type'     => __('CinetPay'),
                            'receipt'          => '',
                            'reference'        => '',
                            'description'      => 'Retainer ' . Utility::retainerNumberFormat($setting, $retainer->retainer_id),
                        ]
                    );

                    if ($retainer->getDue() <= 0) {
                        $retainer->status = 4;
                        $retainer->save();
                    } elseif (($retainer->getDue() - $payments->amount) == 0) {
                        $retainer->status = 4;
                        $retainer->save();
                    } elseif ($retainer->getDue() > 0) {
                        $retainer->status = 3;
                        $retainer->save();
                    } else {
                        $retainer->status = 2;
                        $retainer->save();
                    }

                    $retainerPayment              = new \App\Models\Transaction();
                    $retainerPayment->user_id     = $retainer->customer_id;
                    $retainerPayment->user_type   = 'Customer';
                    $retainerPayment->type        = 'CinetPay';
                    $retainerPayment->created_by  = \Auth::check() ? \Auth::user()->id : $retainer->customer_id;
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
                            'retainer_id' => $payments->id,
                            'payment_name' => $customer->name,
                            'payment_amount' => $getAmount,
                            'payment_date' => $objUser->dateFormat($request->date),
                            'type' => 'CinetPay',
                            'user_name' => $objUser->name,
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

                    return redirect()->route('retainers.index')->with('success', __('Transaction has been successful'));

                } catch (\Throwable $e) {
                    return redirect()->route('retainers.index')->with('error', __($e->getMessage()));
                }
            } else {
                return redirect()->route('retainers.index')->with('error', __('Your Payment has failed!'));
            }
        }
    }

}

