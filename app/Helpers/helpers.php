<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
if (!function_exists('success_response')) {
    function success_response($data = null, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID') ?: uniqid(),
        ], $statusCode);
    }
}

if (!function_exists('error_response')) {
    function error_response($errors = null, $statusCode = 500, $message = 'An error occurred')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
            'timestamp' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID') ?: uniqid(),
        ], $statusCode);
    }
}


if (!function_exists('getSlug')) {
    function getSlug($model, $title, $column = 'slug', $separator = '-') { 
        $slug         = Str::slug($title);
        $originalSlug = $slug;
        $count        = 1;  

        if (Schema::hasColumn((new $model)->getTable(), 'company_id')) { 
            $companyId = auth()->user()->company_id ?? null;  
             
            while ($model::where('company_id', $companyId)->where($column, $slug)->exists()) {
                $slug = $originalSlug . $separator . $count;
                $count++;
            }
        } else { 
            while ($model::where($column, $slug)->exists()) {
                $slug = $originalSlug . $separator . $count;
                $count++;
            }
        }

        return $slug;
    }
}




 

