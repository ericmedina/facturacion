<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComprobantesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->integer('tipo');
            $table->integer('numero');
            $table->integer('cliente_tipo_doc')->nullable();
            $table->unsignedBigInteger('cliente_num_doc')->nullable();
            $table->date('fecha');
            $table->float('importe_total',10,2);
            $table->float('importe_neto',10,2);
            $table->float('importe_iva',10,2);
            $table->integer('comprobante_asociado')->nullable();
            $table->unsignedBigInteger('cae')->nullable();
            $table->date('vencimiento_cae')->nullable();
            $table->string('resultado',1);
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
        Schema::dropIfExists('comprobantes');
    }
}
