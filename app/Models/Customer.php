<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, ActionTrackable, SoftDeletes; 

    protected $fillable = [
        'user_id',
        'company_id',
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
    
    public static function generateNextCustomerId(){ 
        $largest_customer_id = Customer::where('customer_id', 'like', 'CUS-%') 
        ->pluck('customer_id')
                ->map(function ($id) {
                        return (int) substr($id, 4);
                }) 
        ->max(); 
        $largest_customer_id++;
        $new_customer_id = 'CUS-' . str_pad($largest_customer_id, 6, '0', STR_PAD_LEFT);
        return $new_customer_id;
    } 

    protected $dates = ['deleted_at'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
 
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
 
    public function destroyer()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

}
