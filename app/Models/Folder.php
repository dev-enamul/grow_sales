<?php

namespace App\Models;

use App\Traits\ActionTrackable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Folder extends Model
{
    use HasApiTokens, ActionTrackable;
    
    protected $fillable = ['uuid', 'company_id','name', 'parent_id', 'created_by','updated_by'];

    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(FileItem::class, 'folder_id');
    }
}
