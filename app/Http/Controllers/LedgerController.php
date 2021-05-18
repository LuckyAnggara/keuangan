<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class LedgerController extends Controller
{
    public function detail($cabang, $id, $dd, $ddd){

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));

        $output['ledger'] = [];
        $saldo = 0;

        $master = DB::table('master_akun')
        ->select('master_akun.*', 'jenis_akun.nama as nama_jenis')
        ->join('jenis_akun','master_akun.jenis_akun_id','=','jenis_akun.id')    
        ->where('master_akun.id','=',$id)    
        ->first();

        $ledger = DB::table('master_jurnal')
        ->select('master_jurnal.*', 'master_akun.nama as nama_akun','master_akun.kode_akun as kode_akun')
        ->join('master_akun','master_jurnal.master_akun_id','=','master_akun.id')    
        ->where('master_jurnal.master_akun_id','=',$id)    
        ->where('master_jurnal.cabang_id','=',$cabang)    
        // ->where('master_jurnal.created_at','>',$dateawal)    
        // ->where('master_jurnal.created_at','<',$dateakhir)    
        ->where('master_jurnal.deleted_at')
        ->orderBy('master_jurnal.created_at', 'asc')
        ->get();


        foreach ($ledger as $key => $value) {
            $data = $value;
            if($value->jenis === "DEBIT"){
                $saldo += $value->nominal;
                $data->saldo = $saldo; 
            }else{
                $saldo -= $value->nominal;
                $data->saldo = $saldo; 
            }
            $output['ledger'][] = $data;
        }
        $output['master'] = $master;
        $output['master']->saldo = $saldo;

        return response()->json($output, 200);  
    }
}
