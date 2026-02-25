<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/v1/viewadminlogin',[\App\Http\Controllers\ApiController::class, 'getadmin']);
Route::get('/v1/printresult',[\App\Http\Controllers\ApiControllerResult::class, 'printresult']);
Route::get('/v1/printbook',[\App\Http\Controllers\ApiControllerRegister::class, 'printbook']);
    
