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
Route::get('/v1/viewpembelian',[\App\Http\Controllers\ApiControllerPembelian::class, 'viewpembelian']);
Route::get('/v1/viewpenerimaan',[\App\Http\Controllers\ApiControllerGudang::class, 'viewpenerimaan']);
Route::get('/v1/viewpenjualan',[\App\Http\Controllers\ApiControllerPenjualan::class, 'viewpenjualan']);
Route::get('/v1/viewpengiriman',[\App\Http\Controllers\ApiControllerGudang::class, 'viewpengiriman']);
Route::get('/v1/viewpenerimaankas',[\App\Http\Controllers\ApiControllerFinance::class, 'viewpenerimaankas']);
Route::get('/v1/viewpengeluarankas',[\App\Http\Controllers\ApiControllerFinance::class, 'viewpengeluarankas']);
Route::get('/v1/viewpurchasepayment',[\App\Http\Controllers\ApiControllerFinance::class, 'viewpurchasepayment']);
Route::get('/v1/viewsalespayment',[\App\Http\Controllers\ApiControllerFinance::class, 'viewsalespayment']);
Route::get('/v1/viewmutasikirim',[\App\Http\Controllers\ApiControllerGudang::class, 'viewmutasikirim']);
Route::get('/v1/viewmutasiterima',[\App\Http\Controllers\ApiControllerGudang::class, 'viewmutasiterima']);

// Cashier
Route::get('/v1/cash/viewpenjualan',[\App\Http\Controllers\ApiControllerCashier::class, 'viewpenjualan']);
Route::get('/v1/cash/viewadminlogin',[\App\Http\Controllers\ApiController::class, 'getadminCashier']);
// end Cashier
    
