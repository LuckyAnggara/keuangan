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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


// JURNAL
Route::group(['prefix' => 'jurnal'], function () {
    //POST
    Route::post('/store', 'JurnalController@store');
    //GET
    Route::get('/', 'JurnalController@index');
    Route::get('/reqnomorjurnal', 'JurnalController@nomorJurnal');
    // Route::get('/{id}', 'PersediaanController@show');
    //DESTROY
    // Route::delete('/{id}', 'BarangController@destroy');
});

// AKUN
Route::group(['prefix' => 'akun'], function () {
    //POST
    Route::post('/store', 'AkunController@store');
    //GET
    Route::get('/', 'AkunController@index');
    // Route::get('/{id}', 'PersediaanController@show');
    //DESTROY
    // Route::delete('/{id}', 'BarangController@destroy');
});
