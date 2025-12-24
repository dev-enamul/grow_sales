<?php

namespace Database\Seeders;

use App\Models\PaymentReason;
use Illuminate\Database\Seeder;

class PaymentReasonSeeder extends Seeder
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

        $reasons = [
            [
                'name' => 'Booking Money',
                'description' => 'Initial payment to book the property/product.',
            ],
            [
                'name' => 'Down Payment',
                'description' => 'Large upfront payment made after booking.',
            ],
        ];

        // Add Installments 1 to 30
        for ($i = 1; $i <= 30; $i++) {
            $reasons[] = [
                'name' => "Installment $i",
                'description' => "Monthly installment number $i.",
            ];
        }

        $reasons[] = [
             'name' => 'Registration Cost',
             'description' => 'Cost for property registration.',
        ];

        
        $reasons[] = [
             'name' => 'Utility Charge',
             'description' => 'Charges for electricity, gas, water connections.',
        ];

        foreach ($reasons as $reason) {
            PaymentReason::firstOrCreate(
                [
                    'company_id' => $this->companyId,
                    'name' => $reason['name'],
                ],
                [
                    'description' => $reason['description'],
                ]
            );
        }
    }
}
