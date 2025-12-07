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
        'subtotal',
        'discount',
        'grand_total',
        'negotiated_price',
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
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
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

    public static function generateNextLeadId(){
        $largest_lead_id = Lead::where('lead_id', 'like', 'LEAD-%') 
        ->pluck('lead_id')
                ->map(function ($id) {
                        return preg_replace("/[^0-9]/", "", $id);
                }) 
        ->max(); 
        $largest_lead_id++;
        $new_lead_id = 'LEAD-' . str_pad($largest_lead_id, 6, '0', STR_PAD_LEFT);
        return $new_lead_id;
    } 


}
