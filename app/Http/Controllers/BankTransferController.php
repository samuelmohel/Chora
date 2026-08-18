<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\UserCoupon;
use App\Models\Plan;
use App\Models\Utility;
use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\BankTransfer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Retainer;
use App\Models\RetainerPayment;

class BankTransferController extends Controller
{
    protected $invoiceData;

    public function index()
    {
        //
    }

    public function planPayWithbank(Request $request)
    {

        $planID = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);

        $plan   = Plan::find($planID);
        $price = $plan->price;

        $user = \Auth::user();
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
        $admin = Utility::getAdminPaymentSetting();

        $request->validate(
            [
                'receipt' => 'required',
            ]
        );


        $dir = storage_path() . '/uploads/bank_receipt/';
        if (!is_dir($dir)) {
            \File::makeDirectory($dir, $mode = 0777, true, true);
        }
        $file_path = $request->receipt->getClientOriginalName();
        $file = $request->file('receipt');
        $file->move($dir, $file_path);


        $coupon_id = 0;
        if (!empty($request->coupon)) {

            $coupons = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();

            if (!empty($coupons)) {

                $usedCoupun     = $coupons->used_coupon();
                $discount_value = ($plan->price / 100) * $coupons->discount;
                $price          = $plan->price - $discount_value;
                if ($coupons->limit == $usedCoupun) {
                    return redirect()->back()->with('error', __('This coupon code has expired.'));
                }
                $coupon_id = $coupons->id;
            } else {
                return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
            }
        }

        $coupons = Coupon::find($coupon_id);

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

        if ($price >= 0) {

            Order::create(
                [
                    'order_id'        => $orderID,
                    'name'            => $request->name,
                    'email'           => null,
                    'card_number'     => null,
                    'card_exp_month'  => null,
                    'card_exp_year'   => null,
                    'plan_name'       => $plan->name,
                    'plan_id'         => $plan->id,
                    'price'           => $price == null ? 0 : $price,
                    'price_currency'  => $admin['currency'] ? $admin['currency'] : 'USD',
                    'txn_id'          => '',
                    'payment_type'    => 'Bank Transfer',
                    'payment_status'  => 'pending',
                    'receipt'         => !empty($file_path) ? $file_path : '',
                    'user_id'         => $user->id,
                ]
            );
            $assignPlan = $user->assignPlan($plan->id);
            if ($assignPlan['is_success']) {
                return redirect()->route('plans.index')->with('success', __('Plan payment request send successfully!') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
            } else {
                return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
            }

            return redirect()->route('plans.index')->with('success', __('Plan payment request send successfully!') . ((isset($result) && $result != 1) ? '<br> <span class="text-danger">' . $result . '</span>' : ''));
        } else {
            return redirect()->route('plans.index')->with('error', 'Something went wrong.');
        }
    }


    public function show($id)
    {
        $details = Order::find($id);
        $settings = Utility::getAdminPaymentSetting();
        $bank_detail = $settings['bank_detail'];
        return view('banktarnsfer.view', compact('details', 'bank_detail'));
    }


    public function destroy($id)
    {
        $order = Order::where('id', $id)->delete();

        return redirect()->route('order.index')->with(
            'success',
            'Order successfully deleted.'
        );
    }


    public function  ChangeStatus($id, $response)
    {
        $order = Order::find($id);
        if ($response == 1) {
            $order->payment_status = 'approval';
            $order->save();
            $user = User::find($order->user_id);
            $user->plan = $order->plan_id;

            $assignPlan = $user->assignPlan($order->plan_id);
            if (!empty($user->payment_subscription_id) && $user->payment_subscription_id != '') {
                try {
                    $user->cancel_subscription($user->id);
                } catch (\Exception $exception) {
                    \Log::debug($exception->getMessage());
                }
            }
            return redirect()->back()->with('success', __('Payment status successfully updated.'));
        } else {
            $order->payment_status = 'rejected';
            $order->save();
            return redirect()->back()->with('error', __('Payment status successfully updated.'));
        }
    }

