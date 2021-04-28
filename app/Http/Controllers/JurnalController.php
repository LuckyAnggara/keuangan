<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class JurnalController extends Controller
{
    public function index($dd, $ddd){

        $dateawal = date("Y-m-d 00:00:01", strtotime($dd));
        $dateakhir = date("Y-m-d 23:59:59", strtotime($ddd));

        $master = DB::table('master_jurnal')
        ->select('master_jurnal.*', 'master_akun.nama as nama_akun','master_akun.kode_akun as kode_akun')
        ->join('master_akun','master_jurnal.master_akun_id','=','master_akun.id')
        ->where('master_jurnal.created_at','>',$dateawal)    
        ->where('master_jurnal.created_at','<',$dateakhir)  
        ->where('master_jurnal.deleted_at')    
        ->get();
        
        return response()->json($master, 200);
    }

    public function store(Request $request){

        if($request->jurnal){  // UNTUK BATCH
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
                    'created_at'=> date("Y-m-d h:i:s", strtotime($request->tanggalTransaksi)),
                ]);
                $output[] = $data;
            }
    
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
            ]);
            return response()->json($data, 200);
        }

       
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
                'created_at'=> date("Y-m-d h:i:s", strtotime($request->tanggalTransaksi)),
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
}
