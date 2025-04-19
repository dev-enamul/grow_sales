<?php 
namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait Blameable
{
    public static function bootBlameable()
    {
        static::creating(function ($model) {
            $authUser = Auth::user(); 
            if (Auth::check()) {
                if($model->hasColumn('created_by')){
                    $model->created_by = $authUser->id;
                } 
                if($model->hasColumn('company_id')){
                    $model->company_id = $authUser->company_id;
                }
                if($model->hasColumn('uuid')){
                    $model->uuid = (string) Str::uuid();
                }
                if($model->hasColumn('slug')){
                    $model->slug = getSlug($model,$model->name);
                }
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && $model->hasColumn('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check() && $model->isSoftDelete() && $model->hasColumn('deleted_by')) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }

    protected function isSoftDelete()
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($this));
    }

    protected function hasColumn($column)
    {
        try {
            return Schema::hasColumn($this->getTable(), $column);
        } catch (\Exception $e) {
            return false;
        }
    }
}
