<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;
use App\Models\Akun;

class LabaRugiController extends Controller
{
    public function index(Request $payload)
    {
        $year = $payload->input('tahun');
        $month = $payload->input('bulan');
        $day = $payload->input('hari');

        $cabang_id = $payload->input('cabang_id');
        
        // if($year == null) {
        //     $year = 'Y';
        // }
        $jenis = ['4','5'];
        $output = [];
        $totalPendapatan = 0;
        $totalBeban = 0;

        foreach ($jenis as $key => $value) {
            $nama = 'pendapatan';
            if($value == '5'){
                $nama = 'beban';
            }

            $dd = DB::table('master_akun')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('komponen','=',null)
            ->where('jenis_akun_id','=',$value)    
            ->orderBy('kode_akun','ASC')
            ->get();
    
            foreach ($dd as $key => $sub) {
                $headerSaldo = 0;
                $komponen = Akun::where('deleted_at')
                ->where('komponen', $sub->kode_akun)
                ->whereIn('cabang_id', array(0, $cabang_id))
                ->get();
                $sub->komponen = $komponen;

                foreach ($komponen as $key => $value) {
                    $saldo = $this->cekSaldo($value->id,$value->saldo_normal, $cabang_id,$year, $month, $day);
                    $value->saldo = $saldo;
                    $headerSaldo += $saldo;
                }
                $sub->saldo = $headerSaldo;

                $subsaldo = $this->cekSaldo($sub->id,$sub->saldo_normal, $cabang_id,$year, $month, $day);
                $sub->saldo = $headerSaldo + $subsaldo;
            }

            $output[$nama] = $dd;
        }

        foreach ($output['pendapatan'] as $key => $pendapatan) {
            if($pendapatan->saldo_normal == 'DEBIT'){
                $totalPendapatan -= $pendapatan->saldo;
            }else{
                $totalPendapatan += $pendapatan->saldo;
            }
        }
        $output['pendapatan'][] = [
            'nama' => 'TOTAL PENDAPATAN',
            'align' => 1,
            'total' => true,
            'saldo' => $totalPendapatan
        ];

        foreach ($output['beban'] as $key => $beban) {
            if($beban->saldo_normal == 'DEBIT'){
                $totalBeban += $beban->saldo;
            }else{
                $totalBeban -= $beban->saldo;
            }
        }
        $output['beban'][] = [
            'nama' => 'TOTAL BEBAN',
            'align' => 1,
            'total' => true,
            'saldo' => $totalBeban
        ];

        $output['labaRugi'] = $totalPendapatan - $totalBeban;

        return response()->json($output, 200);
    }

    
    function cekSaldo($id, $sifat, $cabang_id, $year=null, $month=null, $day=null){
        
        if($year != null){
            $dateawal = date($year.'-01-01 00:00:00');
            $dateakhir = date($year.'-12-31 23:59:59');
        }
        if($month != null){
            $dateawal =  date('Y-'.$month.'-01 00:00:00');
            $dateakhir = date('Y-'.$month.'-31 23:59:59');
        }
        if($day != null){
            $dateawal = date('Y-m-d 00:00:00', strtotime($day));
            $dateakhir = date('Y-m-d 23:59:59', strtotime($day));
        }

        $saldo = 0;
        $data = DB::table('master_jurnal')
        ->where('master_akun_id','=',$id)    
        ->where('master_jurnal.cabang_id', $cabang_id)
        ->where('master_jurnal.created_at','>=',$dateawal)    
        ->where('master_jurnal.created_at','<=',$dateakhir)    
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
