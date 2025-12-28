<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define all permissions grouped by module
        $permissions = [
             
            'Dashboard' => [
                ['name' => 'dashboard.view', 'title' => 'View Dashboard Statistics'],
                ['name' => 'dashboard.view_financial', 'title' => 'View Financial Widgets'],
            ],
 
            'Employee' => [
                ['name' => 'employee.view_all', 'title' => 'View All Employees'],
                ['name' => 'employee.view_own', 'title' => 'View Own Team (Junior Employees)'],
                ['name' => 'employee.create', 'title' => 'Create Employee'],
                ['name' => 'employee.edit', 'title' => 'Edit Employee Info'],
                ['name' => 'employee.salary_view', 'title' => 'View Employee Salary'],
                ['name' => 'employee.salary_update', 'title' => 'Update Employee Salary'],
                ['name' => 'employee.delete', 'title' => 'Delete Employee'],
            ],

            'Affiliate' => [
                ['name' => 'affiliate.view_all', 'title' => 'View All Affiliates'],
                ['name' => 'affiliate.view_own', 'title' => 'View Own Team (Junior Affiliates)'],
                ['name' => 'affiliate.create', 'title' => 'Create Affiliate'],
                ['name' => 'affiliate.edit', 'title' => 'Edit Affiliate Info'], 
                ['name' => 'affiliate.delete', 'title' => 'Delete Affiliate'],
            ],

            'Designation' => [
                ['name' => 'designation.view', 'title' => 'View All Designations'],
                ['name' => 'designation.create', 'title' => 'Create Designation'],
                ['name' => 'designation.edit', 'title' => 'Edit Designation Info'], 
                ['name' => 'designation.delete', 'title' => 'Delete Designation'],
                ['name' => 'designation.permission', 'title' => 'Change Designation Permission'],
            ],

            
            'Property' => [
                ['name' => 'property.view', 'title' => 'View Properties'],
                ['name' => 'property.create', 'title' => 'Create Property'],
                ['name' => 'property.edit', 'title' => 'Edit Property'],
                ['name' => 'property.delete', 'title' => 'Delete Property'],
            ],
 
            'Service' => [
                ['name' => 'service.view', 'title' => 'View Services'],
                ['name' => 'service.create', 'title' => 'Create Service'],
                ['name' => 'service.edit', 'title' => 'Edit Service'],
                ['name' => 'service.delete', 'title' => 'Delete Service'],
            ],

            'Contact' => [
                ['name' => 'contact.view', 'title' => 'View Contacts'],
                ['name' => 'contact.create', 'title' => 'Create Contact'],
                ['name' => 'contact.edit', 'title' => 'Edit Contact'],
                ['name' => 'contact.delete', 'title' => 'Delete Contact'],
            ],
 
            'Lead' => [
                ['name' => 'lead.view_all', 'title' => 'View All Leads'],
                ['name' => 'lead.view_own', 'title' => 'View Own/Team Leads Only'],
                ['name' => 'lead.create', 'title' => 'Create Lead'],
                ['name' => 'lead.edit', 'title' => 'Edit Lead'],
                ['name' => 'lead.delete', 'title' => 'Delete Lead'],
                ['name' => 'lead.followup', 'title' => 'Followup Lead'],
                ['name' => 'lead.assign', 'title' => 'Assign Lead to Others'],
                ['name' => 'lead.import', 'title' => 'Import Leads'],
                ['name' => 'lead.export', 'title' => 'Export Leads'],
            ],

            'Customer' => [
                ['name' => 'customer.view_all', 'title' => 'View All Customers'],
                ['name' => 'customer.view_own', 'title' => 'View Own/Team Customers Only'],
                ['name' => 'customer.create', 'title' => 'Create Customer'],
                ['name' => 'customer.edit', 'title' => 'Edit Customer'],
                ['name' => 'customer.delete', 'title' => 'Delete Customer'],
            ], 
 
            'Sales' => [
                ['name' => 'sales.view_all', 'title' => 'View All Sales'],
                ['name' => 'sales.view_own', 'title' => 'View Own Sales'],
                ['name' => 'sales.create', 'title' => 'Create Sales Invoice'],
                ['name' => 'sales.edit', 'title' => 'Edit Sales Invoice'],
                ['name' => 'sales.delete', 'title' => 'Delete Sales Invoice'],
                ['name' => 'sales.approve', 'title' => 'Approve Sales'],
                ['name' => 'sales.reject', 'title' => 'Reject Sales'],
                ['name' => 'sales.return', 'title' => 'Return Sales'],
                ['name' => 'sales.transfer', 'title' => 'Transfer Sales'],
                ['name' => 'sales.create_payment_schedule', 'title' => 'Create Payment Schedule'],
                ['name' => 'sales.payment_collection', 'title' => 'Collect Payments'], 
            ],
  
            'Configuration' => [
                ['name' => 'configuration.vat_view', 'title' => 'View VAT Configuration'], 
                ['name' => 'configuration.vat_manage', 'title' => 'Manage VAT Configuration'], 
                ['name' => 'configuration.measurements_view', 'title' => 'View Measurements Configuration'], 
                ['name' => 'configuration.measurements_manage', 'title' => 'Manage Measurements Configuration'], 
                ['name' => 'configuration.properties_view', 'title' => 'View Properties Configuration'], 
                ['name' => 'configuration.properties_manage', 'title' => 'Manage Properties Configuration'], 
                ['name' => 'configuration.pipeline_view', 'title' => 'View Pipeline Configuration'], 
                ['name' => 'configuration.pipeline_manage', 'title' => 'Manage Pipeline Configuration'], 
                ['name' => 'configuration.media_view', 'title' => 'View Media Configuration'], 
                ['name' => 'configuration.media_manage', 'title' => 'Manage Media Configuration'], 
                ['name' => 'configuration.company_view', 'title' => 'View Company Configuration'], 
                ['name' => 'configuration.company_manage', 'title' => 'Manage Company Configuration'], 
            ], 
            'Location' => [
                ['name' => 'location.view', 'title' => 'View Locations'], 
                ['name' => 'location.create', 'title' => 'Create Location'], 
                ['name' => 'location.edit', 'title' => 'Edit Location'], 
                ['name' => 'location.delete', 'title' => 'Delete Location'], 
            ],  
        ]; 

        foreach ($permissions as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(
                    ['name' => $perm['name']],
                    [ 
                        'title' => $perm['title'],
                        'group_name' => $group,
                        'guard_name' => 'web',
                    ]
                );
            }
        }
    }
}
