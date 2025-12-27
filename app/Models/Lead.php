<?php

namespace App\Models;

use App\Traits\ActionTrackable; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable; 
    protected $fillable = [
        'uuid',
        'company_id',
        'lead_id',
        'organization_id',
        'lead_category_id',
        'next_followup_date',
        'last_contacted_at',
        'discount',
        'other_price',
        'assigned_to',
        'lead_source_id',
        'campaign_id',
        'affiliate_id',
        'status',
        'notes',
        'challenges',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'discount' => 'decimal:2',
        'other_price' => 'decimal:2',
        'negotiated_price' => 'decimal:2',
        'next_followup_date' => 'date',
        'last_contacted_at' => 'datetime',
        'challenges' => 'array',
    ];
 
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
 
    public function leadCategory()
    {
        return $this->belongsTo(LeadCategory::class, 'lead_category_id');
    }
    
    public function followups()
    {
        return $this->hasMany(Followup::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
 
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function affiliate()
    {
        return $this->belongsTo(User::class, 'affiliate_id');
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
    
    public function products()
    {
        return $this->hasMany(LeadProduct::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'lead_contacts', 'lead_id', 'contact_id')
                    ->withPivot('relationship_or_role', 'is_decision_maker', 'notes')
                    ->withTimestamps();
    }
    
    public function leadContacts()
    {
        return $this->hasMany(LeadContact::class);
    }

    public static function generateNextLeadId($companyId = null){
        // If company_id is not provided, try to get it from auth user
        if (!$companyId && auth()->check()) {
            $companyId = auth()->user()->company_id;
        }
        
        // Build query with company filter if available
        // Use withTrashed() to include soft deleted records in the check
        $query = Lead::withTrashed()->where('lead_id', 'like', 'LEAD-%');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $largest_lead_id = $query->pluck('lead_id')
                ->map(function ($id) {
                        return (int) preg_replace("/[^0-9]/", "", $id);
                }) 
        ->max(); 
        
        // Handle case where no leads exist yet
        $largest_lead_id = $largest_lead_id ? $largest_lead_id : 0;
        $largest_lead_id++;
        
        $new_lead_id = 'LEAD-' . str_pad($largest_lead_id, 6, '0', STR_PAD_LEFT);
        
        // Check if the generated ID already exists (including soft deleted - race condition protection)
        $maxAttempts = 10;
        $attempt = 0;
        while ($attempt < $maxAttempts) {
            $exists = Lead::withTrashed()->where('lead_id', $new_lead_id);
            if ($companyId) {
                $exists->where('company_id', $companyId);
            }
            $exists = $exists->exists();
            
            if (!$exists) {
        return $new_lead_id;
            }
            
            // If exists, try next number
            $largest_lead_id++;
            $new_lead_id = 'LEAD-' . str_pad($largest_lead_id, 6, '0', STR_PAD_LEFT);
            $attempt++;
        }
        
        // Fallback: add timestamp to ensure uniqueness
        return 'LEAD-' . str_pad($largest_lead_id, 6, '0', STR_PAD_LEFT) . '-' . time();
    } 


}
