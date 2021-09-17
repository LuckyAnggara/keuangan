<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class JurnalController extends Controller
{

    
    public function index($cabang,$dd, $ddd){

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));

        $master = DB::table('master_jurnal')
        ->select('master_jurnal.*', 'master_akun.nama as nama_akun','master_akun.kode_akun as kode_akun')
        ->join('master_akun','master_jurnal.master_akun_id','=','master_akun.id')
        ->where('master_jurnal.cabang_id','=',$cabang)    
        ->where('master_jurnal.created_at','>',$dateawal)    
        ->where('master_jurnal.created_at','<',$dateakhir)  
        ->where('master_jurnal.deleted_at')    
        ->get();
        
        return response()->json($master, 200);
    }

    public function geJurnalByNomorJurnal($nomorJurnal){

        $master = DB::table('master_jurnal')
        ->select('master_jurnal.*', 'master_akun.nama as nama_akun','master_akun.kode_akun as kode_akun')
        ->join('master_akun','master_jurnal.master_akun_id','=','master_akun.id')
        ->where('master_jurnal.deleted_at')    
        ->where('master_jurnal.nomor_jurnal','=', $nomorJurnal)    
        ->get();
        
        return response()->json($master, 200);
    }

    public function store(Request $request){
        $nomorJurnal = $this->nomorJurnal();
        if($request->jurnal){  // UNTUK BATCH
            $output = [];
            foreach ($request->jurnal as $key => $value) {
                $data = Jurnal::create([
                    'reff'=>$request->nomor_jurnal != '' ? $request->nomor_jurnal : $nomorJurnal->original,
                    'nomor_jurnal'=>$request->nomor_jurnal != '' ? $request->nomor_jurnal : $nomorJurnal->original,
                    'master_akun_id'=>$value['akunId'],
                    'nominal'=>$value['saldo'],
                    'jenis'=>$value['namaJenis'],
                    'keterangan'=> $value['catatan']=='' ? $request->catatan : $value['catatan'],
                    'created_at'=> date("Y-m-d 00:00:00", strtotime($request->tanggalTransaksi)),
                    'user_id'=>$request['user_id'],
                    'cabang_id'=>$request['cabang_id'],
                ]);
                $output[] = $data;
            }
            $output['nomor_jurnal'] = $request->nomor_jurnal != '' ? $request->nomor_jurnal : $nomorJurnal->original;
            return response()->json($output, 200);        
        } else{
            // UNTUK SATUAN
            $data = Jurnal::create([
                'reff'=>$request['reff'],
                'nomor_jurnal'=>$request['nomor_jurnal'],
                'master_akun_id'=>$request['master_akun_id'],
                'nominal'=>$request['nominal'],
                'jenis'=>$request['jenis'],
                'keterangan'=>$request['keterangan'],
                'user_id'=>$request['user_id'],
                'cabang_id'=>$request['cabang_id'],
            ]);
            
            return response()->json($data, 200);
        }
    }

    public function destroy($nomorJurnal){
        $jurnal = Jurnal::where('nomor_jurnal',$nomorJurnal)->get();

        foreach ($jurnal as $key => $value) {
            $dd = Jurnal::findOrFail($value->id);
            $dd->delete();
        }

        return response()->json($jurnal, 200);
    }

    public function storeBatch(Request $request){

        $output = [];
        $nomorJurnal = $this->nomorJurnal();

        foreach ($request->jurnal as $key => $value) {
            $data = Jurnal::create([
                'reff'=>$nomorJurnal->original,
                'nomor_jurnal'=>$nomorJurnal->original,
                'master_akun_id'=>$value['akunId'],
                'nominal'=>$value['saldo'],
                'jenis'=>$value['namaJenis'],
                'keterangan'=>$request->catatan,
                'created_at'=> date("Y-m-d 00:00:00", strtotime($request->tanggalTransaksi)),
            ]);
            $output[] = $data;
        }

        return response()->json($output, 200);
    }

    public function nomorJurnal(){
        $data = Jurnal::groupBy('nomor_jurnal')->get(); // CEK DATA NOMOR JURNAL DENGAN GROUPING
        $prefix = date("ymd"); // PREFIX AWALAN PAKE TANGGAL TAHUN-BULAN-TANGGAL (EX 210422)
        $output = $data->count(); // DATA DIHITUNG 
        $output++; // DATA DITAMBAH 1
        return response()->json($prefix.$output, 200);
    }

    public function retur($nomorJurnal){
        $output = [];
        $jurnal = Jurnal::where('nomor_jurnal', $nomorJurnal)->get();

        foreach ($jurnal as $key => $value) {
            $jenis = 'DEBIT';
            if($value->jenis == 'DEBIT'){
                $jenis = 'KREDIT';
            }

            if($value->master_akun_id == 32){
                // JIKA RETUR . AKUN PENJUALAN TIDAK DI DEBIT. TAPI PROSES DEBIT DI AKUN RETUR PENJUALAN
                $dd = Jurnal::create([
                    'reff'=>$value->reff,
                    'nomor_jurnal'=>$value->nomor_jurnal,
                    'master_akun_id'=>34,
                    'nominal'=>$value->nominal,
                    'jenis'=>$jenis,
                    'keterangan'=>'RETUR-'.$value->keterangan,
                    'user_id'=>$value->user_id,
                    'cabang_id'=>$value->cabang_id,
                ]);
            }else{
                $dd = Jurnal::create([
                    'reff'=>$value->reff,
                    'nomor_jurnal'=>$value->nomor_jurnal,
                    'master_akun_id'=>$value->master_akun_id,
                    'nominal'=>$value->nominal,
                    'jenis'=>$jenis,
                    'keterangan'=>'RETUR-'.$value->keterangan,
                    'user_id'=>$value->user_id,
                    'cabang_id'=>$value->cabang_id,
                ]);
            }
            $output[] = $dd;
        }

        return response()->json($output, 200);
    }
}
