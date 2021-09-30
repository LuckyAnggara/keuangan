<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
// use App\Models\Jurnal;
use App\Models\Akun;
use App\Models\Performance;

class PerformanceCabang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:cabang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cabang = Http::get(mainApi().'cabang/')->json();

        foreach ($cabang as $key => $cabang) {
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
                        $saldo = cekSaldo($value->id,$value->saldo_normal, $cabang['id'],null, null, date("Y-m-d"));
                        $value->saldo = $saldo;
                        $headerSaldo += $saldo;
                    }
                    $sub->saldo = $headerSaldo;
    
                    $subsaldo = cekSaldo($sub->id,$sub->saldo_normal, $cabang['id'],null, null, date("Y-m-d"));
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

            $performance = Performance::create([
                'cabang_id' => $cabang['id'],
                'tanggal' => date("Y-m-d"),
                'penjualan' => $output['pendapatan'][0]->saldo,
                'pendapatan_lainnya' => $output['pendapatan'][1]->saldo,
                'retur_penjualan' => $output['pendapatan'][2]->saldo,
                'diskon_penjualan' => $output['pendapatan'][3]->saldo,
                'hpp' => $output['pendapatan'][4]->saldo,
                'diskon_pembelian' => $output['pendapatan'][5]->saldo,
                'beban_gaji' => $output['beban'][0]->saldo,
                'beban_sewa' => $output['beban'][1]->saldo,
                'beban_operasional' => $output['beban'][2]->saldo,
                'beban_lainnya' => $output['beban'][3]->saldo,
                'laba' => $totalPendapatan - $totalBeban
            ]);
        }
    }

    // public function index(Request $payload)
    // {
    //     $year = $payload->input('tahun');
    //     $month = $payload->input('bulan');
    //     $day = $payload->input('hari');

    //     $cabang_id = $payload->input('cabang_id');
        
    //     // if($year == null) {
    //     //     $year = 'Y';
    //     // }
    //     $output = [];
    //     $totalPendapatan = 0;
    //     $totalBeban = 0;

    //     foreach ($jenis as $key => $value) {
    //         $nama = 'pendapatan';
    //         if($value == '5'){
    //             $nama = 'beban';
    //         }

    //         $dd = DB::table('master_akun')
    //         ->where('deleted_at')
    //         ->where('header','=',0)
    //         ->where('komponen','=',null)
    //         ->where('jenis_akun_id','=',$value)    
    //         ->orderBy('kode_akun','ASC')
    //         ->get();
    
    //         foreach ($dd as $key => $sub) {
    //             $headerSaldo = 0;
    //             $komponen = Akun::where('deleted_at')
    //             ->where('komponen', $sub->kode_akun)
    //             ->whereIn('cabang_id', array(0, $cabang_id))
    //             ->get();
    //             $sub->komponen = $komponen;

    //             foreach ($komponen as $key => $value) {
    //                 $saldo = $this->cekSaldo($value->id,$value->saldo_normal, $cabang_id,$year, $month, $day);
    //                 $value->saldo = $saldo;
    //                 $headerSaldo += $saldo;
    //             }
    //             $sub->saldo = $headerSaldo;

    //             $subsaldo = $this->cekSaldo($sub->id,$sub->saldo_normal, $cabang_id,$year, $month, $day);
    //             $sub->saldo = $headerSaldo + $subsaldo;
    //         }

    //         $output[$nama] = $dd;
    //     }

    //     foreach ($output['pendapatan'] as $key => $pendapatan) {
    //         if($pendapatan->saldo_normal == 'DEBIT'){
    //             $totalPendapatan -= $pendapatan->saldo;
    //         }else{
    //             $totalPendapatan += $pendapatan->saldo;
    //         }
    //     }
    //     $output['pendapatan'][] = [
    //         'nama' => 'TOTAL PENDAPATAN',
    //         'align' => 1,
    //         'total' => true,
    //         'saldo' => $totalPendapatan
    //     ];

    //     foreach ($output['beban'] as $key => $beban) {
    //         if($beban->saldo_normal == 'DEBIT'){
    //             $totalBeban += $beban->saldo;
    //         }else{
    //             $totalBeban -= $beban->saldo;
    //         }
    //     }
    //     $output['beban'][] = [
    //         'nama' => 'TOTAL BEBAN',
    //         'align' => 1,
    //         'total' => true,
    //         'saldo' => $totalBeban
    //     ];

    //     $output['labaRugi'] = $totalPendapatan - $totalBeban;

    //     return response()->json($output, 200);
    // }
}
