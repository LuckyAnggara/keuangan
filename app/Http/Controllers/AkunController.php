<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Akun;

class AkunController extends Controller
{
    public function index(){
        $header = DB::table('jenis_akun')
        ->select('jenis_akun.*')
        ->where('deleted_at')    
        ->get();


        foreach ($header as $key => $value) {
            $saldo = 0;
            $subheader = DB::table('master_akun')
            ->select('master_akun.*')
            ->where('deleted_at')
            ->where('header','=',0)
            ->where('jenis_akun_id','=',$value->id)    
            ->orderBy('kode_akun','ASC')
            ->get();

            foreach ($subheader as $key => $sub) {
                $sub->saldo = $this->cekSaldo($sub->id);
                $saldo +=$sub->saldo;
            }

            $value->saldo = $saldo;
            $value->subheader = $subheader;
            $output[] = $value;
        }

        return response()->json($output, 200);
    }

    function cekSaldo($id){
        $saldo = 0;
        $data = DB::table('master_jurnal')
        ->where('master_akun_id','=',$id)    
        ->where('master_jurnal.deleted_at')
        ->get();

        foreach ($data as $key => $value) {
            if($value->jenis === "DEBIT"){
                $saldo += $value->nominal;
            }else{
                $saldo -= $value->nominal;
            }
        }
        return $saldo;
    }

}
