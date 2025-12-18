<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\AreaStructure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AreaSeeder extends Seeder
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
            throw new \Exception('Company ID is required for AreaSeeder');
        }

        // Get all area structures for this company, organized by hierarchy
        $countryStructures = AreaStructure::where('company_id', $this->companyId)
            ->whereNull('parent_id')
            ->get();

        if ($countryStructures->isEmpty()) {
            throw new \Exception('No area structures found. Please run AreaStructureSeeder first.');
        }

        // Assuming we're working with Bangladesh structure
        $countryStructure = $countryStructures->first();

        // Create Country level area (check if exists first)
        $countryArea = Area::where('company_id', $this->companyId)
            ->where('name', $countryStructure->name)
            ->whereNull('parent_id')
            ->first();

        if (!$countryArea) {
            $countryArea = Area::create([
                'company_id' => $this->companyId,
                'uuid' => (string) Str::uuid(),
                'name' => $countryStructure->name,
                'parent_id' => null,
                'area_structure_id' => $countryStructure->id,
                'status' => 1,
            ]);
        }

        // Get all divisions from area structures
        $divisionStructures = AreaStructure::where('company_id', $this->companyId)
            ->where('parent_id', $countryStructure->id)
            ->get();
        $divisionAreas = [];

        foreach ($divisionStructures as $divisionStructure) {
            // Check if area already exists (unique constraint is on company_id + name + parent_id)
            $divisionArea = Area::where('company_id', $this->companyId)
                ->where('name', $divisionStructure->name)
                ->where('parent_id', $countryArea->id)
                ->first();

            if (!$divisionArea) {
                $divisionArea = Area::create([
                    'company_id' => $this->companyId,
                    'uuid' => (string) Str::uuid(),
                    'name' => $divisionStructure->name,
                    'parent_id' => $countryArea->id,
                    'area_structure_id' => $divisionStructure->id,
                    'status' => 1,
                ]);
            }
            $divisionAreas[$divisionStructure->id] = $divisionArea;
        }

        // Get all districts from area structures
        $districtStructures = AreaStructure::where('company_id', $this->companyId)
            ->whereIn('parent_id', $divisionStructures->pluck('id'))
            ->get();
        $districtAreas = [];

        foreach ($districtStructures as $districtStructure) {
            $parentArea = $divisionAreas[$districtStructure->parent_id] ?? null;
            if ($parentArea) {
                // Check if area already exists (unique constraint is on company_id + name + parent_id)
                $districtArea = Area::where('company_id', $this->companyId)
                    ->where('name', $districtStructure->name)
                    ->where('parent_id', $parentArea->id)
                    ->first();

                if (!$districtArea) {
                    // Get district data for coordinates
                    $districtData = DB::table('districts')
                        ->where('name', $districtStructure->name)
                        ->first();

                    $districtArea = Area::create([
                        'company_id' => $this->companyId,
                        'uuid' => (string) Str::uuid(),
                        'name' => $districtStructure->name,
                        'parent_id' => $parentArea->id,
                        'area_structure_id' => $districtStructure->id,
                        'latitude' => $districtData->lat ?? null,
                        'longitude' => $districtData->lon ?? null,
                        'status' => 1,
                    ]);
                }
                $districtAreas[$districtStructure->id] = $districtArea;
            }
        }

        // Get all upazilas from area structures
        $upazilaStructures = AreaStructure::where('company_id', $this->companyId)
            ->whereIn('parent_id', $districtStructures->pluck('id'))
            ->get();
        $upazilaAreas = [];

        foreach ($upazilaStructures as $upazilaStructure) {
            $parentArea = $districtAreas[$upazilaStructure->parent_id] ?? null;
            if ($parentArea) {
                // Check if area already exists (unique constraint is on company_id + name + parent_id)
                $upazilaArea = Area::where('company_id', $this->companyId)
                    ->where('name', $upazilaStructure->name)
                    ->where('parent_id', $parentArea->id)
                    ->first();

                if (!$upazilaArea) {
                    $upazilaArea = Area::create([
                        'company_id' => $this->companyId,
                        'uuid' => (string) Str::uuid(),
                        'name' => $upazilaStructure->name,
                        'parent_id' => $parentArea->id,
                        'area_structure_id' => $upazilaStructure->id,
                        'status' => 1,
                    ]);
                }
                $upazilaAreas[$upazilaStructure->id] = $upazilaArea;
            }
        }

        // Get all unions from area structures
        $unionStructures = AreaStructure::where('company_id', $this->companyId)
            ->whereIn('parent_id', $upazilaStructures->pluck('id'))
            ->get();

        foreach ($unionStructures as $unionStructure) {
            $parentArea = $upazilaAreas[$unionStructure->parent_id] ?? null;
            if ($parentArea) {
                // Check if area already exists (unique constraint is on company_id + name + parent_id)
                $existingUnionArea = Area::where('company_id', $this->companyId)
                    ->where('name', $unionStructure->name)
                    ->where('parent_id', $parentArea->id)
                    ->first();

                if (!$existingUnionArea) {
                    Area::create([
                        'company_id' => $this->companyId,
                        'uuid' => (string) Str::uuid(),
                        'name' => $unionStructure->name,
                        'parent_id' => $parentArea->id,
                        'area_structure_id' => $unionStructure->id,
                        'status' => 1,
                    ]);
                }
            }
        }
    }
}

