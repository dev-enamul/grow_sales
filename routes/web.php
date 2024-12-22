<?php

use App\Helpers\ReportingService;
use App\Models\Employee;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {  
    $employee = Employee::find(1);
    // dd($employee->designation("2024-4-4")->first()->designation->title);

    return view('welcome');
});
