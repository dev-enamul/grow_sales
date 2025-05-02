<?php

namespace App\Traits;

trait FindByUuidTrait
{
    public static function findByUuid($value)
    {
        $query = static::query();
        $model = new static;
 
        if (
            auth()->check() &&
            \Schema::hasColumn($model->getTable(), 'company_id')
        ) {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query->where('uuid', $value)->firstOrFail();
    }
}

