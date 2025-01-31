<?php

namespace Database\Seeders;
namespace Database\Seeders;

use App\Models\LeadCategory;
use Illuminate\Database\Seeder;

class LeadCategorySeeder extends Seeder
{
    public $companyId;

    public function __construct($companyId = null)
    {
        $this->companyId = $companyId;
    }

    public function run(): void
    {
        if ($this->companyId) {
            $categories = [
                'Opportunity',
                'Prospect',
                'Followup',
                'Negotiation',
            ];   
            
            foreach ($categories as $index => $title) {
                LeadCategory::create([
                    'company_id' => $this->companyId,
                    'title' => $title,
                    'slug' => getSlug(new LeadCategory(), $title),
                    'serial' => $index * 5,  
                ]);
            }
        }
    }
}
