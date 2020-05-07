<?php

use Pampadev\Facturacion\Comprobante;

Route::get('/facturacion/prueba', function(){
    $comprobante = new Comprobante;
    dd($comprobante);
});

 ?>