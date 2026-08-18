<?php

namespace App\Http\Controllers;

use App\Models\AnnualBilling;
use App\Models\Customer;
use App\Services\AccountingPostingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnualBillingController extends Controller
{
    protected $postingService;

    public function __construct(AccountingPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * Display Annual Billing Manager
     */
    public function index()
    {
        if (!Auth::user()->can('manage invoice') && Auth::user()->type !== 'company') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $billings = AnnualBilling::orderBy('year', 'desc')->get();
        $liableResidents = Customer::whereIn('occupancy_status', ['occupied', 'vacant_owner_liable'])
            ->where('billing_status', 'active')
            ->count();

        $currentYear = date('Y');
        $isCurrentYearBilled = AnnualBilling::where('year', $currentYear)->where('status', 'posted')->exists();

        return view('annual_billing.index', compact('billings', 'liableResidents', 'currentYear', 'isCurrentYearBilled'));
    }

    /**
     * Post Annual Billing Batch
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('create invoice') && Auth::user()->type !== 'company') {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
            'annual_amount' => 'required|numeric|min:1',
            'monthly_amount' => 'required|numeric|min:1',
        ]);

        try {
            $annualBilling = $this->postingService->postAnnualBilling(
                $request->year,
                $request->annual_amount,
                $request->monthly_amount,
                Auth::user()->id
            );

            return redirect()->back()->with('success', __("Annual dues billing for year {$annualBilling->year} posted successfully for {$annualBilling->total_liable_residents} residents. Total Billed: ₦" . number_format($annualBilling->total_billed_amount, 2)));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
