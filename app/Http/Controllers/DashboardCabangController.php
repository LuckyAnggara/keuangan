<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;
use App\Models\Akun;
use Carbon\Carbon;

class DashboardCabangController extends Controller
{
    public function omsetHarian(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $total = 0;

        $output = [];
        $jumlah = [];
        $label = [];
        $jumlah_hari = 6;
        $days = date('Y-m-d', strtotime('-'.$jumlah_hari.' days'));

        for ($i=0; $i <= $jumlah_hari ; $i++) { 
            $days_ago = date('Y-m-d', strtotime($days . '+'.$i.' days',));
            $omset = Jurnal::where('master_akun_id',32)->where('cabang_id',$cabang_id)->whereDate('created_at', $days_ago)->sum('nominal');
            $data[] = $omset;
            $label[] = $days_ago;
        }

        $output['series']['name'] = 'Total Laba Harian';
        $output['series']['data'] = $data;
        $output['label'] = $label;
        $output['total'] = $data[$jumlah_hari];
        return response()->json($output, 200);

    }

    public function labaHarian(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $jumlah_hari = $payload->input('hari');
        $output = [];
        $jumlah = [];
        $label = [];
        $jumlah_hari = 6;
        $days = date('Y-m-d', strtotime('-'.$jumlah_hari.' days'));

        for ($i=0; $i <= $jumlah_hari ; $i++) { 
            $days_ago = date('Y-m-d', strtotime($days . '+'.$i.' days',));
            $omset = Jurnal::where('master_akun_id',32)->where('cabang_id',$cabang_id)->whereDate('created_at', $days_ago)->sum('nominal');
            $hpp = Jurnal::where('master_akun_id',44)->where('cabang_id',$cabang_id)->whereDate('created_at', $days_ago)->sum('nominal');
            $jumlah[] = $omset-$hpp;
            $label[] = $days_ago;
        }

        $output['series']['name'] = 'Total Omset Harian';
        $output['series']['data'] = $jumlah;
        $output['label'] = $label;
        $output['total'] = $jumlah[$jumlah_hari];
        return response()->json($output, 200);
    }

    public function labaBulanan(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $tahun = $payload->input('tahun');
        $bulan = [1,2,3,4,5,6,7,8,9,10,11,12];
        $output = [];

        foreach ($bulan as $key => $value) {
            $omsetDebit = Jurnal::where('master_akun_id',32)->where('cabang_id',$cabang_id)->where('jenis','DEBIT')->whereYear('created_at', $tahun)->whereMonth('created_at', $value)->sum('nominal');
            $omsetKredit = Jurnal::where('master_akun_id',32)->where('cabang_id',$cabang_id)->where('jenis','KREDIT')->whereYear('created_at', $tahun)->whereMonth('created_at', $value)->sum('nominal');
            $hppDebit = Jurnal::where('master_akun_id',44)->where('cabang_id',$cabang_id)->where('jenis','DEBIT')->whereYear('created_at', $tahun)->whereMonth('created_at', $value)->sum('nominal');
            $hppKredit = Jurnal::where('master_akun_id',44)->where('cabang_id',$cabang_id)->where('jenis','KREDIT')->whereYear('created_at', $tahun)->whereMonth('created_at', $value)->sum('nominal');
            $beb = DB::table('master_beban')->where('cabang_id',$cabang_id)->whereYear('created_at', $tahun)->whereMonth('created_at', $value)->sum('nominal');

            $labaBersih = (( $omsetKredit - $omsetDebit) - ($hppDebit - $hppKredit)) - $beb;

            // if($labaBersih === 0){
            //     continue;
            // }

            $laba[] = $labaBersih;
            $beban[] = 0- $beb;
            // $label[] = date('F', mktime(0, 0, 0, $value, 10));
        }
        $labaY['name'] = 'Laba Bersih';
        $labaY['data'] = $laba;
        $bebanY['name'] = 'Beban';
        $bebanY['data'] = $beban;
        $output['series'][] = $labaY;
        $output['series'][] = $bebanY;
        // $output['label'] = $label;
        $output['labaBersih'] = $laba[date('n')- 1];
        return response()->json($output, 200);
    }

    public function bebanHarian(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $jumlah_hari = $payload->input('hari');
        $output = [];
        $jumlah = [];
        $label = [];
        $jumlah_hari = 6;
        $days = date('Y-m-d', strtotime('-'.$jumlah_hari.' days'));

        for ($i=0; $i <= $jumlah_hari ; $i++) { 
            $days_ago = date('Y-m-d', strtotime($days . '+'.$i.' days',));
            $nominal = DB::table('master_beban')->where('cabang_id',$cabang_id)->whereDate('created_at', $days_ago)->sum('nominal');
            $jumlah[] = $nominal;
            $label[] = $days_ago;
        }

        $output['series']['name'] = 'Total Beban Harian';
        $output['series']['data'] = $jumlah;
        $output['label'] = $label;
        $output['total'] = $jumlah[$jumlah_hari];
        return response()->json($output, 200);
    }

    public function kasHarian(Request $payload){
        $cabang_id = $payload->input('cabang_id');

        $totalKas = 0;
        $kas = Akun::select('nama','id')->where('komponen', '1.1.2')->where('cabang_id', $cabang_id)->get();

        foreach ($kas as $key => $value) {
            $kredit = Jurnal::where('master_akun_id',$value->id)->where('cabang_id',$cabang_id)->where('jenis','KREDIT')->whereDate('created_at', Carbon::today())->sum('nominal');
            $debit = Jurnal::where('master_akun_id',$value->id)->where('cabang_id',$cabang_id)->where('jenis','DEBIT')->whereDate('created_at', Carbon::today())->sum('nominal');
            $totalKas += $debit - $kredit;
        }


        return response()->json($totalKas, 200);


    }

    public function utangHarian(Request $payload){
        $cabang_id = $payload->input('cabang_id');
        $utangDagang = Akun::select('nama','id','cabang_id')->where('komponen', '2.1.1')->having('cabang_id',0)->orHaving('cabang_id', $cabang_id)->get();
        // return $utangDagang;
        $totalUtang = 0;

        foreach ($utangDagang as $key => $value) {
            $kredit = Jurnal::where('master_akun_id',$value->id)->where('cabang_id',$cabang_id)->where('jenis','KREDIT')->sum('nominal');
            $debit = Jurnal::where('master_akun_id',$value->id)->where('cabang_id',$cabang_id)->where('jenis','DEBIT')->sum('nominal');    
            $totalUtang += $kredit - $debit;
        }


        return response()->json($totalUtang, 200);
    }
}
