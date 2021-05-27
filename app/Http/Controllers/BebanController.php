<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;
use App\Models\Akun;
use App\Models\Beban;

class BebanController extends Controller
{
    public function operasional($cabang, $year)
    {
        $headerSaldo = 0;
        if($year == null) {
            $year = 'Y';
        }
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $master = Akun::where('id',42)->first();
        $komponen = Akun::where('deleted_at')
        ->where('komponen', $master->kode_akun)
        ->whereIn('cabang_id', array(0, $cabang))
        ->get();
        $detail = Beban::where('cabang_id', $cabang)->whereBetween('created_at',[$dateawal, $dateakhir])->get();
        $master->komponen = $komponen;
        $master->nama_jenis = 'BEBAN';
        $master->detail = $detail;

        foreach ($komponen as $key => $value) {
            $saldo = $this->cekSaldo($value->id,$value->saldo_normal, $year, $cabang);
            $value->saldo = $saldo;
            $headerSaldo += $saldo;
        }

        $master->saldo = $headerSaldo;

        $output = $master;

        return response()->json($output, 200);
    }

    
    function cekSaldo($id, $sifat, $year, $cabang){
        
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $saldo = 0;
        $data = DB::table('master_jurnal')
        ->where('master_akun_id','=',$id)    
        ->where('master_jurnal.deleted_at')
        ->where('master_jurnal.cabang_id', $cabang)
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

    public function store(Request $payload){
        $output;
        $master = Beban::create([
            'master_akun_id'=>$payload->master_akun_id,
            'nominal'=>$payload->nominal,
            'catatan'=>$payload->catatan,
            'user_id'=>$payload->user_id,
            'cabang_id'=>$payload->cabang_id,
        ]);

        $output['master'] = $master;
        if($master->id){
            $akun = Akun::where('id',$payload->master_akun_id)->first();

            $data = Jurnal::groupBy('nomor_jurnal')->get(); // CEK DATA NOMOR JURNAL DENGAN GROUPING
            $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
            $nomorJurnal = $data->count(); // DATA DIHITUNG 
            $nomorJurnal++;
            $nomorJurnal = $prefix.$nomorJurnal; // DATA DITAMBAH 1

            $nomor_akun[] = (object) array('master_akun_id' => $payload->master_akun_id, 'jenis' => 'DEBIT');
            $nomor_akun[] = (object) array('master_akun_id' => '4', 'jenis' => 'KREDIT');

            foreach ($nomor_akun as $key => $value) {
                $jurnal = Jurnal::create([
                    'reff'=> $nomorJurnal,
                    'nomor_jurnal'=>$nomorJurnal,
                    'master_akun_id'=>$value->master_akun_id,
                    'nominal'=>$payload->nominal,
                    'jenis'=>$value->jenis,
                    'keterangan'=> $akun->nama.' - '. $value->catatan,
                    'user_id'=>$payload->user_id,
                    'cabang_id'=>$payload->cabang_id,
                ]);
                $output['jurnal'][] = $jurnal;
            }
            $master->nomor_jurnal = $nomorJurnal;
            $master->save();
        }

        return response()->json($output, 200);
    }
}
