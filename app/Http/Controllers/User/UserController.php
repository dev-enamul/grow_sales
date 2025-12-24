<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $authUser = Auth::user();
            $companyId = $authUser->company_id;
            $keyword = $request->keyword;

            $query = User::where('company_id', $companyId)
                ->whereIn('user_type', ['employee', 'affiliate'])
                ->where('status', 1);

            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('user_id', 'like', "%{$keyword}%");
                });
            }

            $users = $query->select('id', 'name', 'user_type', 'user_id', 'phone', 'email')
                           ->orderBy('created_at', 'desc')
                           ->limit(50) // Limit to 50 results to avoid performance issues
                           ->get();

            return success_response($users);
        } catch (Exception $e) {
            return error_response($e->getMessage(), 500);
        }
    }
}
