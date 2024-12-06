<?php 
if (!function_exists('api_response')) { 
    function api_response($data = null, $message = 'Success', $success = true, $statusCode = 200, $errors = null)
    { 
        $responseData = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,  
            'timestamp' => now()->toIso8601String(),  
            'request_id' => request()->header('X-Request-ID') ?: uniqid(),  
        ]; 
        return response()->json($responseData, $statusCode);
    }
}

