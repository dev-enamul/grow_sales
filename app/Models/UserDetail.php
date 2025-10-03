<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Company;
use App\Traits\ActionTrackable;

class UserDetail extends Model
{
    use HasFactory, SoftDeletes, ActionTrackable; 
    protected $fillable = [
        'uuid',
        'user_id',
        "customer_id",
        'company_id',
        'name',
        'primary_phone',
        'secondary_phone',
        'primary_email',
        'secondary_email',
        'whatsapp',
        'imo',
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
        'relationship_or_role',
        'is_decision_maker',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

   public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
