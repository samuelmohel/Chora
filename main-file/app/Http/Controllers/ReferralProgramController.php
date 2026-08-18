<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReferralSetting;
use App\Models\ReferralTransaction;
use App\Models\User;
use App\Models\ReferralTransactionOrder;

class ReferralProgramController extends Controller
{
    public function index()
    {
        $setting        = ReferralSetting::where('created_by',\Auth::user()->id)->first();
        $payRequests    = ReferralTransactionOrder::where('status' , 1)->get();
        $transactions   = ReferralTransaction::get();
        
        return view('referral-program.index' , compact('setting' , 'payRequests' , 'transactions'));
    }

    public function store(Request $request)
    {

        $validator = \Validator::make(
            $request->all(), [

                               'percentage' => 'required',
                               'minimum_threshold_amount' => 'required',
                            //    'guideline' => 'required',
                           ]
        );

        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        if($request->has('is_enable') && $request->is_enable == 'on')
        {
            $is_enable = 1;
        }
        else
        {
            $is_enable = 0;
        }
        
        $setting = ReferralSetting::where('created_by' , \Auth::user()->id)->first();

        if($setting == null)
        {
            $setting = new ReferralSetting();
        }
        $setting->minimum_threshold_amount = $request->minimum_threshold_amount;
        $setting->percentage    = $request->percentage;
        $setting->is_enable     = $is_enable;
        $setting->guideline     = $request->guideline;
        $setting->created_by    = \Auth::user()->creatOrId();
        $setting->save();

        return redirect()->route('referral-program.index')->with('success', __('Referral Program Setting successfully Updated.'));

    }

    public function companyIndex()
    {
        $setting            = ReferralSetting::where('created_by',1)->first();
        $objUser            = \Auth::user();
        $transactions       = ReferralTransaction::where('referral_code' , $objUser->referral_code)->get();
        $transactionsOrder  = ReferralTransactionOrder::where('req_user_id',$objUser->id)->get();
        $paidAmount         = $transactionsOrder->where('status' , 2)->sum('req_amount');
        $paymentRequest     = ReferralTransactionOrder::where('status' , 1)->where('req_user_id',$objUser->id)->first();

        return view('referral-program.company' , compact('setting' , 'transactions' , 'paidAmount' , 'transactionsOrder' , 'paymentRequest'));
    }

    public function requestedAmountSent($id)
    {
        $id  = \Illuminate\Support\Facades\Crypt::decrypt($id);
        
        return view('referral-program.request_amount' , compact('id'));
    }

    public function requestCancel($id)
    {
        $transaction = ReferralTransactionOrder::where('req_user_id',$id)->orderBy('id','desc')->first();
        $transaction->status = 0;
        $transaction->req_user_id = \Auth::user()->id;
        $transaction->save();

        return redirect()->route('referral-program.company')->with('success', __('Request Cancel Successfully.'));
    }

    public function requestedAmountStore(Request $request , $id)
    {
        $order = new ReferralTransactionOrder();
        $order->req_amount  =  $request->request_amount;
        $order->req_user_id = \Auth::user()->id;
        $order->status      = 1;
        $order->date        = date('Y-m-d');
        $order->save();

        return redirect()->route('referral-program.company')->with('success', __('Request Send Successfully.'));
    }

    public function requestedAmount($id , $status)
    {
        $setting        = ReferralSetting::where('created_by',1)->first();
        $transaction    = ReferralTransactionOrder::find($id);
        $paidAmount     = ReferralTransactionOrder::where('req_user_id',$transaction->req_user_id)->where('status' , 2)->sum('req_amount');
        $user           = User::find($transaction->req_user_id);
        $netAmount      = $user->commissionAmount() - $paidAmount;

        if($transaction->req_amount >= $netAmount && $status == 1)
        {
            $transaction->status = 0;
            $transaction->save();

            return redirect()->route('referral-program.index')->with('error', __('This request cannot be accepted because it exceeds the commission amount.'));
        }
        elseif($transaction->req_amount <= $setting->minimum_threshold_amount && $status == 1)
        {
            $transaction->status = 0;
            $transaction->save();
            return redirect()->route('referral-program.index')->with('error', __('This request cannot be accepted because it less than the threshold amount.'));
        }
        elseif($status == 0)
        {
            $transaction->status = 0;
            $transaction->save();

            return redirect()->route('referral-program.index')->with('error', __('Request Rejected Successfully.'));
        }
        else
        {
            $transaction->status = 2;

            $transaction->save();
            return redirect()->route('referral-program.index')->with('success', __('Request Aceepted Successfully.'));
        }        
    }
}
