<?php

namespace Database\Seeders;

use App\Models\CategoryType;
use Illuminate\Database\Seeder;

class CategoryTypeSeeder extends Seeder
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

        $types = [
            ['name' => 'Residential'],
            ['name' => 'Commercial'],
            ['name' => 'Land'],
        ];

        foreach ($types as $type) {
            CategoryType::firstOrCreate(
                [
                    'company_id' => $this->companyId,
                    'name' => $type['name'],
                ],
                [
                    'applies_to' => 'property',
                    'created_by' => 1,
                ]
            );
        }
    }
}
