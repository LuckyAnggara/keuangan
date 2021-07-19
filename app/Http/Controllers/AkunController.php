<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Akun;

class AkunController extends Controller
{
    public function index($year = null){
        $header = DB::table('jenis_akun')
        ->select('jenis_akun.*')
        ->where('deleted_at')    
        ->get();


        foreach ($header as $key => $value) {
            $headerSaldo = 0;
            $subheader = DB::table('master_akun')
            ->select('master_akun.*')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('komponen','=',null)    
            ->where('jenis_akun_id','=',$value->id)    
            ->orderBy('kode_akun','ASC')
            ->get();

            foreach ($subheader as $key => $sub) {
                $saldo = 0;
                $komponen = DB::table('master_akun')
                    ->where('komponen','=', $sub->kode_akun)
                    ->where('deleted_at')
                    ->orderBy('kode_akun','ASC')
                    ->get();
                foreach ($komponen as $key => $komp) {
                        $komp->saldo = $this->cekSaldo($komp->id,$komp->saldo_normal, $year);
                        $saldo +=$komp->saldo;
                }
                $sub->komponen = $komponen;
                $subHeaderSaldo =$this->cekSaldo($sub->id,$sub->saldo_normal, $year);
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

        return response()->json($output, 200);
    }

    function cekSaldo($id,$sifat, $year){
        
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

    public function store(Request $payload){
        $akun = Akun::create([
            'jenis_akun_id' => $payload->jenis_akun_id,
            'kode_akun' => $payload->kode_akun,
            'nama' => $payload->nama,
            'saldo_normal' => $payload->saldo_normal,
            'komponen' => $payload->komponen,
            'cabang_id' => $payload->cabang_id,
        ]);
        if($akun->id){
            $code = 200;
        }else{
            $code = 404;
        }
        return response()->json($akun, $code);

    }
}
