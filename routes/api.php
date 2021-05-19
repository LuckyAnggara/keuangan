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
    Route::post('/storebatch', 'JurnalController@storeBatch');
    Route::post('/retur', 'JurnalController@retur');
    //GET
    Route::get('/{cabang}/{dd}/{ddd}', 'JurnalController@index');
    Route::get('/{nomorjurnal}', 'JurnalController@geJurnalByNomorJurnal');
    Route::get('/reqnomorjurnal', 'JurnalController@nomorJurnal');
    //delete
    Route::delete('/delete/{nomorJurnal}', 'JurnalController@destroy');

});

// LEDGER
Route::group(['prefix' => 'ledger'], function () {
    //GET
    Route::get('/{cabang}/{id}/{dd}/{ddd}', 'LedgerController@detail');
});

// AKUN
Route::group(['prefix' => 'akun'], function () {
    //POST
    Route::post('/store', 'AkunController@store');
    //GET
    Route::get('/tahun/{tahun}', 'AkunController@index');
    Route::get('/ceksaldo/{id}', 'AkunController@cekSaldo');
    // Route::get('/{id}', 'PersediaanController@show');
    //DESTROY
    // Route::delete('/{id}', 'BarangController@destroy');
});

// NERACA
Route::group(['prefix' => 'neraca'], function () {
    //GET
    Route::get('/tahun/{tahun}', 'NeracaController@index');
});
