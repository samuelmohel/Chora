<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\User;
use App\Services\AccountingPostingService;
use App\Services\FinancialReportService;
use Database\Seeders\ChoraChartOfAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $postingService;
    protected $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed CHORA Chart of Accounts
        $this->seed(ChoraChartOfAccountSeeder::class);

        $this->postingService = new AccountingPostingService();
        $this->reportService = new FinancialReportService();
    }

    /**
     * Test 1: Annual Dues Batch Billing (347 residents * ₦96,000 = ₦33,312,000)
     */
    public function test_annual_dues_batch_billing()
    {
        // Create 347 liable residents
        Customer::factory()->count(347)->create([
            'occupancy_status' => 'occupied',
            'billing_status' => 'active',
        ]);

        $annualBilling = $this->postingService->postAnnualBilling(2026, 96000.00, 8000.00, 1);

        $this->assertEquals(347, $annualBilling->total_liable_residents);
        $this->assertEquals(33312000.00, $annualBilling->total_billed_amount);

        // Check Trial Balance
        $tb = $this->reportService->getTrialBalance();
        $this->assertTrue($tb['is_balanced']);
        $this->assertEquals(33312000.00, $tb['total_debit']);
        $this->assertEquals(33312000.00, $tb['total_credit']);
    }

    /**
     * Test 2: Monthly Income Recognition Release (January ₦2,776,000)
     */
    public function test_monthly_dues_income_recognition()
    {
        Customer::factory()->count(347)->create([
            'occupancy_status' => 'occupied',
            'billing_status' => 'active',
        ]);

        $this->postingService->postAnnualBilling(2026, 96000.00, 8000.00, 1);
        $journal = $this->postingService->recognizeMonthlyDues(2026, 1, 1);

        $this->assertNotNull($journal);

        // Check Income & Expenditure
        $ie = $this->reportService->getIncomeAndExpenditure();
        $this->assertEquals(2776000.00, $ie['total_income']);
    }

    /**
     * Test 3: Monthly Payer (₦8,000 payment)
     */
    public function test_monthly_payer_receipt()
    {
        $customer = Customer::factory()->create([
            'name' => 'Olumide Makanjuola',
            'occupancy_status' => 'occupied',
            'billing_status' => 'active',
        ]);

        $this->postingService->postAnnualBilling(2026, 96000.00, 8000.00, 1);
        
        $journal = $this->postingService->postResidentReceipt($customer->id, 8000.00, 'RCT-260018', '2026-01-05', 1);
        $this->assertNotNull($journal);

        $tb = $this->reportService->getTrialBalance();
        $this->assertTrue($tb['is_balanced']);
    }

    /**
     * Test 4: Full Year Payer (₦96,000 payment)
     */
    public function test_full_year_payer_receipt()
    {
        $customer = Customer::factory()->create([
            'occupancy_status' => 'occupied',
            'billing_status' => 'active',
        ]);

        $this->postingService->postAnnualBilling(2026, 96000.00, 8000.00, 1);
        $journal = $this->postingService->postResidentReceipt($customer->id, 96000.00, 'RCT-FULL', '2026-01-05', 1);

        $this->assertNotNull($journal);
    }

    /**
     * Test 5: Overpayment (₦100,000 against ₦96,000 bill)
     */
    public function test_overpayment_creates_resident_advance_credit()
    {
        $customer = Customer::factory()->create([
            'occupancy_status' => 'occupied',
            'billing_status' => 'active',
        ]);

        $this->postingService->postAnnualBilling(2026, 96000.00, 8000.00, 1);
        $journal = $this->postingService->postResidentReceipt($customer->id, 100000.00, 'RCT-OVERPAY', '2026-01-05', 1);

        $advanceAcc = ChartOfAccount::where('code', 2110)->first();
        $advanceCredit = JournalItem::where('account', $advanceAcc->id)->sum('credit');

        $this->assertEquals(4000.00, $advanceCredit);
    }

    /**
     * Test 6: EKEDC Token Agent Model Collection and Settlement
     */
    public function test_ekedc_token_agent_model()
    {
        // Collection ₦1,200,000
        $this->postingService->postEkedcCollection('12345678', 'EKEDC-001', 1200000.00, null, '2026-01-10', 1);

        // Settlement: Remit ₦1,150,000 and retain ₦50,000 commission
        $this->postingService->postEkedcSettlement(1200000.00, 1150000.00, 50000.00, 'EKEDC-SETTLE-001', '2026-01-15', 1);

        $ie = $this->reportService->getIncomeAndExpenditure();
        $this->assertEquals(50000.00, $ie['total_income']);
    }

    /**
     * Test 7: Transformer Asset & Depreciation
     */
    public function test_transformer_asset_purchase_and_depreciation()
    {
        // Capitalize ₦2,400,000 Transformer Asset
        $this->postingService->postTransformerAssetPurchase(2400000.00, 'ASSET-TRANS-01', 'Transformer Asset Acquisition', '2026-01-01', 1);

        // Monthly Depreciation ₦40,000
        $this->postingService->postMonthlyDepreciation(40000.00, 'DEP-TRANS-01', '2026-01-31', 1);

        $bs = $this->reportService->getBalanceSheet();
        $this->assertTrue($bs['is_balanced']);
    }

    /**
     * Test 8: Reversal Control (Immutable Audit Rule)
     */
    public function test_journal_entry_reversal()
    {
        $customer = Customer::factory()->create();
        $journal = $this->postingService->postResidentReceipt($customer->id, 8000.00, 'RCT-REV-TEST', '2026-01-05', 1);

        $reversal = $this->postingService->reverseJournalEntry($journal->id, 'Mistaken receipt entry', 1);

        $this->assertTrue($journal->fresh()->is_reversed);
        $this->assertEquals($journal->id, $reversal->reverses_id);
    }
}
