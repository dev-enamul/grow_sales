<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    protected $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    public function run()
    {
        if (!$this->companyId) {
            return;
        }

        $accounts = [
            // Assets
            [
                'code' => '1000',
                'name' => 'Cash in Hand',
                'type' => 'Asset',
                'is_bank_account' => false,
            ],
            [
                'code' => '1100',
                'name' => 'Accounts Receivable',
                'type' => 'Asset',
                'is_bank_account' => false,
            ],
            [
                'code' => '1200',
                'name' => 'Inventory Asset',
                'type' => 'Asset',
                'is_bank_account' => false,
            ],
            
            // Liabilities
            [
                'code' => '2000',
                'name' => 'Accounts Payable',
                'type' => 'Liability',
                'is_bank_account' => false,
            ],
            [
                'code' => '2100',
                'name' => 'VAT Payable',
                'type' => 'Liability',
                'is_bank_account' => false,
            ],

            // Equity
            [
                'code' => '3000',
                'name' => 'Opening Balance Equity',
                'type' => 'Equity',
                'is_bank_account' => false,
            ],
            [
                'code' => '3100',
                'name' => 'Owner\'s Equity',
                'type' => 'Equity',
                'is_bank_account' => false,
            ],

            // Income
            [
                'code' => '4000',
                'name' => 'Sales/Revenue',
                'type' => 'Income',
                'is_bank_account' => false,
            ],
            [
                'code' => '4100',
                'name' => 'Service Income',
                'type' => 'Income',
                'is_bank_account' => false,
            ],

            // Expenses
            [
                'code' => '5000',
                'name' => 'Cost of Goods Sold',
                'type' => 'Expense',
                'is_bank_account' => false,
            ],
            [
                'code' => '5100',
                'name' => 'Salary Expense',
                'type' => 'Expense',
                'is_bank_account' => false,
            ],
            [
                'code' => '5200',
                'name' => 'Rent Expense',
                'type' => 'Expense',
                'is_bank_account' => false,
            ],
            [
                'code' => '5300',
                'name' => 'Office Supplies',
                'type' => 'Expense',
                'is_bank_account' => false,
            ],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                [
                    'company_id' => $this->companyId,
                    'code' => $account['code'],
                ],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'is_bank_account' => $account['is_bank_account'],
                ]
            );
        }
    }
}
