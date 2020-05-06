<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetallesComprobantesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalles_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->integer('comprobante_id');
            $table->string('codigo');
            $table->string('descripcion');
            $table->float('importe', 10,2);
            $table->float('cantidad', 10,2);
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
        Schema::dropIfExists('detalles_comprobantes');
    }
}
