<?php
 namespace Database\Seeders;

 use App\Models\Role;
 use Illuminate\Database\Seeder;
 
 class RoleSeeder extends Seeder
 {
     public $companyId;
 
     public function __construct($companyId = null)
     {
         $this->companyId = $companyId;
     }
 
     public function run(): void
     {
         $roles = [
             'Admin',
             'Student',
             'Manager',
             'Salesperson',
             'Support',
             'Customer',
             'Marketing',
             'HR',
             'Accounting'
         ];
 
         if ($this->companyId) {
             $rolesData = []; 
             foreach ($roles as $role) {
                 $rolesData[] = [
                     'company_id' => $this->companyId,
                     'name' => $role,
                     'slug' => getSlug(new Role(), $role),
                 ];
             }
 
             Role::insert($rolesData);  // Using Eloquent to insert the roles
         }
     }
 }
 
