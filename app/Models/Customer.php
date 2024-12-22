<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory; 

    protected $fillable = [
        'user_id',
        'lead_source_id',
        'visitor_id',
        'customer_id',
        'customer_type',
        'ref_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    


    public static function generateNextVisitorId(){ 
        $largest_employee_id = Customer::where('visitor_id', 'like', 'VIS-%') 
        ->pluck('visitor_id')
                ->map(function ($id) {
                        return preg_replace("/[^0-9]/", "", $id);
                }) 
        ->max(); 
        $largest_employee_id++;
        $new_employee_id = 'VIS-' . str_pad($largest_employee_id, 6, '0', STR_PAD_LEFT);
        return $new_employee_id;
    } 
}
