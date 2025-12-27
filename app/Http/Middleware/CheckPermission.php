<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permission): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($user->is_admin) {
            return $next($request);
        }

        // Check if user has the permission via designation
        // We need to load permissions if not already loaded, but ideally user should have it cached or eager loaded
        // For now, let's query it or utilize the relationship
        $hasPermission = false;
        
        $currentDesignation = $user->currentDesignation;

        if ($currentDesignation && $currentDesignation->designation) {
             // Support for OR condition with pipe "|"
             $requiredPermissions = explode('|', $permission);
             $hasPermission = $currentDesignation->designation->permissions
                ->whereIn('name', $requiredPermissions)
                ->isNotEmpty();
        }

        if (!$hasPermission) {
            return response()->json(['message' => 'You do not have permission to access this resource.'], 403);
        }

        return $next($request);
    }
}
