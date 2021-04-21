<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterAkun extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_akun', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_akun_id');
            $table->string('nomor_akun');
            $table->string('nama');
            $table->enum('saldo_normal', ['DEBIT','KREDIT']);
            $table->tinyInteger('header');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_akun');
    }
}
