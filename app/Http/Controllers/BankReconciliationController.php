<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Services\AccountingPostingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BankReconciliationController extends Controller
{
    protected $postingService;

    public function __construct(AccountingPostingService $postingService)
    {
        $this->postingService = $postingService;
    }

    public function index()
    {
        $reconciliations = BankReconciliation::with(['preparedBy', 'reviewedBy'])
            ->orderBy('statement_date', 'desc')
            ->get();

        return view('bank_reconciliation.index', compact('reconciliations'));
    }

    public function create()
    {
        $bankAccounts = BankAccount::all();
        return view('bank_reconciliation.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_account_id' => 'required|integer',
            'statement_date' => 'required|date',
            'statement_ending_balance' => 'required|numeric',
            'book_ending_balance' => 'required|numeric',
        ]);

        $diff = $request->statement_ending_balance - $request->book_ending_balance;

        $reconciliation = BankReconciliation::create([
            'bank_account_id' => $request->bank_account_id,
            'statement_date' => $request->statement_date,
            'statement_ending_balance' => $request->statement_ending_balance,
            'book_ending_balance' => $request->book_ending_balance,
            'reconciled_balance' => $request->statement_ending_balance,
            'difference' => $diff,
            'status' => 'draft',
            'prepared_by' => Auth::user()->id,
        ]);

        return redirect()->route('bank-reconciliation.show', $reconciliation->id)
            ->with('success', __('Bank reconciliation draft created.'));
    }

    public function show($id)
    {
        $reconciliation = BankReconciliation::with(['items', 'preparedBy', 'reviewedBy'])->findOrFail($id);
        return view('bank_reconciliation.show', compact('reconciliation'));
    }

    public function signOff($id)
    {
        $reconciliation = BankReconciliation::findOrFail($id);
        if ($reconciliation->difference != 0) {
            return redirect()->back()->with('error', __('Cannot sign off reconciliation with unmatched difference. Difference must be 0.00'));
        }

        $reconciliation->update([
            'status' => 'completed',
            'reviewed_by' => Auth::user()->id,
            'signed_off_at' => now(),
        ]);

        return redirect()->back()->with('success', __('Bank reconciliation completed and signed off.'));
    }
}
