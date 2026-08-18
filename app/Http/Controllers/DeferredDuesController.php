<?php

namespace App\Http\Controllers;

use App\Models\DeferredDuesSchedule;
use App\Services\AccountingPostingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeferredDuesController extends Controller
{
    protected $postingService;

    public function __construct(AccountingPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    /**
     * Display Deferred Dues Income Recognition Dashboard
     */
    public function index(Request $request)
    {
        $year = $request->get('year', date('Y'));
        
        $schedules = DeferredDuesSchedule::with('customer')
            ->where('year', $year)
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        $monthlySummary = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthItems = $schedules->get($m, collect());
            $monthlySummary[$m] = [
                'month_name' => date('F', mktime(0, 0, 0, $m, 1)),
                'total_residents' => $monthItems->count(),
                'pending_amount' => $monthItems->where('status', 'pending')->sum('amount'),
                'recognized_amount' => $monthItems->where('status', 'recognized')->sum('amount'),
                'status' => $monthItems->where('status', 'pending')->count() > 0 ? 'Pending Release' : ($monthItems->count() > 0 ? 'Recognized' : 'Not Billed'),
            ];
        }

        return view('deferred_dues.index', compact('year', 'monthlySummary', 'schedules'));
    }

    /**
     * Recognize Monthly Dues Income for a given month
     */
    public function recognize(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|between:1,12',
        ]);

        try {
            $journal = $this->postingService->recognizeMonthlyDues(
                $request->year,
                $request->month,
                Auth::user()->id
            );

            $monthName = date('F Y', mktime(0, 0, 0, $request->month, 1, $request->year));
            return redirect()->back()->with('success', __("Dues income for {$monthName} successfully recognized and released to Income & Expenditure Account. Journal Ref: {$journal->journal_id}"));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
