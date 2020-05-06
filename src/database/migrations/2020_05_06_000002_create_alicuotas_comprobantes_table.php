<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlicuotasComprobantesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alicuotas_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo');
            $table->integer('comprobante_id');
            $table->float('importe_base',10,2);
            $table->float('importe_iva',10,2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alicuotas_comprobantes');
    }
}
