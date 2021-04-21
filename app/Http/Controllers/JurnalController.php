<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Jurnal;

class JurnalController extends Controller
{
    public function index(){
        $data = Jurnal::all();
        return response()->json($data, 200);
    }

    public function store(Request $request){
        $data = Jurnal::create([
            'reff'=>$request['reff'],
            'master_akun_id'=>$request['master_akun_id'],
            'nominal'=>$request['nominal'],
            'jenis'=>$request['jenis'],
        ]);
        return response()->json($data, 200);
    }
}
