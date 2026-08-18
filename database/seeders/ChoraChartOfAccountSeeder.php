<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\ChartOfAccountSubType;
use App\Models\ChartOfAccountType;
use Illuminate\Database\Seeder;

class ChoraChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Assets' => 1,
            'Liabilities' => 2,
            'Expenses' => 3,
            'Income' => 4,
            'Equity' => 5,
        ];

        foreach ($types as $name => $id) {
            ChartOfAccountType::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'created_by' => 1]
            );
        }

        $subTypes = [
            1 => ['Current Assets', 'Fixed Assets'],
            2 => ['Current Liabilities', 'Non-Current Liabilities'],
            3 => ['Operating Expenses', 'Administrative Expenses'],
            4 => ['Revenue', 'Other Income'],
            5 => ['Owner Equity', 'Accumulated Fund'],
        ];

        $subTypeIdMap = [];
        $subId = 1;
        foreach ($subTypes as $typeId => $subNames) {
            foreach ($subNames as $subName) {
                $subType = ChartOfAccountSubType::updateOrCreate(
                    ['id' => $subId],
                    ['name' => $subName, 'type' => $typeId, 'created_by' => 1]
                );
                $subTypeIdMap[$subName] = $subType->id;
                $subId++;
            }
        }

        $accounts = [
            // Assets
            ['code' => 1010, 'name' => 'Access Bank', 'type' => 1, 'sub_type' => $subTypeIdMap['Current Assets']],
            ['code' => 1100, 'name' => 'Resident Dues Receivable Control', 'type' => 1, 'sub_type' => $subTypeIdMap['Current Assets']],
            ['code' => 1110, 'name' => 'Other Resident Charges Receivable', 'type' => 1, 'sub_type' => $subTypeIdMap['Current Assets']],
            ['code' => 1200, 'name' => 'Token Inventory/Prepaid Power', 'type' => 1, 'sub_type' => $subTypeIdMap['Current Assets']],
            ['code' => 1300, 'name' => 'Prepayments', 'type' => 1, 'sub_type' => $subTypeIdMap['Current Assets']],
            ['code' => 1500, 'name' => 'Transformer/Electrical Infrastructure Asset', 'type' => 1, 'sub_type' => $subTypeIdMap['Fixed Assets']],
            ['code' => 1590, 'name' => 'Accumulated Depreciation', 'type' => 1, 'sub_type' => $subTypeIdMap['Fixed Assets']],

            // Liabilities
            ['code' => 2010, 'name' => 'Supplier Payables', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],
            ['code' => 2020, 'name' => 'EKEDC Clearing/Payable', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],
            ['code' => 2100, 'name' => 'Deferred Association Dues', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],
            ['code' => 2110, 'name' => 'Resident Advances/Credits', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],
            ['code' => 2120, 'name' => 'Unidentified Receipts Suspense', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],
            ['code' => 2200, 'name' => 'Restricted Programme Funds', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],
            ['code' => 2300, 'name' => 'Accrued Salaries/Expenses', 'type' => 2, 'sub_type' => $subTypeIdMap['Current Liabilities']],

            // Equity / Accumulated Fund
            ['code' => 3100, 'name' => 'Accumulated Fund', 'type' => 5, 'sub_type' => $subTypeIdMap['Accumulated Fund']],
            ['code' => 3200, 'name' => 'Current-Year Surplus/Deficit', 'type' => 5, 'sub_type' => $subTypeIdMap['Accumulated Fund']],

            // Income
            ['code' => 4010, 'name' => 'Association Dues Income', 'type' => 4, 'sub_type' => $subTypeIdMap['Revenue']],
            ['code' => 4020, 'name' => 'Donation Income', 'type' => 4, 'sub_type' => $subTypeIdMap['Other Income']],
            ['code' => 4030, 'name' => 'Token Sales or Vending Commission', 'type' => 4, 'sub_type' => $subTypeIdMap['Revenue']],
            ['code' => 4040, 'name' => 'Dog-Control Fine Income', 'type' => 4, 'sub_type' => $subTypeIdMap['Other Income']],
            ['code' => 4050, 'name' => 'Reconnection Fee Income', 'type' => 4, 'sub_type' => $subTypeIdMap['Other Income']],
            ['code' => 4090, 'name' => 'Other Income', 'type' => 4, 'sub_type' => $subTypeIdMap['Other Income']],

            // Expenditure
            ['code' => 5010, 'name' => 'Common Electricity', 'type' => 3, 'sub_type' => $subTypeIdMap['Operating Expenses']],
            ['code' => 5020, 'name' => 'Cost of Tokens', 'type' => 3, 'sub_type' => $subTypeIdMap['Operating Expenses']],
            ['code' => 5030, 'name' => 'Administrative Salaries', 'type' => 3, 'sub_type' => $subTypeIdMap['Administrative Expenses']],
            ['code' => 5040, 'name' => 'MOPOL/Security', 'type' => 3, 'sub_type' => $subTypeIdMap['Operating Expenses']],
            ['code' => 5050, 'name' => 'Programmes/Parties', 'type' => 3, 'sub_type' => $subTypeIdMap['Operating Expenses']],
            ['code' => 5060, 'name' => 'Repairs', 'type' => 3, 'sub_type' => $subTypeIdMap['Operating Expenses']],
            ['code' => 5070, 'name' => 'Depreciation', 'type' => 3, 'sub_type' => $subTypeIdMap['Administrative Expenses']],
            ['code' => 5080, 'name' => 'Bank Charges', 'type' => 3, 'sub_type' => $subTypeIdMap['Administrative Expenses']],
            ['code' => 5090, 'name' => 'Other Approved Expenses', 'type' => 3, 'sub_type' => $subTypeIdMap['Administrative Expenses']],
        ];

        foreach ($accounts as $acc) {
            ChartOfAccount::updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'sub_type' => $acc['sub_type'],
                    'is_enabled' => 1,
                    'created_by' => 1,
                ]
            );
        }
    }
}
