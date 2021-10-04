<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Akun;

class AkunController extends Controller
{
    public function show($id){
       $output=  Akun::findOrFail($id);
       return response()->json($output, 200);
    }
    
    public function index(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $year = $payload->input('tahun');
        
        $header = DB::table('jenis_akun')
        ->select('jenis_akun.*')
   
        ->where('deleted_at')    
        ->get();

        if($year == null){
            $year = date('y');
        }


        foreach ($header as $key => $value) {
            $headerSaldo = 0;
            $subheader = DB::table('master_akun')
            ->select('master_akun.*')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('komponen','=',null)    
            ->where('jenis_akun_id','=',$value->id)
            ->having('cabang_id', 0)
            ->orHaving('cabang_id', $cabang_id)
            ->orderBy('kode_akun','ASC')
            ->get();

            foreach ($subheader as $key => $sub) {
                $saldo = 0;
                $komponen = DB::table('master_akun')
                    ->where('komponen','=', $sub->kode_akun)
                    ->where('deleted_at')
                    ->having('cabang_id', 0)
                    ->orHaving('cabang_id', $cabang_id)
                    ->orderBy('kode_akun','ASC')
                    ->get();
                foreach ($komponen as $key => $komp) {
                        $komp->saldo = $this->cekSaldo($komp->id,$komp->saldo_normal, $year, $cabang_id);
                        $saldo +=$komp->saldo;
                }
                $sub->komponen = $komponen;
                $subHeaderSaldo =$this->cekSaldo($sub->id,$sub->saldo_normal, $year, $cabang_id);
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

    function cekSaldo($id,$sifat, $year, $cabang_id){
        
        $dateawal = date($year.'-01-01 00:00:01');
        $dateakhir = date($year.'-12-31 23:59:59');

        $saldo = 0;
        $data = DB::table('master_jurnal')
        ->where('master_akun_id','=',$id)    
        ->where('master_jurnal.cabang_id', $cabang_id)
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
        $data = Akun::where('komponen',$payload->komponen)->get();
        $dd =  collect($data)->last();
        if($dd){
            $str = explode('-', $dd->kode_akun);
            $last_prefix = $str[1]+ 1;
        }else{
            $last_prefix = 1;
        }

        $akun = Akun::create([
            'jenis_akun_id' => $payload->jenis_akun_id,
            'kode_akun' => $payload->komponen !== null ? $payload->kode_akun.'-'.$last_prefix : $payload->kode_akun,
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

    
    function cekSaldoApi(Request $payload){

        $id = $payload->input('id');
        $tanggal = $payload->input('tanggal');
        $bulan = $payload->input('bulan');
        $cabang_id = $payload->input('cabang_id');
        
        if($bulan == null){
            if($tanggal==null){ // JIKA GA DI ISI ARTINYA DATA PALING UPDATE
                $tanggal_akhir = date('Y-m-d'); 
            }else{
                $tanggal_akhir = strtotime('Y-m-d', $tanggal);
            }
        }else{
            $year = date('Y');
            $last_day = cal_days_in_month(CAL_GREGORIAN, $bulan, $year);
            $tanggal_akhir = date('Y-'.$bulan.'-'.$last_day);
        }
        // return $tanggal_akhir;

        $master = Akun::findOrFail($id);   
        
        $komponen = DB::table('master_akun')
        ->where('komponen','=', $master->kode_akun)
        ->where('deleted_at')
        ->orderBy('kode_akun','ASC')
        ->get();

        // return $komponen;

        $saldo = 0;
        $data = DB::table('master_jurnal')
                ->where('master_akun_id','=',$master->id)    
                ->where('cabang_id',$cabang_id == 0 ? '!=' : '=',$cabang_id)    
                ->whereDate('created_at','<=',$tanggal_akhir)  
                ->get();

                // return $tanggal_akhir;

                if($master->saldo_normal == 'DEBIT'){
                    foreach ($data as $key => $val) {
                        if($val->jenis == "DEBIT"){
                            $saldo += $val->nominal;
                        }else{
                            $saldo -= $val->nominal;
                        }
                    }
                }else{
                    foreach ($data as $key => $val) {
                        if($val->jenis == "KREDIT"){
                            $saldo += $val->nominal;
                        }else{
                            $saldo -= $val->nominal;
                        }
                    }
                }
        // return $komponen;
                $bb =[];
        if(count($komponen) > 0){
            foreach ($komponen as $key => $value) {
                $komponen_saldo = 0;
                $data = DB::table('master_jurnal')
                ->where('master_akun_id','=',$value->id)    
                ->where('cabang_id',$cabang_id == 0 ? '!=' : '=',$cabang_id)    
                ->whereDate('created_at','<=',$tanggal_akhir)  
                ->get();
                $bb[] = $data;
                // return $tanggal_akhir;

                if($value->saldo_normal == 'DEBIT'){
                    foreach ($data as $key => $val) {
                        if($val->jenis == "DEBIT"){
                            $komponen_saldo += $val->nominal;
                        }else{
                            $komponen_saldo -= $val->nominal;
                        }
                    }
                }else{
                    foreach ($data as $key => $val) {
                        if($val->jenis == "KREDIT"){
                            $komponen_saldo += $val->nominal;
                        }else{
                            $komponen_saldo -= $val->nominal;
                        }
                    }
                }
            $saldo =+ $komponen_saldo;
            }
        }else{
            return $saldo;
        }
// return $bb;
        

        return response()->json($saldo, 200);
    }

}
