<?php

namespace Database\Seeders;

use App\Models\AreaStructure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AreaStructureSeeder extends Seeder
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
            throw new \Exception('Company ID is required for AreaStructureSeeder');
        }

        // Get Bangladesh country (default country_id is 18 based on division migration)
        // Try to find Bangladesh by ISO code or name
        $bangladesh = DB::table('countries')
            ->where(function($query) {
                $query->where('iso', 'BD')
                      ->orWhere('name', 'like', '%Bangladesh%')
                      ->orWhere('name', 'like', '%বাংলাদেশ%');
            })
            ->first();
        
        // If not found, try to get country with ID 18 (default in divisions table)
        if (!$bangladesh) {
            $bangladesh = DB::table('countries')->where('id', 18)->first();
        }
        
        // If still not found, use the first country
        if (!$bangladesh) {
            $bangladesh = DB::table('countries')->first();
        }

        if (!$bangladesh) {
            throw new \Exception('No country found. Please run CountrySeeder first.');
        }

        // Create Country level area structure
        $countryStructure = AreaStructure::create([
            'company_id' => $this->companyId,
            'uuid' => (string) Str::uuid(),
            'name' => $bangladesh->name ?? 'Bangladesh',
            'parent_id' => null,
            'status' => 1,
        ]);

        // Get all divisions
        $divisions = DB::table('divisions')->get();
        $divisionStructures = [];

        foreach ($divisions as $division) {
            $divisionStructure = AreaStructure::create([
                'company_id' => $this->companyId,
                'uuid' => (string) Str::uuid(),
                'name' => $division->name,
                'parent_id' => $countryStructure->id,
                'status' => 1,
            ]);
            $divisionStructures[$division->id] = $divisionStructure;
        }

        // Get all districts
        $districts = DB::table('districts')->get();
        $districtStructures = [];

        foreach ($districts as $district) {
            $parentStructure = $divisionStructures[$district->division_id] ?? null;
            if ($parentStructure) {
                $districtStructure = AreaStructure::create([
                    'company_id' => $this->companyId,
                    'uuid' => (string) Str::uuid(),
                    'name' => $district->name,
                    'parent_id' => $parentStructure->id,
                    'status' => 1,
                ]);
                $districtStructures[$district->id] = $districtStructure;
            }
        }

        // Get all upazilas
        $upazilas = DB::table('upazilas')->get();
        $upazilaStructures = [];

        foreach ($upazilas as $upazila) {
            $parentStructure = $districtStructures[$upazila->district_id] ?? null;
            if ($parentStructure) {
                $upazilaStructure = AreaStructure::create([
                    'company_id' => $this->companyId,
                    'uuid' => (string) Str::uuid(),
                    'name' => $upazila->name,
                    'parent_id' => $parentStructure->id,
                    'status' => 1,
                ]);
                $upazilaStructures[$upazila->id] = $upazilaStructure;
            }
        }

        // Get all unions
        $unions = DB::table('unions')->get();

        foreach ($unions as $union) {
            $parentStructure = $upazilaStructures[$union->upazila_id] ?? null;
            if ($parentStructure) {
                AreaStructure::create([
                    'company_id' => $this->companyId,
                    'uuid' => (string) Str::uuid(),
                    'name' => $union->name,
                    'parent_id' => $parentStructure->id,
                    'status' => 1,
                ]);
            }
        }
    }
}

