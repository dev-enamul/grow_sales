<?php

namespace Database\Seeders;

use App\Models\MeasurmentUnit;
use Illuminate\Database\Seeder;

class MeasurmentUnitSeeder extends Seeder
{
    protected $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!$this->companyId) {
            return;
        }

        $units = [
            ['name' => 'Box', 'abbreviation' => 'Box'],
            ['name' => 'Piece', 'abbreviation' => 'Pcs'],
            ['name' => 'Kilogram', 'abbreviation' => 'KG'],
            ['name' => 'Meter', 'abbreviation' => 'M'],
            ['name' => 'Liter', 'abbreviation' => 'Ltr'],
            ['name' => 'Feet', 'abbreviation' => 'Ft'],
            ['name' => 'Dozen', 'abbreviation' => 'Dzn'],
            ['name' => 'Pound', 'abbreviation' => 'Lb'],
        ];

        foreach ($units as $unit) {
            MeasurmentUnit::firstOrCreate(
                [
                    'company_id' => $this->companyId,
                    'name' => $unit['name'],
                ],
                [
                    'abbreviation' => $unit['abbreviation'],
                    'is_active' => true,
                ]
            );
        }
    }
}
