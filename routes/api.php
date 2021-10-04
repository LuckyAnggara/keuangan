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


// DASHBOARD
Route::group(['prefix' => 'dashboard-cabang'], function () {
    //GET
    Route::get('/omset-harian', 'DashboardCabangController@omsetHarian');
    Route::get('/laba-harian', 'DashboardCabangController@labaHarian');
    Route::get('/beban-harian', 'DashboardCabangController@bebanHarian');
    Route::get('/kas-harian', 'DashboardCabangController@kasHarian');
    Route::get('/utang-harian', 'DashboardCabangController@utangHarian');
    Route::get('/persediaan-harian', 'DashboardCabangController@persediaanHarian');
    Route::get('/laba-bulanan', 'DashboardCabangController@labaBulanan');

});

// CABANG
Route::group(['prefix' => 'cabang'], function () {
    //GET
    Route::get('/performance-all', 'CabangController@allPerformance');
    Route::get('/performance-satuan', 'CabangController@satuanPerformance');
    Route::get('/kas', 'CabangController@kas');

});


// JURNAL
Route::group(['prefix' => 'jurnal'], function () {
    //POST
    Route::post('/store', 'JurnalController@store');
    Route::post('/storebatch', 'JurnalController@storeBatch');
    //GET
    Route::get('/{cabang}/{dd}/{ddd}', 'JurnalController@index');
    Route::get('/{nomorjurnal}', 'JurnalController@geJurnalByNomorJurnal');
    Route::get('/reqnomorjurnal', 'JurnalController@nomorJurnal');
    Route::get('/retur/{nomorjurnal}', 'JurnalController@retur');
    //delete
    Route::delete('/delete/{nomorJurnal}', 'JurnalController@destroy');

});

// LEDGER
Route::group(['prefix' => 'ledger'], function () {
    //GET
    Route::get('/', 'LedgerController@detail');
});

// AKUN
Route::group(['prefix' => 'akun'], function () {
    //POST
    Route::post('/store', 'AkunController@store');
    //GET
    Route::get('/', 'AkunController@index');
    
    Route::get('/cek-saldo', 'AkunController@cekSaldoApi');
    // Route::get('/{id}', 'AkunController@show');
    Route::get('/cek-data', 'AkunController@cekPrefix');
    //DESTROY
    // Route::delete('/{id}', 'BarangController@destroy');
});

// NERACA
Route::group(['prefix' => 'neraca'], function () {
    //GET
    Route::get('/', 'NeracaController@index');
});

// LABARUGI
Route::group(['prefix' => 'labarugi'], function () {
    //GET
    Route::get('/', 'LabaRugiController@index');
});

// BEBAN
Route::group(['prefix' => 'beban'], function () {
    //GET
    Route::get('/operasional/cabang/{cabang}/tahun/{tahun}', 'BebanController@operasional');
    Route::get('/gaji/cabang/{cabang}/tahun/{tahun}', 'BebanController@gaji');
    //POST
    Route::post('/store', 'BebanController@store');
    //delete
    Route::delete('/delete/{id}', 'BebanController@destroy');
});


// KAS
Route::group(['prefix' => 'kas'], function () {
    //GET
    // Route::get('/detail/{id}/{dd}/{ddd}', 'KasController@detail');
    Route::get('/saldo-kas/{id}/{dd}/{ddd}', 'KasController@saldoKas');
    //POST
    Route::post('/store', 'KasController@store');
    //delete
    // Route::delete('/delete/{id}', 'BebanController@destroy');
});
