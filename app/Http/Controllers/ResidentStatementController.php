<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeferredDuesSchedule;
use App\Models\JournalItem;

use Illuminate\Http\Request;

class ResidentStatementController extends Controller
{
    /**
     * Show Resident Statement by Customer ID
     */
    public function show($id, Request $request)
    {
        $customer = Customer::findOrFail($id);
        $year = $request->get('year', date('Y'));

        // Fetch all transactions for this resident
        $journalItems = JournalItem::with('journalEntry')
            ->whereHas('journalEntry', function($q) use ($customer) {
                $q->where('description', 'like', "%{$customer->name}%")
                  ->orWhere('reference', 'like', "%{$customer->customer_id}%")
                  ->orWhere('description', 'like', "%{$customer->id}%");
            })
            ->get()
            ->sortBy(function($item) {
                return $item->journalEntry?->date;
            });

        // Calculate Dues Summary
        $annualAmount = 96000.00;
        $monthlyAmount = 8000.00;
        $currentMonth = date('n');

        // Total payments received
        $receivableAcc = 1100;
        $totalPayments = JournalItem::whereHas('journalEntry', function($q) use ($customer) {
            $q->where('description', 'like', "%{$customer->name}%");
        })->sum('credit');

        $totalOutstanding = max(0, $annualAmount - $totalPayments);

        // Due to date (months elapsed so far)
        $dueToDate = $currentMonth * $monthlyAmount;
        $overdueBalance = max(0, $dueToDate - $totalPayments);
        $futureInstalments = max(0, $totalOutstanding - $overdueBalance);
        $advanceBalance = ($totalPayments > $annualAmount) ? ($totalPayments - $annualAmount) : 0;

        return view('resident_statement.show', compact(
            'customer',
            'year',
            'journalItems',
            'annualAmount',
            'totalPayments',
            'totalOutstanding',
            'overdueBalance',
            'futureInstalments',
            'advanceBalance'
        ));
    }
}
