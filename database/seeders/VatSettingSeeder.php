<?php

namespace Database\Seeders;

use App\Models\VatSetting;
use Illuminate\Database\Seeder;

class VatSettingSeeder extends Seeder
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

        VatSetting::firstOrCreate(
            [
                'company_id' => $this->companyId,
                'name' => 'Standard VAT Rate',
            ],
            [
                'vat_percentage' => 15.00,
                'is_active' => true,
                'note' => 'This is the standard VAT rate in Bangladesh for most goods and services.',
                'created_by' => 1,
            ]
        );
    }
}
