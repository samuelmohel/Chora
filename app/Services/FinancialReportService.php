<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Get Trial Balance as of date or date range
     */
    public function getTrialBalance($startDate = null, $endDate = null)
    {
        $query = ChartOfAccount::with('types')->get();

        $trialBalance = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($query as $acc) {
            $itemQuery = JournalItem::where('account', $acc->id);

            if ($startDate && $endDate) {
                $itemQuery->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            } elseif ($endDate) {
                $itemQuery->whereHas('journalEntry', function($q) use ($endDate) {
                    $q->where('date', '<=', $endDate);
                });
            }

            $debitSum = $itemQuery->sum('debit');
            $creditSum = $itemQuery->sum('credit');

            $netBalance = $debitSum - $creditSum;

            // Determine if account is debit-normal or credit-normal
            // Type 1 (Assets) & 3 (Expenses) are Debit Normal
            // Type 2 (Liabilities), 4 (Income), 5 (Equity) are Credit Normal
            $debitBalance = 0;
            $creditBalance = 0;

            if (in_array($acc->type, [1, 3])) {
                if ($netBalance >= 0) {
                    $debitBalance = $netBalance;
                } else {
                    $creditBalance = abs($netBalance);
                }
            } else {
                if ($netBalance <= 0) {
                    $creditBalance = abs($netBalance);
                } else {
                    $debitBalance = $netBalance;
                }
            }

            if ($debitSum > 0 || $creditSum > 0 || $debitBalance > 0 || $creditBalance > 0) {
                $trialBalance[] = [
                    'account_id' => $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'type_name' => $acc->types?->name ?? 'Other',
                    'debit' => $debitBalance,
                    'credit' => $creditBalance,
                ];

                $totalDebit += $debitBalance;
                $totalCredit += $creditBalance;
            }
        }

        return [
            'accounts' => $trialBalance,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => (abs($totalDebit - $totalCredit) < 0.01),
        ];
    }

    /**
     * Get Income & Expenditure Account
     */
    public function getIncomeAndExpenditure($startDate = null, $endDate = null)
    {
        // Type 4 = Income, Type 3 = Expenses
        $incomeAccounts = ChartOfAccount::where('type', 4)->get();
        $expenseAccounts = ChartOfAccount::where('type', 3)->get();

        $incomeLines = [];
        $totalIncome = 0;

        foreach ($incomeAccounts as $acc) {
            $itemQuery = JournalItem::where('account', $acc->id);
            if ($startDate && $endDate) {
                $itemQuery->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            }

            // Income is credit normal: credit minus debit
            $amount = $itemQuery->sum('credit') - $itemQuery->sum('debit');
            if ($amount != 0) {
                $incomeLines[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'amount' => $amount,
                ];
                $totalIncome += $amount;
            }
        }

        $expenseLines = [];
        $totalExpenditure = 0;

        foreach ($expenseAccounts as $acc) {
            $itemQuery = JournalItem::where('account', $acc->id);
            if ($startDate && $endDate) {
                $itemQuery->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('date', [$startDate, $endDate]);
                });
            }

            // Expense is debit normal: debit minus credit
            $amount = $itemQuery->sum('debit') - $itemQuery->sum('credit');
            if ($amount != 0) {
                $expenseLines[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'amount' => $amount,
                ];
                $totalExpenditure += $amount;
            }
        }

        $surplusDeficit = $totalIncome - $totalExpenditure;

        return [
            'income_lines' => $incomeLines,
            'total_income' => $totalIncome,
            'expense_lines' => $expenseLines,
            'total_expenditure' => $totalExpenditure,
            'surplus_deficit' => $surplusDeficit,
        ];
    }

    /**
     * Get Statement of Financial Position (Balance Sheet)
     */
    public function getBalanceSheet($asOfDate = null)
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');

        // Assets (Type 1)
        $assetAccounts = ChartOfAccount::where('type', 1)->get();
        $assetLines = [];
        $totalAssets = 0;

        foreach ($assetAccounts as $acc) {
            $itemQuery = JournalItem::where('account', $acc->id)
                ->whereHas('journalEntry', function($q) use ($asOfDate) {
                    $q->where('date', '<=', $asOfDate);
                });

            // Asset is debit normal: debit minus credit
            $balance = $itemQuery->sum('debit') - $itemQuery->sum('credit');
            if ($balance != 0) {
                $assetLines[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'balance' => $balance,
                ];
                $totalAssets += $balance;
            }
        }

        // Liabilities (Type 2)
        $liabilityAccounts = ChartOfAccount::where('type', 2)->get();
        $liabilityLines = [];
        $totalLiabilities = 0;

        foreach ($liabilityAccounts as $acc) {
            $itemQuery = JournalItem::where('account', $acc->id)
                ->whereHas('journalEntry', function($q) use ($asOfDate) {
                    $q->where('date', '<=', $asOfDate);
                });

            // Liability is credit normal: credit minus debit
            $balance = $itemQuery->sum('credit') - $itemQuery->sum('debit');
            if ($balance != 0) {
                $liabilityLines[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'balance' => $balance,
                ];
                $totalLiabilities += $balance;
            }
        }

        // Current Year Surplus/Deficit from Income & Expenditure
        $ieReport = $this->getIncomeAndExpenditure(null, $asOfDate);
        $currentSurplus = $ieReport['surplus_deficit'];

        // Equity / Accumulated Fund (Type 5)
        $equityAccounts = ChartOfAccount::where('type', 5)->where('code', '!=', 3200)->get();
        $equityLines = [];
        $totalEquity = 0;

        foreach ($equityAccounts as $acc) {
            $itemQuery = JournalItem::where('account', $acc->id)
                ->whereHas('journalEntry', function($q) use ($asOfDate) {
                    $q->where('date', '<=', $asOfDate);
                });

            $balance = $itemQuery->sum('credit') - $itemQuery->sum('debit');
            $equityLines[] = [
                'code' => $acc->code,
                'name' => $acc->name,
                'balance' => $balance,
            ];
            $totalEquity += $balance;
        }

        // Add Current Surplus to Equity
        $equityLines[] = [
            'code' => 3200,
            'name' => 'Current-Year Surplus/Deficit',
            'balance' => $currentSurplus,
        ];
        $totalEquity += $currentSurplus;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
            'as_of_date' => $asOfDate,
            'asset_lines' => $assetLines,
            'total_assets' => $totalAssets,
            'liability_lines' => $liabilityLines,
            'total_liabilities' => $totalLiabilities,
            'equity_lines' => $equityLines,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => (abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01),
        ];
    }

    /**
     * Cash Movement / Receipts & Payments Report (Account #1010 Access Bank)
     */
    public function getCashMovementReport($startDate = null, $endDate = null)
    {
        $bankAcc = ChartOfAccount::where('code', 1010)->first();
        if (!$bankAcc) {
            return ['inflows' => [], 'outflows' => [], 'total_inflows' => 0, 'total_outflows' => 0, 'closing_balance' => 0];
        }

        $query = JournalItem::with('journalEntry')->where('account', $bankAcc->id);

        if ($startDate && $endDate) {
            $query->whereHas('journalEntry', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            });
        }

        $items = $query->get();

        $inflows = [];
        $outflows = [];
        $totalInflows = 0;
        $totalOutflows = 0;

        foreach ($items as $item) {
            if ($item->debit > 0) {
                $inflows[] = [
                    'date' => $item->journalEntry?->date,
                    'reference' => $item->journalEntry?->reference,
                    'description' => $item->description,
                    'amount' => $item->debit,
                ];
                $totalInflows += $item->debit;
            }
            if ($item->credit > 0) {
                $outflows[] = [
                    'date' => $item->journalEntry?->date,
                    'reference' => $item->journalEntry?->reference,
                    'description' => $item->description,
                    'amount' => $item->credit,
                ];
                $totalOutflows += $item->credit;
            }
        }

        $closingBalance = $totalInflows - $totalOutflows;

        return [
            'inflows' => $inflows,
            'total_inflows' => $totalInflows,
            'outflows' => $outflows,
            'total_outflows' => $totalOutflows,
            'closing_balance' => $closingBalance,
        ];
    }
}
