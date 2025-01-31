<?php
namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;

class LeadSourceSeeder extends Seeder
{
    public $companyId;
 
    public function __construct($companyId = null)
    { 
        $this->companyId = $companyId;
    }

    public function run(): void
    {
        if ($this->companyId) {
            $leadSources = [
                'Self',
                'Website',
                'Facebook',
                'Youtube',
                'Personal Contact',
                'Referral'
            ];
    
            foreach ($leadSources as $title) {
                LeadSource::create([
                    'name' => $title,
                    'slug' => getSlug(new LeadSource(), $title),
                    'company_id' => $this->companyId
                ]);
            }
        }
    }
}