    public function invoicePayWithbank(Request $request, $invoice_id)
    {
        $invoice                 = Invoice::find($invoice_id);

        $this->invoiceData       = $invoice;

        $get_amount = $request->amount;

        if (\Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $invoice->created_by)->first();
            $settings = Utility::settingById($invoice->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($invoice->created_by);
            //            $this->non_auth_setApiContext($invoice->created_by);
            $objUser = $user;
        }

        $request->validate(
            [
                'receipt' => 'required',
            ]
        );

        // $image_size = $request->file('receipt')->getSize();
        // $result = Utility::updateStorageLimit($objUser->creatorId(), $image_size);
        // $total_storage = $user->storage_limit + $image_size;

        // if ($result == 1) {
        $dir = storage_path() . '/uploads/bank_receipt/';
        if (!is_dir($dir)) {
            \File::makeDirectory($dir, $mode = 0777, true, true);
        }
        $file_path = $request->receipt->getClientOriginalName();
        $file = $request->file('receipt');
        $file->move($dir, $file_path);
        // }

        // dd($user->storage_limit <= $total_storage && $user->storage_limit != -1);

        // if ($user->storage_limit <= $total_storage && $user->storage_limit != -1) {
        //     $user->storage_limit = $total_storage;
        // } else {
        //     $error = __('Your plan storage limit is over , so you can not see customer uploaded payment receipt.');
        //     return $error;
        // }

        try {
            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

            $payments = BankTransfer::create(
                [
                    'invoice_id' => $invoice->id,
                    'order_id' => $order_id,
                    'amount' => $get_amount,
                    'status' => 'pending',
                    'receipt' => !empty($file_path) ? $file_path : '',
                    'created_by' => $invoice->created_by,
                    'type' => __('invoice'),
                ]
            );

            if (\Auth::check()) {
                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        } catch (\Exception $e) {
            if (\Auth::check()) {
                return redirect()->route('invoice.show', \Crypt::encrypt($invoice->id))->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been complted.'));
            }
        }
    }

    public function invoicpaymenteshow($id)
    {
        $BankTransfer = BankTransfer::find($id);

        $details  = Invoice::find($BankTransfer->invoice_id);
        $user_id        = $BankTransfer->created_by;

        $settings = Utility::getCompanyPaymentSetting($user_id);
        $bank_detail = $settings['bank_detail'];

        return view('invoice.payment_view', compact('details', 'BankTransfer', 'bank_detail'));
    }

    public function invoicechangestatus($id, $response)
    {

        $BankTransfer = BankTransfer::find($id);

        $order_id = $BankTransfer->order_id;

        $details  = Invoice::find($BankTransfer->invoice_id);

        if (Auth::check()) {
            $settings  = DB::table('settings')->where('created_by', '=', $BankTransfer->created_by)->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $details->created_by)->first();
            $settings = Utility::settingById($details->created_by);
        }

        $setting = Utility::settingsById($details->created_by);

        if ($response == 'Approval') {

            $BankTransfer->status = 'Approved';
            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

            $payments = InvoicePayment::create(
                [
                    'invoice_id' => $details->id,
                    'date' => date('Y-m-d'),
                    'amount' => $BankTransfer->amount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => $setting['site_currency'],
                    'txn_id' => '',
                    'payment_type' => __('Bank Transfer'),
                    'receipt' => $BankTransfer->receipt,
                    'reference' => '',
                    'description' => 'Invoice ' . Utility::invoiceNumberFormat($settings, $details->invoice_id),
                ]
            );
            
            $BankTransfer->delete();

            return redirect()->back()->with('success', __('Payment status successfully updated.'));
        } else {
            $BankTransfer->status           = 'Rejected';
        }
        $BankTransfer->save();

        return redirect()->back()->with('success', __('Invoice payment request send successfully.'));
    }


    public function invoicedestroy($id)
    {
        $invoice = BankTransfer::where('id', $id)->delete();

        return redirect()->back()->with(
            'success',
            'Bank transfer successfully deleted.'
        );
    }

