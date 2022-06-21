<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class PenutupTahunController extends Controller
{
    public function proses(Request $payload){

        $name = [];
        $cabang_id = $payload->input('cabang_id');
        $year = $payload->input('tahun');
        $data = $this->cekSemuaAkun($cabang_id, $year);

        foreach ($data as $key => $header) {
            if($header->saldo != 0){
                foreach ($header->subheader as $key => $subheader) {
                    if($subheader->saldo !=0){
                        $name[] = $subheader->nama. ' - ' . $subheader->saldo;
                    }
                }
            }
        }
        return $name;
    
    }

    public function cekSemuaAkun($cabang_id, $year){
               
        $header = DB::table('jenis_akun')
        ->select('jenis_akun.*')
   
        ->where('deleted_at')    
        ->get();

        if($year == null){
            $year = date('y');
        }


        foreach ($header as $key => $value) {
            $headerSaldo = 0;
            $subheader = DB::table('master_akun')
            ->select('master_akun.*')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('komponen','=',null)    
            ->where('jenis_akun_id','=',$value->id)
            ->having('cabang_id', 0)
            ->orHaving('cabang_id', $cabang_id)
            ->orderBy('kode_akun','ASC')
            ->get();

            foreach ($subheader as $key => $sub) {
                $saldo = 0;
                $komponen = DB::table('master_akun')
                    ->where('komponen','=', $sub->kode_akun)
                    ->where('deleted_at')
                    ->having('cabang_id', 0)
                    ->orHaving('cabang_id', $cabang_id)
                    ->orderBy('kode_akun','ASC')
                    ->get();
                foreach ($komponen as $key => $komp) {
                        $komp->saldo = $this->cekSaldo($komp->id,$komp->saldo_normal, $year, $cabang_id);
                        $saldo +=$komp->saldo;
                }
                $sub->komponen = $komponen;
                $subHeaderSaldo =$this->cekSaldo($sub->id,$sub->saldo_normal, $year, $cabang_id);
                $sub->saldo = $saldo + $subHeaderSaldo;

                if($sub->saldo_normal == "DEBIT"){
                    $headerSaldo += $sub->saldo;
                }else{
                    $headerSaldo -= $sub->saldo;
                }
                $headerSaldo = abs($headerSaldo);
            }

            $value->saldo = $headerSaldo;
            $value->subheader = $subheader;
            $output[] = $value;
        }

        return $output;
    }
    
    function cekSaldo($id,$sifat, $year, $cabang_id){
        
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $saldo = 0;
        $data = DB::table('master_jurnal')
        ->where('master_akun_id','=',$id)    
        ->where('master_jurnal.cabang_id', $cabang_id)
        ->where('master_jurnal.deleted_at')
        ->where('master_jurnal.created_at','>',$dateawal)    
        ->where('master_jurnal.created_at','<',$dateakhir)  
        ->get();

        if($sifat == 'DEBIT'){
            foreach ($data as $key => $value) {
                if($value->jenis == "DEBIT"){
                    $saldo += $value->nominal;
                }else{
                    $saldo -= $value->nominal;
                }
            }
        }else{
            foreach ($data as $key => $value) {
                if($value->jenis == "KREDIT"){
                    $saldo += $value->nominal;
                }else{
                    $saldo -= $value->nominal;
                }
            }
        }

        return $saldo;
    }
    
   
}
