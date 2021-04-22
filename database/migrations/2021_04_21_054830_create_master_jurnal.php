<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMasterJurnal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('master_jurnal', function (Blueprint $table) {
            $table->id();
            $table->string('reff');
            $table->string('master_akun_id');
            $table->double('nominal');
            $table->enum('jenis', ['DEBIT','KREDIT']);
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
        Schema::dropIfExists('master_jurnal');
    }
}