    public function retainerPayWithbank(Request $request, $retainer_id)
    {
        $retainer = Retainer::find($retainer_id);

        // $this->invoiceData       = $invoice;

        $get_amount = $request->amount;


        if (\Auth::check()) {
            $settings = DB::table('settings')->where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('value', 'name');
            $objUser     = \Auth::user();
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            //            $this->setApiContext();
        } else {
            $user = User::where('id', $retainer->created_by)->first();
            $settings = Utility::settingById($retainer->created_by);
            $payment_setting = Utility::getCompanyPaymentSetting($retainer->created_by);
            //            $this->non_auth_setApiContext($invoice->created_by);
            $objUser = $user;
        }

        $request->validate(
            [
                'receipt' => 'required',
            ]
        );

        // $image_size = $request->file('receipt')->getSize();

        // $result = Utility::updateStorageLimit($objUser->creatorId(), $image_size);

        // if ($result == 1) {
        $dir = storage_path() . '/uploads/bank_receipt/';
        if (!is_dir($dir)) {
            \File::makeDirectory($dir, $mode = 0777, true, true);
        }
        $file_path = $request->receipt->getClientOriginalName();
        $file = $request->file('receipt');
        $file->move($dir, $file_path);
        // }



        try {
            $order_id = strtoupper(str_replace('.', '', uniqid('', true)));

            $payments = BankTransfer::create(
                [
                    'retainer_id' => $retainer->id,
                    'order_id' => $order_id,
                    'amount' => $get_amount,
                    'status' => 'pending',
                    'receipt' => !empty($file_path) ? $file_path : '',
                    'created_by' => $retainer->created_by,
                    'type' => __('retainer'),
                ]
            );


            if (\Auth::check()) {
                return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('success', __('Payment successfully added.'));
            } else {
                return redirect()->back()->with('success', __(' Payment successfully added.'));
            }
        } catch (\Exception $e) {
            if (\Auth::check()) {
                return redirect()->route('retainer.show', \Crypt::encrypt($retainer->id))->with('error', __('Transaction has been failed.'));
            } else {
                return redirect()->back()->with('success', __('Transaction has been complted.'));
            }
        }
    }


    public function retainerpaymenteshow($id)
    {
        $BankTransfer = BankTransfer::find($id);

        $details  = Retainer::find($BankTransfer->retainer_id);

        $user_id        = $BankTransfer->created_by;

        $settings = Utility::getCompanyPaymentSetting($user_id);
        $bank_detail = $settings['bank_detail'];

        return view('retainer.retainer_view', compact('BankTransfer', 'details', 'bank_detail'));
    }

    public function retainerchangestatus($id, $response)
    {

        $BankTransfer = BankTransfer::find($id);

        $order_id = $BankTransfer->order_id;

        $details  = Retainer::find($BankTransfer->retainer_id);

        if (Auth::check()) {
            $settings  = DB::table('settings')->where('created_by', '=', $BankTransfer->created_by)->get()->pluck('value', 'name');
        } else {
            $user = User::where('id', $details->created_by)->first();
            $settings = Utility::settingById($details->created_by);
        }

        $setting = Utility::settingsById($details->created_by);

        if ($response == 'Approval') {

            $BankTransfer->status = 'Approved';

            $payments = RetainerPayment::create(
                [
                    'retainer_id' => $details->id,
                    'date' => date('Y-m-d'),
                    'amount' => $BankTransfer->amount,
                    'account_id' => 0,
                    'payment_method' => 0,
                    'order_id' => $order_id,
                    'currency' => $setting['site_currency'],
                    'txn_id' => '',
                    'payment_type' => __('Bank Transfer'),
                    'receipt' => $BankTransfer->receipt,
                    'reference' => '',
                    'description' => 'Retainer ' . Utility::retainerNumberFormat($settings, $details->retainer_id),
                ]
            );

            $BankTransfer->delete();

            return redirect()->back()->with('success', __('Payment status successfully updated.'));
        } else {
            $BankTransfer->status           = 'Rejected';
        }
        $BankTransfer->save();

        return redirect()->back()->with('success', __('Invoice payment request send successfully.'));
    }

    public function retainerdestroy($id)
    {
        $invoice = BankTransfer::where('id', $id)->delete();
        return redirect()->back()->with(
            'success',
            'Bank transfer successfully deleted.'
        );
    }
}
