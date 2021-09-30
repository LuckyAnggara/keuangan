<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class LedgerController extends Controller
{
    public function detail(Request $payload){

        $cabang_id = $payload->input('cabang_id');
        $id = $payload->input('akun_id');
        $dd = $payload->input('dd');
        $ddd = $payload->input('ddd');
        $dateawal = date("Y-m-d 00:00:00", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));


        if($ddd == "null"){
            $dateakhir = date("Y-m-d 23:59:59", strtotime($dateawal));
        }
        // return $dateakhir;

        $headerSaldo = 0;

        $master = DB::table('master_akun')
        ->select('master_akun.*', 'jenis_akun.nama as nama_jenis')
        ->join('jenis_akun','master_akun.jenis_akun_id','=','jenis_akun.id')    
        ->where('master_akun.id','=',$id)    
        ->first();

        $komponen = DB::table('master_akun')
                    ->where('komponen','=', $master->kode_akun)
                    ->where('cabang_id', $cabang_id)
                    ->where('deleted_at')
                    ->orderBy('kode_akun','ASC')
                    ->get();

        $master->komponen = $komponen;
        

        if(count($master->komponen) > 0){
            foreach ($komponen as $key => $value) {
                $saldo = 0;
                $ledger = DB::table('master_jurnal')
                ->select('master_jurnal.*')
                ->where('master_jurnal.master_akun_id','=',$value->id)    
                ->where('master_jurnal.cabang_id','=',$cabang_id)       
                ->where('master_jurnal.deleted_at')
                ->whereDate('master_jurnal.created_at','>=',$dateawal)    
                ->whereDate('master_jurnal.created_at','<=',$dateakhir)  
                ->orderBy('master_jurnal.id', 'asc')
                ->orderBy('master_jurnal.created_at', 'asc')
                ->get();

                $value->ledger = $ledger;

                    if($value->saldo_normal == 'DEBIT'){
                        foreach ($ledger as $key => $led) {
                            if($led->jenis === "DEBIT"){
                                $saldo += $led->nominal;
                            }else{
                                $saldo -= $led->nominal;
                            }
                            $led->saldo = $saldo;
                        }
                    }else{
                        foreach ($ledger as $key => $led) {
                            if($led->jenis === "KREDIT"){
                                $saldo += $led->nominal;
                            }else{
                                $saldo -= $led->nominal;
                            }
                            $led->saldo = $saldo;
                        }
                    }
                    $value->saldo = $saldo;
                    $headerSaldo += $saldo;
                }
        }else{
            $ledger = DB::table('master_jurnal')
            ->select('master_jurnal.*')
            ->where('master_jurnal.master_akun_id','=',$master->id)    
            ->where('master_jurnal.cabang_id','=',$cabang_id)       
            ->where('master_jurnal.deleted_at')
            ->whereDate('master_jurnal.created_at','>',$dateawal)    
            ->whereDate('master_jurnal.created_at','<',$dateakhir)  
            ->orderBy('master_jurnal.id', 'asc')
            ->orderBy('master_jurnal.created_at', 'asc')
            ->get();

            // return $ledger;

            if($master->saldo_normal == 'DEBIT'){
                foreach ($ledger as $key => $led) {
                    if($led->jenis === "DEBIT"){
                        $headerSaldo += $led->nominal;
                    }else{
                        $headerSaldo -= $led->nominal;
                    }
                    $led->saldo = $headerSaldo;
                   
                }
            }else{
                foreach ($ledger as $key => $led) {
                    if($led->jenis === "KREDIT"){
                        $headerSaldo += $led->nominal;
                    }else{
                        $headerSaldo -= $led->nominal;
                    }
                    $led->saldo = $headerSaldo;
                }
            }
            $master->saldo = $headerSaldo;
            $master->ledger = $ledger;
        }
          
        $output = $master;
        $output->saldo = $headerSaldo;

        return response()->json($output, 200);  
    }
}
