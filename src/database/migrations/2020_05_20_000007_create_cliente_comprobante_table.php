<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClienteComprobanteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cliente_comprobante', function (Blueprint $table) {
            $table->id();
            $table->integer('comprobante_id');
            $table->string('nombre')->default('');
            $table->string('num_doc')->default('');
            $table->string('domicilio')->default('');
            $table->string('localidad')->default('');
            $table->string('condicion_iva')->default('');
            $table->string('condicion_venta')->default('');
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
        Schema::dropIfExists('cliente_comprobante');
    }
}
