<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;
use App\Models\Akun;

class LabaRugiController extends Controller
{
    public function index($year)
    {
        if($year == null) {
            $year = 'Y';
        }
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $jenis = ['4','5'];
        $output = [];

        foreach ($jenis as $key => $value) {
            $nama = 'pendapatan';
            if($value == '5'){
                $nama = 'beban';
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
            
                $subsaldo = $this->cekSaldo($sub->id,$sub->saldo_normal, $year);
                $sub->saldo = $saldo + $subsaldo;
                $headsaldo += $sub->saldo;
            }

            $output[$nama] = $dd;
        }
        $output['pendapatan'][] = [
            'nama' => 'TOTAL PENDAPATAN',
            'align' => 1,
            'saldo' => $output['pendapatan'] - $output['pendapatan']['1']
        ];

        return response()->json($output, 200);
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
