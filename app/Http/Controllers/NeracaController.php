<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;
use App\Models\Akun;

class NeracaController extends Controller
{
    public function index($year = null){

        if($year == null) {
            $year = 'Y';
        }
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $jenis = ['1','2','3'];
        $output = [];

        foreach ($jenis as $key => $value) {
            $nama = 'assets';
            if($value == '3'){
                $nama = 'equity';
            }
            if($value == '2'){
                $nama = 'liabilities';
            }
            $headsaldo = 0;
            $dd = DB::table('master_akun')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('komponen','=',null)
            ->where('jenis_akun_id','=',$value)    
            ->orderBy('kode_akun','ASC')
            ->get();
    
            foreach ($dd as $key => $sub) {
                $saldo = 0;
                $komponen = DB::table('master_akun')
                    ->where('komponen','=', $sub->kode_akun)
                    ->where('deleted_at')
                    ->orderBy('kode_akun','ASC');
                if($komponen->count() > 0){
                    $komponen = $komponen->get();
                    foreach ($komponen as $key => $komp) {
                        $komp->saldo = $this->cekSaldo($komp->id, $komp->saldo_normal, $year);
                        $saldo +=$komp->saldo;
                    }
                }else{
                    $komponen = null;
                }

                $sub->komponen = $komponen;
                $subsaldo = $this->cekSaldo($sub->id,$sub->saldo_normal, $year);
                $sub->saldo = $saldo + $subsaldo;
    
                $headsaldo += $sub->saldo;
            }

            $output[$nama] = $dd;
        }

        $laba = $this->labaditahan($year);
        $data = [
            'nama' => 'yaaa',
            'saldo' => 1000
        ];

        $output['equity'][]= $laba;
        return response()->json($output, 200);
    }

    public function labaditahan($year) {
        if($year == null) {
            $year = 'Y';
        }
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $jenis = ['4','5'];
        $output = [];

        foreach ($jenis as $key => $value) {
            $headsaldo = 0;
            $dd = DB::table('master_akun')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('komponen','=',null)
            ->where('jenis_akun_id','=',$value)    
            ->orderBy('kode_akun','ASC')
            ->get();
    
            foreach ($dd as $key => $sub) {
                $saldo = 0;
                $komponen = DB::table('master_akun')
                    ->where('komponen','=', $sub->kode_akun)
                    ->where('deleted_at')
                    ->orderBy('kode_akun','ASC');
                if($komponen->count() > 0){
                    $komponen = $komponen->get();
                    foreach ($komponen as $key => $komp) {
                        $komp->saldo = $this->cekSaldo($komp->id,  $komp->saldo_normal,  $year);
                        $saldo +=$komp->saldo;
                    }
                }else{
                    $komponen = null;
                }

                $sub->komponen = $komponen;
                $subsaldo = $this->cekSaldo($sub->id, $sub->saldo_normal, $year);
                $sub->saldo = $saldo + $subsaldo;
    
                $headsaldo += $sub->saldo;
            }

            $output[$value] = $dd;
        }

        $pendapatan = 0;
        $beban = 0;

        foreach ($output[4] as $key => $pend) {
            $pendapatan += $pend->saldo;
        }

        foreach ($output[5] as $key => $beb) {
            $beban += $beb->saldo;
        }

        return [
            'nama' => 'RETAINED EARNING',
            'saldo' => $pendapatan - $beban
        ];

    }


    function cekSaldo($id, $sifat, $year){
        
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $saldo = 0;
        $data = DB::table('master_jurnal')
        ->where('master_akun_id','=',$id)    
        ->where('master_jurnal.deleted_at')
        ->where('master_jurnal.created_at','>',$dateawal)    
        ->where('master_jurnal.created_at','<',$dateakhir)  
        ->get();

        if($sifat == 'DEBIT'){
            foreach ($data as $key => $value) {
                if($value->jenis === "DEBIT"){
                    $saldo += $value->nominal;
                }else{
                    $saldo -= $value->nominal;
                }
            }
        }else{
            foreach ($data as $key => $value) {
                if($value->jenis === "KREDIT"){
                    $saldo += $value->nominal;
                }else{
                    $saldo -= $value->nominal;
                }
            }
        }

        return $saldo;
    }
}
