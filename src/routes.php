<?php

use Pampadev\Models\Comprobante;

Route::get('/facturacion/prueba', function(){
    $comprobante = new Comprobante;
    dd($comprobante);
});

 ?>