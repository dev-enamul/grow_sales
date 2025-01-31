<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignationApiController extends Controller
{
    public function __invoke()
    { 
        try { 
            $data = Designation::select('id','title','slug')->where('company_id',Auth::user()->company_id)->get(); 
            if ($data->isEmpty()) {
                return error_response('No designation found', 404);
            } 
            return success_response($data);
            
        } catch (\Exception $e) {   
            return error_response($e->getMessage(),500);
        }
    }
}
