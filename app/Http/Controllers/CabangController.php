<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

use App\Models\Performance;
use App\Models\Akun;
use App\Models\Jurnal;
use Carbon\Carbon;

class CabangController extends Controller
{
    public function allPerformance(Request $payload){
        $year = $payload->input('tahun');
        $month = $payload->input('bulan');
        $day = $payload->input('hari');

        $master = Http::get(mainApi().'cabang/')->json();
        $result = [];
        foreach ($master as $key => $cabang) {
            $jenis = ['4','5']; // 4 PENDAPATAN 5 BEBAN
            $totalBeban = 0;
            $totalPendapatan = 0;
            $output = [];
            foreach ($jenis as $key => $jenis) {
                $nama = 'pendapatan';
                if($jenis == '5'){
                    $nama = 'beban';
                }
    
                $dd = DB::table('master_akun')
                ->where('deleted_at')
                ->where('header','=',0)
                ->where('komponen','=',null)
                ->where('jenis_akun_id','=',$jenis)    
                ->orderBy('kode_akun','ASC')
                ->get();
        
                foreach ($dd as $key => $sub) {
                    $headerSaldo = 0;
                    $komponen = Akun::where('deleted_at')
                    ->where('komponen', $sub->kode_akun)
                    ->whereIn('cabang_id', array(0, $cabang['id']))
                    ->get();
                    $sub->komponen = $komponen;
    
                    foreach ($komponen as $key => $value) {
                        $saldo = cekSaldo($value->id,$value->saldo_normal, $cabang['id'],$year, $month,$day);
                        $value->saldo = $saldo;
                        $headerSaldo += $saldo;
                    }
                    $sub->saldo = $headerSaldo;
    
                    $subsaldo = cekSaldo($sub->id,$sub->saldo_normal, $cabang['id'],$year, $month,$day);
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

            foreach ($output['beban'] as $key => $beban) {
                if($beban->saldo_normal == 'DEBIT'){
                    $totalBeban += $beban->saldo;
                }else{
                    $totalBeban -= $beban->saldo;
                }
            }

            $cabang['setoran'] =  Http::get(mainApi().'setor/pelaporan?cabang_id='.$cabang['id'].'&tahun='.$year.'&bulan='.$month.'&hari='.$day)->json();
            $cabang['penjualan'] = $output['pendapatan'][0]->saldo - $output['pendapatan'][2]->saldo;
            $cabang['pendapatan_lainnya'] = $output['pendapatan'][1]->saldo;
            $cabang['hpp'] = $output['pendapatan'][4]->saldo;
            $cabang['total_pendapatan'] = $totalPendapatan;
            $cabang['total_beban'] =  $totalBeban;
            $cabang['laba_rugi'] = $totalPendapatan - $totalBeban;
            $cabang['gross_margin'] = $output['pendapatan'][0]->saldo == 0 ? 0 : round((($output['pendapatan'][0]->saldo - $output['pendapatan'][4]->saldo) / $output['pendapatan'][0]->saldo) * 100, 2);

            $result[]= $cabang;
        }
        return response()->json($result, 200);
    }

    public function satuanPerformance(Request $payload){

        $cabang_id = $payload->input('cabang_id');
        $tanggalAwal = $payload->input('awal');
        $tanggalAkhir = $payload->input('akhir');
        $result = [];
        $to_date = Carbon::createFromFormat('Y-m-d', $tanggalAwal);
        $from_date = Carbon::createFromFormat('Y-m-d', $tanggalAkhir);
        $jumlah_hari = $to_date->diffInDays($from_date);

        for ($i=0; $i <= $jumlah_hari ; $i++) { 
            $newDate = Carbon::createFromFormat('Y-m-d', $tanggalAwal)->addDays($i);
            $jenis = ['4','5']; // 4 PENDAPATAN 5 BEBAN
            $output = [];
            $totalBeban = 0;
            $totalPendapatan = 0;
            foreach ($jenis as $key => $jenis) {
                $nama = 'pendapatan';
                if($jenis == '5'){
                    $nama = 'beban';
                }
    
                $dd = DB::table('master_akun')
                ->where('deleted_at')
                ->where('header','=',0)
                ->where('komponen','=',null)
                ->where('jenis_akun_id','=',$jenis)    
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
                        $saldo = cekSaldo($value->id,$value->saldo_normal, $cabang_id,'', '',$newDate);
                        $value->saldo = $saldo;
                        $headerSaldo += $saldo;
                    }
                    $sub->saldo = $headerSaldo;
    
                    $subsaldo = cekSaldo($sub->id,$sub->saldo_normal, $cabang_id,'', '',$newDate);
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
    
            foreach ($output['beban'] as $key => $beban) {
                if($beban->saldo_normal == 'DEBIT'){
                    $totalBeban += $beban->saldo;
                }else{
                    $totalBeban -= $beban->saldo;
                }
            }
            $cabang['setoran'] =  Http::get(mainApi().'setor/pelaporan?cabang_id='.$cabang_id.'&tahun=&bulan=&hari='.$newDate)->json();
            $cabang['tanggal'] = $newDate;
            $cabang['penjualan'] = $output['pendapatan'][0]->saldo - $output['pendapatan'][2]->saldo;
            $cabang['pendapatan_lainnya'] = $output['pendapatan'][1]->saldo;
            $cabang['hpp'] = $output['pendapatan'][4]->saldo;
            $cabang['total_pendapatan'] = $totalPendapatan;
            $cabang['total_beban'] =  $totalBeban;
            $cabang['laba_rugi'] = $totalPendapatan - $totalBeban;
            $cabang['gross_margin'] = $output['pendapatan'][0]->saldo == 0 ? 0 : round((($output['pendapatan'][0]->saldo - $output['pendapatan'][4]->saldo) / $output['pendapatan'][0]->saldo) * 100, 2);
            $result[] = $cabang;
         }
        return $result;



    }

    public function kas(Request $payload){
        $cabang_id = $payload->input('cabang_id');

        $dateawal = date('Y-01-01 00:00:00');
        $dateakhir = date('Y-m-d 23:59:59');

        $master = Akun::whereIn('komponen',['1.1.2','1.1.3'])->whereIn('cabang_id', array(0, $cabang_id))->get();
        
        foreach ($master as $key => $sub) {   
            $kredit = Jurnal::where('master_akun_id',$sub->id)->where('cabang_id',$cabang_id)->where('jenis','KREDIT')
            ->where('master_jurnal.created_at','>=',$dateawal) 
            ->where('master_jurnal.created_at','<=',$dateakhir)
            ->sum('nominal');

            $debit = Jurnal::where('master_akun_id',$sub->id)->where('cabang_id',$cabang_id)->where('jenis','DEBIT')
            ->where('master_jurnal.created_at','>=',$dateawal)    
            ->where('master_jurnal.created_at','<=',$dateakhir)    
            ->sum('nominal');
            $sub->saldo = $debit - $kredit;
        }
            
        return response()->json($master, 200);
    }
}
