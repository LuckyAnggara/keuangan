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
        $dateawal = date($year.'-01-01 00:00:00');
        $dateakhir = date($year.'-12-31 23:59:59');

        $master = Akun::where('id',42)->first();
        $komponen = Akun::where('deleted_at')
        ->where('komponen', $master->kode_akun)
        ->whereIn('cabang_id', array(0, $cabang))
        ->get();
        $detail = Beban::where('cabang_id', $cabang)
        // ->whereBetween('created_at',[$dateawal, $dateakhir])
        ->whereYear('created_at', $year)
        ->get()->sortBy([['created_at','asc']]);

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
        
        $dateawal = date($year.'-01-01 00:00:00');
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
        $output = [];
        $akun = Akun::find($payload->master_akun_id);
        $master = Beban::create([
            'master_akun_id'=>$payload->master_akun_id,
            'nominal'=>$payload->nominal,
            'catatan'=>$akun->nama.' - '. $payload->catatan,
            'user_id'=>$payload->user_id,
            'cabang_id'=>$payload->cabang_id,
            'created_at'=> date("Y-m-d h:i:s", strtotime($payload->tanggal)),
        ]);

        $output['master'] = $master;
        if($master->id){

            $data = Jurnal::groupBy('nomor_jurnal')->get(); // CEK DATA NOMOR JURNAL DENGAN GROUPING
            $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
            $nomorJurnal = $data->count(); // DATA DIHITUNG 
            $nomorJurnal++;
            $nomorJurnal = $prefix.$nomorJurnal; // DATA DITAMBAH 1

            $nomor_akun[] = (object) array('master_akun_id' => $payload->master_akun_id, 'jenis' => 'DEBIT');
            $nomor_akun[] = (object) array('master_akun_id' => $payload->kas['id'], 'jenis' => 'KREDIT');

            foreach ($nomor_akun as $key => $value) {
                $jurnal = Jurnal::create([
                    'reff'=> $nomorJurnal,
                    'nomor_jurnal'=>$nomorJurnal,
                    'master_akun_id'=>$value->master_akun_id,
                    'nominal'=>$payload->nominal,
                    'jenis'=>$value->jenis,
                    'keterangan'=> $akun->nama.' - '. $payload->catatan,
                    'user_id'=>$payload->user_id,
                    'cabang_id'=>$payload->cabang_id,
                ]);
                $output['jurnal'][] = $jurnal;
            }
            
            $master->nomor_jurnal = $nomorJurnal;
            $master->save();
            $output['akun'] = $akun;
        }

        return response()->json($output, 200);
    }

    public function destroy($id){
        $beban = Beban::find($id);
        $beban->delete();
        $beban->jurnal = $beban->nomor_jurnal;

        return response()->json($beban, 200);
    }

    public function gaji($cabang, $year)
    {
        $saldo = 0;
        if($year == null) {
            $year = 'Y';
        }
        $master = Akun::where('id',40)->first();

        $saldo = $this->cekSaldo($master->id,$master->saldo_normal, $year, $cabang);
        $master->saldo = $saldo;

        $output = $master;

        return response()->json($output, 200);
    }

    public function getBeban(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $year = $payload->input('tahun');
        $month = $payload->input('bulan');
        $day = $payload->input('hari');

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

        $master = Beban::where('cabang_id', $cabang_id)
        ->where('created_at','>=',$dateawal)    
        ->where('created_at','<=',$dateakhir) 
        ->get();

        return response()->json($master, 200);

    }
}
