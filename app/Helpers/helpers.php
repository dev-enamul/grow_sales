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

if (!function_exists('getFileUrl')) {
    /**
     * Get the full URL for a file by file_id
     * 
     * @param int|null $fileId The file ID from the files table
     * @return string|null The preview URL or null if file not found
     */
    function getFileUrl($fileId)
    {
        if (!$fileId) {
            return null;
        }

        try {
            $file = \App\Models\FileItem::find($fileId);
            return $file?->preview_path;
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('formatDate')) {
    /**
     * Format a date for frontend display
     * Auto-detects if date has time component and formats accordingly
     * 
     * @param mixed $date Carbon instance, date string, DateTime, or null
     * @param string|null $format Custom format (if null, auto-detects: 'Y-m-d H:i:s' for datetime, 'Y-m-d' for date only)
     * @return string|null Formatted date string or null if date is invalid/empty
     */
    function formatDate($date, $format = null)
    {
        if (!$date) {
            return null;
        }

        try {
            $carbon = null;

            // Convert to Carbon instance
            if ($date instanceof \Carbon\Carbon) {
                $carbon = $date;
            } elseif (is_string($date)) {
                $carbon = \Carbon\Carbon::parse($date);
            } elseif ($date instanceof \DateTime) {
                $carbon = \Carbon\Carbon::instance($date);
            } else {
                return null;
            }

            // If custom format is provided, use it
            if ($format !== null) {
                return $carbon->format($format);
            }

            // Auto-detect: check if time component exists (not 00:00:00)
            $hasTime = $carbon->format('H:i:s') !== '00:00:00';
            
            // If has time, use datetime format; otherwise use date only
            $defaultFormat = $hasTime ? 'Y-m-d H:i:s' : 'Y-m-d';
            
            return $carbon->format($defaultFormat);
        } catch (\Exception $e) {
            return null;
        }
    }
}




 

