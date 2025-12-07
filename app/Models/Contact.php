<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use App\Traits\FindByUuidTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Contact extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable, FindByUuidTrait;

    protected $fillable = [
        'uuid',
        'organization_id',
        'company_id',
        'name',
        'phone',
        'profile_image',
        'secondary_phone',
        'email',
        'secondary_email',
        'whatsapp',
        'facebook',
        'linkedin',
        'website',
        'dob',
        'gender',
        'marital_status',
        'blood_group',
        'religion',
        'education',
        'profession',
        'avalable_time',
        'bio',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'dob' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
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

    public function addresses()
    {
        return $this->hasMany(ContactAddress::class);
    }
    
    public function leadContacts()
    {
        return $this->hasMany(LeadContact::class);
    }
    
    public function leads()
    {
        return $this->belongsToMany(Lead::class, 'lead_contacts', 'contact_id', 'lead_id')
                    ->withPivot('relationship_or_role', 'is_decision_maker', 'notes')
                    ->withTimestamps();
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
