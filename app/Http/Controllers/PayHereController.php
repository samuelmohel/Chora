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
use Lahirulhr\PayHere\PayHere;

class PayHereController extends Controller
{
    public $paymentSetting;
    public function __construct()
    {
        // $paymentSetting = Utility::getAdminPaymentSetting();
        // $config = [
        //     'payhere.api_endpoint' => isset($paymentSetting['payhere_mode']) && $paymentSetting['payhere_mode'] === 'sandbox'
        //         ? 'https://sandbox.payhere.lk/'
        //         : 'https://www.payhere.lk/',
        // ];

        // $config['payhere.merchant_id']      = $paymentSetting['payhere_merchant_id'] ?? '';
        // $config['payhere.merchant_secret']  = $paymentSetting['payhere_merchant_secret'] ?? '';
        // $config['payhere.app_secret']       = $paymentSetting['payhere_app_secret'] ?? '';
        // $config['payhere.app_id']           = $paymentSetting['payhere_app_id'] ?? '';

        // config($config);

        // $this->paymentSetting = $paymentSetting;
    }

    public function planPayWithPayHere(Request $request)
    {
        $paymentSetting = Utility::getAdminPaymentSetting();
          $config = [
            'payhere.api_endpoint' => isset($paymentSetting['payhere_mode']) && $paymentSetting['payhere_mode'] === 'sandbox'
                ? 'https://sandbox.payhere.lk/'
                : 'https://www.payhere.lk/',
        ];

        $config['payhere.merchant_id']      = $paymentSetting['payhere_merchant_id'] ?? '';
        $config['payhere.merchant_secret']  = $paymentSetting['payhere_merchant_secret'] ?? '';
        $config['payhere.app_secret']       = $paymentSetting['payhere_app_secret'] ?? '';
        $config['payhere.app_id']           = $paymentSetting['payhere_app_id'] ?? '';

        config($config);

        $this->paymentSetting = $paymentSetting;
        $orderID    = strtoupper(str_replace('.', '', uniqid('', true)));
        $planID     = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
        $plan       = Plan::find($planID);

        $authuser = Auth::user();

        if ($plan) {
            $get_amount = $plan->price;

            if (!empty($request->coupon)) {
                $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if (!empty($coupons)) {
                    $usedCoupun     = $coupons->used_coupon();
                    $discount_value = ($plan->price / 100) * $coupons->discount;
                    $get_amount     = $plan->price - $discount_value;

                    $userCoupon = new UserCoupon();
                    $userCoupon->user   = Auth::user()->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order  = $orderID;
                    $userCoupon->save();

                    if ($coupons->limit == $usedCoupun) {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                } else {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }
            if ($get_amount <= 0) {
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
                    $userCoupon->user = $authuser->id;
                    $userCoupon->coupon = $coupons->id;
                    $userCoupon->order = $orderID;
                    $userCoupon->save();
                    Order::create(
                        [
                            'order_id'      => $orderID,
                            'name'          => null,
                            'email'         => null,
                            'card_number'   => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name'     => $plan->name,
                            'plan_id'       => $plan->id,
                            'price'         => $get_amount == null ? 0 : $get_amount,
                            'price_currency' => !empty($payment_setting['currency']) ? $payment_setting['currency'] : 'USD',
                            'txn_id'        => '',
                            'payment_type'  => 'Nepalste',
                            'payment_status' => 'success',
                            'receipt'       => null,
                            'user_id'       => $authuser->id,
                        ]
                    );
                    $assignPlan = $authuser->assignPlan($plan->id);
                    return redirect()->route('plans.index')->with('success', __('Plan Successfully Activated'));
                }
            }

            $hash = strtoupper(
                md5(
                    config('payhere.merchant_id') .
                        $orderID .
                        number_format($get_amount, 2, '.', '') .
                        'LKR' .
                        strtoupper(md5(config('payhere.merchant_secret')))
                )
            );

            $data = [
                'first_name'    => $authuser->name,
                'last_name'     => '',
                'email'         => $authuser->email,
                'address'       => '',
                'city'          => '',
                'country'       => '',
                'order_id'      => $orderID,
                'items'         => $plan->name,
                'currency'      => 'LKR',
                'amount'        => $get_amount,
                'hash'          => $hash,
            ];

            return PayHere::checkOut()
                ->data($data)
                ->successUrl(route('plan.payhere.status', ['success' => 1, 'data' => $request->all(),'amount'=>$get_amount]))
                ->failUrl(route('plan.payhere.status', ['success' => 0, 'data' => $request->all()]))
                ->renderView();
        } else {
            return redirect()->back()->with('error', __('Plan not found!'));
        }
    }

    public function planGetPayHereStatus(Request $request)
    {
        if ($request->success == 1) {
            $info = PayHere::retrieve()
                ->orderId($request->order_id) // order number that you use to charge from customer
                ->submit();

            if ($info['data'][0]['order_id'] == $request->order_id) {
                if ($info['data'][0]['status'] == "RECEIVED") {

                    $planID = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
                    $plan = Plan::find($planID);

                    Utility::referralTransaction($plan);


                    $order                 = new Order();
                    $order->order_id       = $request->order_id;
                    $order->name           = Auth::user()->name;
                    $order->card_number    = '';
                    $order->card_exp_month = '';
                    $order->card_exp_year  = '';
                    $order->plan_name      = $plan->name;
                    $order->plan_id        = $plan->id;
                    $order->price          = isset($request->amount) ? $request->amount / 100 : 0;
                    $order->price_currency = 'LKR';
                    $order->txn_id         = app('App\Http\Controllers\BillController')->transactionNumber(Auth::user()->id);
                    $order->payment_type   = __('PayHere');
                    $order->payment_status = 'success';
                    $order->receipt        = '';
                    $order->user_id        = Auth::user()->id;
                    $order->save();

                    $assignPlan = Auth::user()->assignPlan($plan->id, $request->payment_frequency);

                    if ($assignPlan['is_success']) {
                        return redirect()->route('plans.index')->with('success', __('Plan activated Successfully!'));
                    } else {
                        return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
                    }
                }
            }
        } else {
            return redirect()->back()->with('error', __('Oops! Something went wrong.'));
        }
    }

    public function invoicePayWithPayHere(Request $request, $invoice_id)
    {
        try {
            $invoice            = Invoice::find($invoice_id);
            $customer           = Customer::find($invoice->customer_id);
            $payment_setting    = Utility::getCompanyPaymentSetting($invoice->created_by);
            $setting            = Utility::settingsById($invoretainerice->created_by);
            $currency           = isset($setting['currency']) ? $setting['currency'] : '';
            $api_key            = isset($payment_setting['payhere_merchant_id']) ? $payment_setting['payhere_merchant_id'] : '';
            $get_amount         = $request->amount;
            $order_id           = strtoupper(str_replace('.', '', uniqid('', true)));

            $request->validate(['amount' => 'required|numeric|min:0']);

            $hash = strtoupper(
                md5(
                    $api_key .
                        $order_id .
                        number_format($get_amount, 2, '.', '') .
                        $currency .
                        strtoupper(md5(config('payhere.merchant_secret')))
                )
            );

            $data = [
                'first_name'    => $customer->name,
                'last_name'     => '',
                'email'         => $customer->email,
                'address'       => '',
                'city'          => '',
                'country'       => '',
                'order_id'      => $order_id,
                'items'         => 'Invoice Payment',
                'currency'      => $currency,
                'amount'        => $get_amount,
                'hash'          => $hash,
            ];

            return PayHere::checkOut()
                ->data($data)
                ->successUrl(route('invoice.payhere.status', ['success' => 1, 'invoice_id' => $invoice_id, 'amount' => $get_amount]))
                ->failUrl(route('invoice.payhere.status', ['success' => 0, 'invoice_id' => $invoice_id]))
                ->renderView();
        } catch (\Exception $e) {
            return redirect()->route('invoices.index')->with('error', $e->getMessage());
        }
    }

    public function invoiceGetPayHereStatus(Request $request)
    {
        if ($request->success == 1) {
            $info = PayHere::retrieve()
                ->orderId($request->order_id)
                ->submit();

            if ($info['data'][0]['order_id'] == $request->order_id) {
                if ($info['data'][0]['status'] == "RECEIVED") {
                    $invoice = Invoice::find($request->invoice_id);

                    $payment = new InvoicePayment();
                    $payment->invoice_id    = $invoice->id;
                    $payment->date          = date('Y-m-d');
                    $payment->amount        = $request->amount;
                    $payment->account_id    = 0;
                    $payment->payment_method = 0;
                    $payment->order_id      = $request->order_id;
                    $payment->currency      = $invoice->currency;
                    $payment->txn_id        = '';
                    $payment->payment_type  = __('PayHere');
                    $payment->receipt       = '';
                    $payment->reference     = '';
                    $payment->description   = 'Invoice Payment ' . Utility::invoiceNumberFormat($invoice->created_by, $invoice->invoice_id);
                    $payment->save();

                    if ($invoice->getDue() <= 0) {
                        $invoice->status = 4;
                    } elseif (($invoice->getDue() - $payment->amount) == 0) {
                        $invoice->status = 4;
                    } else {
                        $invoice->status = 3;
                    }
                    $invoice->save();

                    return redirect()->route('invoices.index')->with('success', __('Invoice payment has been received successfully.'));
                }
            }
        } else {
            return redirect()->route('invoices.index')->with('error', __('Invoice payment failed.'));
        }
    }

    public function retainerPayWithPayHere(Request $request, $retainer_id)
    {
        try {
            $retainer       = Retainer::find($retainer_id);
            $customer       = Customer::find($retainer->customer_id);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            $currency       = isset($payment_setting['currency']) ? $payment_setting['currency'] : '';
            $api_key        = isset($payment_setting['payhere_merchant_id']) ? $payment_setting['payhere_merchant_id'] : '';
            $get_amount     = $request->amount;
            $order_id       = strtoupper(str_replace('.', '', uniqid('', true)));
            $request->validate(['amount' => 'required|numeric|min:0']);

            $hash = strtoupper(
                md5(
                    $api_key .
                        $order_id .
                        number_format($get_amount, 2, '.', '') .
                        $currency .
                        strtoupper(md5(config('payhere.merchant_secret')))
                )
            );

            $data = [
                'first_name'    => $customer->name,
                'last_name'     => '',
                'email'         => $customer->email,
                'address'       => '',
                'city'          => '',
                'country'       => '',
                'order_id'      => $order_id,
                'items'         => 'Retainer Payment',
                'currency'      => $currency,
                'amount'        => $get_amount,
                'hash'          => $hash,
            ];

            return PayHere::checkOut()
                ->data($data)
                ->successUrl(route('retainer.payhere.status', ['success' => 1, $retainer_id, $get_amount]))
                ->failUrl(route('retainer.payhere.status', ['success' => 0,  $retainer_id]))
                ->renderView();
        } catch (\Exception $e) {
            return redirect()->route('retainers.index')->with('error', $e->getMessage());
        }
    }

    public function retainerGetPayHereStatus(Request $request, $retainer_id , $getAmount=0)
    {
        if ($request->success == 1) {
            $info = PayHere::retrieve()
                ->orderId($request->order_id)
                ->submit();

            if ($info['data'][0]['order_id'] == $request->order_id) {
                if ($info['data'][0]['status'] == "RECEIVED") {
                    $retainer = Retainer::find($request->retainer_id);

                    $payment = new RetainerPayment();
                    $payment->retainer_id       = $retainer->id;
                    $payment->date              = date('Y-m-d');
                    $payment->amount            = $request->amount;
                    $payment->account_id        = 0;
                    $payment->payment_method    = 0;
                    $payment->order_id          = $request->order_id;
                    $payment->currency          = $retainer->currency;
                    $payment->txn_id            = '';
                    $payment->payment_type      = __('PayHere');
                    $payment->receipt           = '';
                    $payment->reference         = '';
                    $payment->description       = 'Retainer Payment ' . Utility::retainerNumberFormat($retainer->created_by, $retainer->retainer_id);
                    $payment->save();
    
                    if ($retainer->getDue() <= 0) {
                        $retainer->status = 'close';
                    } elseif (($retainer->getDue() - $payment->amount) == 0) {
                        $retainer->status = 'close';
                    } else {
                        $retainer->status = 'active';
                    }
                    $retainer->save();
    
                    return redirect()->route('retainers.index')->with('success', __('Retainer payment has been received successfully.'));
                }
            }
        } else {
            return redirect()->route('retainers.index')->with('error', __('Retainer payment failed.'));
        }
    }
    
    
}
