<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class JurnalController extends Controller
{
    public function index(){
        $master = DB::table('master_jurnal')
        ->select('master_jurnal.*', 'master_akun.nama as nama_akun','master_akun.nomor_akun as kode_akun')
        ->join('master_akun','master_jurnal.master_akun_id','=','master_akun.nomor_akun')    
        ->where('master_jurnal.deleted_at')    
        ->get();
        
        return response()->json($master, 200);
    }
    public function store(Request $request){
        $data = Jurnal::create([
            'reff'=>$request['reff'],
            'nomor_jurnal'=>$request['nomor_jurnal'],
            'master_akun_id'=>$request['master_akun_id'],
            'nominal'=>$request['nominal'],
            'jenis'=>$request['jenis'],
            'keterangan'=>$request['keterangan'],
        ]);
        return response()->json($data, 200);
    }

    public function nomorJurnal(){
        $data = Jurnal::groupBy('nomor_jurnal')->get(); // CEK DATA NOMOR JURNAL DENGAN GROUPING
        $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
        $output = $data->count(); // DATA DIHITUNG 
        $output++; // DATA DITAMBAH 1
        return response()->json($prefix.$output, 200);
    }
}
