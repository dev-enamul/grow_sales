<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleApiController extends Controller
{
    public function __invoke()
    {
        try { 
            $data = Role::select('id','name','slug')->where('company_id',Auth::user()->company_id)->get(); 
            if ($data->isEmpty()) {
                return error_response('No roles found', 404);
            }  
            return success_response($data);
            
        } catch (\Exception $e) {   
            return error_response($e->getMessage(), 500);
        }
    }
}
