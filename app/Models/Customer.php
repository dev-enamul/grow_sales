<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes; 

    protected $fillable = [
        'user_id',
        'lead_source_id',
        'customer_id',
        'referred_by',
        'total_sales',
        'total_sales_amount',
        'newsletter_subscribed',
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

    protected $dates = ['deleted_at'];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }  

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }  

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }  

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
 
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}
