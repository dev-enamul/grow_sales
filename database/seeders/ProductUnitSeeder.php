<?php

namespace Database\Seeders;

use App\Models\ProductUnit;
use Illuminate\Database\Seeder;

class ProductUnitSeeder extends Seeder
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

        $units = [
            ['name' => 'Flat', 'abbreviation' => 'FLT'],
            ['name' => 'Plot', 'abbreviation' => 'PLT'],
            ['name' => 'Duplex', 'abbreviation' => 'DPX'],
            ['name' => 'Penthouse', 'abbreviation' => 'PTH'],
            ['name' => 'Studio Apartment', 'abbreviation' => 'STD'],
            ['name' => 'Commercial Space', 'abbreviation' => 'CMS'],
            ['name' => 'Office Space', 'abbreviation' => 'OFF'],
            ['name' => 'Retail Shop', 'abbreviation' => 'RSP'],
            ['name' => 'Showroom', 'abbreviation' => 'SHR'],
            ['name' => 'Warehouse', 'abbreviation' => 'WHR'],
            ['name' => 'Parking Space', 'abbreviation' => 'PKS'],
            ['name' => 'Land', 'abbreviation' => 'LND'],
        ];

        foreach ($units as $unit) {
            ProductUnit::firstOrCreate(
                [
                    'company_id' => $this->companyId,
                    'name' => $unit['name'],
                ],
                [
                    'abbreviation' => $unit['abbreviation'],
                    'applies_to' => 'property',
                    'created_by' => 1, // System or Admin
                ]
            );
        }
    }
}
