<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Akun;

class AkunController extends Controller
{
    public function index(){
        $data = Akun::where('jenis_akun_id', 1)->get();
        return response()->json($data, 200);
    }
}
