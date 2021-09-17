<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;
use App\Models\Akun;

class KasController extends Controller
{
    public function store(Request $payload){

        $data = Jurnal::groupBy('nomor_jurnal')->get(); // CEK DATA NOMOR JURNAL DENGAN GROUPING
        $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
        $nomorJurnal = $data->count(); // DATA DIHITUNG 
        $nomorJurnal++;
        $nomorJurnal = $prefix.$nomorJurnal; // DATA DITAMBAH 1

            $debit = Jurnal::create([
                'reff'=> $nomorJurnal,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>$payload->kode_akun_id,
                'nominal'=>$payload->jumlah,
                'jenis'=> $payload->jenis == 'DEBIT' ? 'DEBIT' : 'KREDIT',
                'keterangan'=> $payload->jenis == 'DEBIT' ? 'TARIK KAS - ' : 'SETOR KAS - '. $payload->catatan,
                'user_id'=>$payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
            ]);
            $output[] = $debit;
            $kredit = Jurnal::create([
                'reff'=> $nomorJurnal,
                'nomor_jurnal'=>$nomorJurnal,
                'master_akun_id'=>$payload->lawan_akun_id['id'],
                'nominal'=>$payload->jumlah,
                'jenis'=> $payload->jenis == 'DEBIT' ? 'KREDIT' : 'DEBIT',
                'keterangan'=> $payload->jenis == 'DEBIT' ? 'TARIK KAS - ' : 'SETOR KAS - '. $payload->catatan,
                'user_id'=>$payload->user['id'],
                'cabang_id'=>$payload->user['cabang_id'],
            ]);
            $output[] = $kredit;


        return $nomorJurnal;

    }

    public function saldoKas($id, $dd, $ddd){

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));

        if($ddd == "null"){
            $dateakhir = date("Y-m-d 23:59:59", strtotime($dateawal));
        }

        $output = DB::table('master_jurnal')
        ->select('master_jurnal.*', 'master_akun.nama as nama_akun','master_akun.kode_akun as kode_akun')
        ->join('master_akun','master_jurnal.master_akun_id','=','master_akun.id')
        ->where('master_jurnal.deleted_at')    
        ->where('master_jurnal.master_akun_id','=', $id)
        ->whereBetween('master_jurnal.created_at',[$dateawal, $dateakhir])
        ->get();
        
        $saldo = 0;
        foreach ($output as $key => $value) {
            if($value->jenis === "DEBIT"){
                $saldo += $value->nominal;
            }else{
                $saldo -= $value->nominal;
            }
        }

        return response()->json($saldo, 200);  

    }
}
