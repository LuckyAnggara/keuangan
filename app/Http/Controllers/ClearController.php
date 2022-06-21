<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClearController extends Controller
{
    public function clear(){
        DB::table('master_jurnal')->truncate();
    }
}