<?php

use Pampadev\Comprobante;

Route::get('/facturacion/prueba', function(){
    $comprobante = new Comprobante;
    dd($comprobante);
});

 ?>