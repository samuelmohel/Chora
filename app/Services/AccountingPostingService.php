<?php

namespace App\Services;

use App\Models\AnnualBilling;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DeferredDuesSchedule;
use App\Models\DogControlFine;
use App\Models\EkedcTransaction;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\ReconnectionFee;
use App\Models\RestrictedProgrammeFund;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    /**
     * Helper to get account ID by code
     */
    protected function getAccountIdByCode($code)
    {
        $acc = ChartOfAccount::where('code', $code)->first();
        if (!$acc) {
            throw new Exception("Chart of Account with code {$code} not found. Please run ChoraChartOfAccountSeeder.");
        }
        return $acc->id;
    }

    /**
     * Create a balanced Journal Entry
     */
    public function createBalancedJournal(array $data, array $lines, $creatorId = 1)
    {
        return DB::transaction(function () use ($data, $lines, $creatorId) {
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $totalDebit += $line['debit'] ?? 0;
                $totalCredit += $line['credit'] ?? 0;
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new Exception("Out of balance journal entry! Debits ({$totalDebit}) must equal Credits ({$totalCredit}).");
            }

            $journalNumber = 'JOU-' . sprintf("%05d", (JournalEntry::max('id') + 1));

            $journal = JournalEntry::create([
                'journal_id' => $journalNumber,
                'date' => $data['date'] ?? date('Y-m-d'),
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? '',
                'created_by' => $creatorId,
                'approval_status' => $data['approval_status'] ?? 'approved',
                'approved_by' => $data['approved_by'] ?? $creatorId,
            ]);

            foreach ($lines as $line) {
                JournalItem::create([
                    'journal' => $journal->id,
                    'account' => $line['account_id'],
                    'description' => $line['description'] ?? $data['description'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $journal;
        });
    }

    /**
     * 1. Post Annual Dues Billing (₦96,000 per liable resident)
     * Debit: 1100 Resident Dues Receivable Control ₦96,000
     * Credit: 2100 Deferred Association Dues ₦96,000
     */
    public function postAnnualBilling($year = 2026, $annualAmount = 96000.00, $monthlyAmount = 8000.00, $creatorId = 1)
    {
        return DB::transaction(function () use ($year, $annualAmount, $monthlyAmount, $creatorId) {
            $existing = AnnualBilling::where('year', $year)->first();
            if ($existing && $existing->status === 'posted') {
                throw new Exception("Annual billing for year {$year} has already been posted.");
            }

            $liableCustomers = Customer::whereIn('occupancy_status', ['occupied', 'vacant_owner_liable'])
                ->where('billing_status', 'active')
                ->get();

            if ($liableCustomers->isEmpty()) {
                throw new Exception("No liable resident accounts found for annual billing.");
            }

            $receivableAccId = $this->getAccountIdByCode(1100);
            $deferredAccId = $this->getAccountIdByCode(2100);

            $totalBilled = count($liableCustomers) * $annualAmount;

            $annualBilling = AnnualBilling::updateOrCreate(
                ['year' => $year],
                [
                    'annual_amount' => $annualAmount,
                    'monthly_amount' => $monthlyAmount,
                    'total_liable_residents' => count($liableCustomers),
                    'total_billed_amount' => $totalBilled,
                    'status' => 'posted',
                    'approved_by' => $creatorId,
                    'posted_at' => now(),
                    'created_by' => $creatorId,
                ]
            );

            // Create Journal Entry for whole batch
            $journalLines = [
                [
                    'account_id' => $receivableAccId,
                    'debit' => $totalBilled,
                    'credit' => 0,
                    'description' => "Annual Dues Billing {$year} - {$annualBilling->total_liable_residents} residents @ ₦{$annualAmount}"
                ],
                [
                    'account_id' => $deferredAccId,
                    'debit' => 0,
                    'credit' => $totalBilled,
                    'description' => "Deferred Dues Liability {$year} - {$annualBilling->total_liable_residents} residents @ ₦{$annualAmount}"
                ]
            ];

            $journal = $this->createBalancedJournal([
                'date' => "{$year}-01-01",
                'reference' => "ANN-BILL-{$year}",
                'description' => "Annual Dues Billing for Year {$year}",
            ], $journalLines, $creatorId);

            // Generate 12-month schedule per resident
            foreach ($liableCustomers as $customer) {
                for ($m = 1; $m <= 12; $m++) {
                    DeferredDuesSchedule::updateOrCreate(
                        [
                            'annual_billing_id' => $annualBilling->id,
                            'customer_id' => $customer->id,
                            'year' => $year,
                            'month' => $m,
                        ],
                        [
                            'amount' => $monthlyAmount,
                            'status' => 'pending',
                        ]
                    );
                }
            }

            return $annualBilling;
        });
    }

    /**
     * 2. Recognize Monthly Dues Income (₦8,000 per active account)
     * Debit: 2100 Deferred Association Dues ₦8,000
     * Credit: 4010 Association Dues Income ₦8,000
     */
    public function recognizeMonthlyDues($year, $month, $creatorId = 1)
    {
        return DB::transaction(function () use ($year, $month, $creatorId) {
            $schedules = DeferredDuesSchedule::where('year', $year)
                ->where('month', $month)
                ->where('status', 'pending')
                ->get();

            if ($schedules->isEmpty()) {
                throw new Exception("No pending deferred dues schedule found for {$year}-" . sprintf("%02d", $month));
            }

            $totalRecognized = $schedules->sum('amount');
            $deferredAccId = $this->getAccountIdByCode(2100);
            $incomeAccId = $this->getAccountIdByCode(4010);

            $monthName = Carbon::create($year, $month, 1)->format('F Y');

            $lines = [
                [
                    'account_id' => $deferredAccId,
                    'debit' => $totalRecognized,
                    'credit' => 0,
                    'description' => "Monthly Dues Income Recognition - {$monthName} ({$schedules->count()} residents)"
                ],
                [
                    'account_id' => $incomeAccId,
                    'debit' => 0,
                    'credit' => $totalRecognized,
                    'description' => "Association Dues Income - {$monthName}"
                ]
            ];

            $lastDay = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

            $journal = $this->createBalancedJournal([
                'date' => $lastDay,
                'reference' => "DUES-REC-{$year}-" . sprintf("%02d", $month),
                'description' => "Monthly Dues Income Release - {$monthName}",
            ], $lines, $creatorId);

            foreach ($schedules as $sch) {
                $sch->update([
                    'status' => 'recognized',
                    'journal_entry_id' => $journal->id,
                    'recognized_at' => now(),
                ]);
            }

            return $journal;
        });
    }

    /**
     * 3. Post Resident Payment Receipt
     * Debit: 1010 Access Bank
     * Credit: 1100 Resident Dues Receivable (up to balance)
     * Credit: 2110 Resident Advances/Credits (excess)
     */
    public function postResidentReceipt($customerId, $amount, $paymentRef, $date = null, $creatorId = 1)
    {
        return DB::transaction(function () use ($customerId, $amount, $paymentRef, $date, $creatorId) {
            $customer = Customer::findOrFail($customerId);
            $date = $date ?? date('Y-m-d');

            $bankAccId = $this->getAccountIdByCode(1010);
            $receivableAccId = $this->getAccountIdByCode(1100);
            $advanceAccId = $this->getAccountIdByCode(2110);

            // Compute current resident receivable balance
            $currentReceivable = $this->getResidentReceivableBalance($customerId);

            $lines = [];
            $lines[] = [
                'account_id' => $bankAccId,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Resident Receipt {$paymentRef} - {$customer->name} ({$customer->property_account_id})"
            ];

            if ($currentReceivable > 0) {
                $receivablePay = min($amount, $currentReceivable);
                $excessPay = $amount - $receivablePay;

                $lines[] = [
                    'account_id' => $receivableAccId,
                    'debit' => 0,
                    'credit' => $receivablePay,
                    'description' => "Settlement of Resident Receivable - {$customer->name}"
                ];

                if ($excessPay > 0) {
                    $lines[] = [
                        'account_id' => $advanceAccId,
                        'debit' => 0,
                        'credit' => $excessPay,
                        'description' => "Resident Overpayment / Advance - {$customer->name}"
                    ];
                }
            } else {
                // Whole payment is advance
                $lines[] = [
                    'account_id' => $advanceAccId,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => "Resident Pre-billing Advance - {$customer->name}"
                ];
            }

            return $this->createBalancedJournal([
                'date' => $date,
                'reference' => $paymentRef,
                'description' => "Dues Receipt from {$customer->name}",
            ], $lines, $creatorId);
        });
    }

    /**
     * Calculate resident's current receivable balance
     */
    public function getResidentReceivableBalance($customerId)
    {
        // Calculate total debits minus credits for this resident in Account 1100
        $receivableAccId = $this->getAccountIdByCode(1100);
        $totalDebits = JournalItem::where('account', $receivableAccId)
            ->whereHas('journalEntry', function($q) use ($customerId) {
                $q->where('description', 'like', "%{$customerId}%")
                  ->orWhere('reference', 'like', "%{$customerId}%");
            })->sum('debit');

        $totalCredits = JournalItem::where('account', $receivableAccId)
            ->whereHas('journalEntry', function($q) use ($customerId) {
                $q->where('description', 'like', "%{$customerId}%")
                  ->orWhere('reference', 'like', "%{$customerId}%");
            })->sum('credit');

        // Fallback default annual bill if not itemized individually
        $annualBilling = AnnualBilling::latest()->first();
        $annualAmount = $annualBilling ? $annualBilling->annual_amount : 96000.00;

        return max(0, $annualAmount - $totalCredits);
    }

    /**
     * 4. Post Unidentified Access Bank Credit
     * Debit: 1010 Access Bank
     * Credit: 2120 Unidentified Receipts Suspense
     */
    public function postUnidentifiedReceipt($amount, $bankRef, $narration, $date = null, $creatorId = 1)
    {
        $bankAccId = $this->getAccountIdByCode(1010);
        $suspenseAccId = $this->getAccountIdByCode(2120);

        $lines = [
            [
                'account_id' => $bankAccId,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Unidentified Bank Credit {$bankRef} - {$narration}"
            ],
            [
                'account_id' => $suspenseAccId,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Unidentified Receipts Suspense {$bankRef}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $bankRef,
            'description' => "Unidentified Bank Receipt - {$narration}",
        ], $lines, $creatorId);
    }

    /**
     * 5. Post Voluntary Donations
     * Unrestricted: Debit 1010 Bank / Credit 4020 Donation Income
     * Restricted: Debit 1010 Bank / Credit 2200 Restricted Programme Fund
     */
    public function postDonation($donorName, $amount, $isRestricted = false, $programmeCode = null, $date = null, $creatorId = 1)
    {
        $bankAccId = $this->getAccountIdByCode(1010);

        if ($isRestricted) {
            $creditAccId = $this->getAccountIdByCode(2200);
            $desc = "Restricted Donation for {$programmeCode} from {$donorName}";
        } else {
            $creditAccId = $this->getAccountIdByCode(4020);
            $desc = "Unrestricted Donation from {$donorName}";
        }

        $lines = [
            [
                'account_id' => $bankAccId,
                'debit' => $amount,
                'credit' => 0,
                'description' => $desc
            ],
            [
                'account_id' => $creditAccId,
                'debit' => 0,
                'credit' => $amount,
                'description' => $desc
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => 'DON-' . time(),
            'description' => $desc,
        ], $lines, $creatorId);
    }

    /**
     * 6. EKEDC Electricity Token Agent Model
     * Collection: Dr 1010 Bank / Cr 2020 EKEDC Clearing
     * Settlement: Dr 2020 EKEDC Clearing / Cr 1010 Bank & Cr 4030 Token Commission Income
     */
    public function postEkedcCollection($meterNumber, $ref, $collectionAmount, $customerId = null, $date = null, $creatorId = 1)
    {
        $bankAccId = $this->getAccountIdByCode(1010);
        $ekedcAccId = $this->getAccountIdByCode(2020);

        $lines = [
            [
                'account_id' => $bankAccId,
                'debit' => $collectionAmount,
                'credit' => 0,
                'description' => "EKEDC Token Collection Meter {$meterNumber} Ref {$ref}"
            ],
            [
                'account_id' => $ekedcAccId,
                'debit' => 0,
                'credit' => $collectionAmount,
                'description' => "EKEDC Clearing Liability Meter {$meterNumber}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $ref,
            'description' => "EKEDC Token Collection Meter {$meterNumber}",
        ], $lines, $creatorId);
    }

    public function postEkedcSettlement($totalClearingAmount, $remittanceAmount, $commissionAmount, $ref, $date = null, $creatorId = 1)
    {
        $bankAccId = $this->getAccountIdByCode(1010);
        $ekedcAccId = $this->getAccountIdByCode(2020);
        $commissionAccId = $this->getAccountIdByCode(4030);

        $lines = [
            [
                'account_id' => $ekedcAccId,
                'debit' => $totalClearingAmount,
                'credit' => 0,
                'description' => "Clear EKEDC Liability Ref {$ref}"
            ],
            [
                'account_id' => $bankAccId,
                'debit' => 0,
                'credit' => $remittanceAmount,
                'description' => "Remittance to EKEDC Ref {$ref}"
            ],
            [
                'account_id' => $commissionAccId,
                'debit' => 0,
                'credit' => $commissionAmount,
                'description' => "Token Vending Commission Income Ref {$ref}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $ref,
            'description' => "EKEDC Remittance and Commission Settlement",
        ], $lines, $creatorId);
    }

    /**
     * 7. Dog-Control Fines
     * Raise Fine: Dr 1110 Other Resident Charges Receivable / Cr 4040 Dog Fine Income
     * Collect Fine: Dr 1010 Access Bank / Cr 1110 Other Resident Charges Receivable
     */
    public function postDogFine($customerId, $incidentRef, $fineAmount = 20000.00, $date = null, $creatorId = 1)
    {
        $otherReceivableAccId = $this->getAccountIdByCode(1110);
        $dogFineIncomeAccId = $this->getAccountIdByCode(4040);
        $customer = Customer::findOrFail($customerId);

        $lines = [
            [
                'account_id' => $otherReceivableAccId,
                'debit' => $fineAmount,
                'credit' => 0,
                'description' => "Dog-Control Fine Billed - {$customer->name} Ref {$incidentRef}"
            ],
            [
                'account_id' => $dogFineIncomeAccId,
                'debit' => 0,
                'credit' => $fineAmount,
                'description' => "Dog-Control Fine Income Ref {$incidentRef}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $incidentRef,
            'description' => "Approved Dog-Control Fine - {$customer->name}",
        ], $lines, $creatorId);
    }

    /**
     * 8. Reconnection Fees
     * Dr 1110 Other Resident Charges / Cr 4050 Reconnection Fee Income
     * Receipt: Dr 1010 Bank / Cr 1110 Other Resident Charges
     */
    public function postReconnectionFee($customerId, $ref, $feeAmount = 10000.00, $date = null, $creatorId = 1)
    {
        $bankAccId = $this->getAccountIdByCode(1010);
        $reconnectionIncomeAccId = $this->getAccountIdByCode(4050);
        $customer = Customer::findOrFail($customerId);

        $lines = [
            [
                'account_id' => $bankAccId,
                'debit' => $feeAmount,
                'credit' => 0,
                'description' => "Reconnection Fee Payment - {$customer->name}"
            ],
            [
                'account_id' => $reconnectionIncomeAccId,
                'debit' => 0,
                'credit' => $feeAmount,
                'description' => "Reconnection Fee Income - {$customer->name}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $ref,
            'description' => "Reconnection Fee Received - {$customer->name}",
        ], $lines, $creatorId);
    }

    /**
     * 9. Estate Expenditure & Asset Capitalization
     */
    public function postEstateExpense($expenseAccountCode, $amount, $description, $ref, $date = null, $creatorId = 1)
    {
        $expenseAccId = $this->getAccountIdByCode($expenseAccountCode);
        $bankAccId = $this->getAccountIdByCode(1010);

        $lines = [
            [
                'account_id' => $expenseAccId,
                'debit' => $amount,
                'credit' => 0,
                'description' => $description
            ],
            [
                'account_id' => $bankAccId,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Payment from Access Bank - {$description}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $ref,
            'description' => $description,
        ], $lines, $creatorId);
    }

    public function postTransformerAssetPurchase($amount, $ref, $description = 'Qualifying Transformer Asset Acquisition', $date = null, $creatorId = 1)
    {
        $assetAccId = $this->getAccountIdByCode(1500);
        $bankAccId = $this->getAccountIdByCode(1010);

        $lines = [
            [
                'account_id' => $assetAccId,
                'debit' => $amount,
                'credit' => 0,
                'description' => $description
            ],
            [
                'account_id' => $bankAccId,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Bank Outflow - {$description}"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $ref,
            'description' => $description,
        ], $lines, $creatorId);
    }

    public function postMonthlyDepreciation($monthlyAmount = 40000.00, $ref = 'DEP-TRANSFORMER', $date = null, $creatorId = 1)
    {
        $depExpenseAccId = $this->getAccountIdByCode(5070);
        $accumDepAccId = $this->getAccountIdByCode(1590);

        $lines = [
            [
                'account_id' => $depExpenseAccId,
                'debit' => $monthlyAmount,
                'credit' => 0,
                'description' => "Monthly Depreciation Charge - Transformer Infrastructure"
            ],
            [
                'account_id' => $accumDepAccId,
                'debit' => 0,
                'credit' => $monthlyAmount,
                'description' => "Accumulated Depreciation Credit - Transformer Infrastructure"
            ]
        ];

        return $this->createBalancedJournal([
            'date' => $date ?? date('Y-m-d'),
            'reference' => $ref,
            'description' => "Monthly Depreciation Entry",
        ], $lines, $creatorId);
    }

    /**
     * 10. Reversal Control (Immutable Audit Rule)
     */
    public function reverseJournalEntry($journalEntryId, $reason, $creatorId = 1)
    {
        return DB::transaction(function () use ($journalEntryId, $reason, $creatorId) {
            $original = JournalEntry::with('items')->findOrFail($journalEntryId);

            if ($original->is_reversed) {
                throw new Exception("Journal entry {$original->journal_id} has already been reversed.");
            }

            $reversalLines = [];
            foreach ($original->items as $item) {
                $reversalLines[] = [
                    'account_id' => $item->account,
                    'debit' => $item->credit, // Invert credit to debit
                    'credit' => $item->debit, // Invert debit to credit
                    'description' => "REVERSAL of {$original->journal_id}: " . $item->description
                ];
            }

            $reversalJournal = $this->createBalancedJournal([
                'date' => date('Y-m-d'),
                'reference' => "REV-" . $original->journal_id,
                'description' => "REVERSAL ENTRY for {$original->journal_id} - Reason: {$reason}",
            ], $reversalLines, $creatorId);

            $original->update([
                'is_reversed' => true,
                'reversed_by_id' => $reversalJournal->id,
                'reversal_reason' => $reason,
            ]);

            $reversalJournal->update([
                'reverses_id' => $original->id,
            ]);

            return $reversalJournal;
        });
    }
}
