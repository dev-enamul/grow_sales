<?php
 namespace Database\Seeders;

 use App\Models\Designation;
 use Illuminate\Database\Seeder;
 
 class DesignationSeeder extends Seeder
 {
     public $companyId;
 
     public function __construct($companyId = null)
     {
         $this->companyId = $companyId;
     }
 
     public function run(): void
     {
         $designations = [
             'Chief Executive Officer',
             'Chief Operating Officer',
             'Chief Technology Officer',
             'Software Engineer',
             'Junior Software Engineer',
             'Senior Software Engineer',
             'Project Manager',
             'Human Resources Manager',
             'Marketing Manager',
             'Sales Executive',
             'Accountant',
             'Graphic Designer',
         ];
 
         if($this->companyId){
             foreach ($designations as $title) { 
                 Designation::create([
                     'company_id' => $this->companyId,
                     'title' => $title,
                     'slug' => getSlug(new Designation(), $title),
                 ]);
             }
         } 
     }
 }
 
