<?php

use Pampadev\Facturacion\Facturacion;
use Pampadev\Facturacion\Models\AlicuotaComprobante;
use Pampadev\Facturacion\Models\DetalleComprobante;
use Pampadev\Facturacion\Models\Comprobante;

Route::get('/facturacion/prueba', function(){
    try{
        $tiempo_inicio =microtime(true);
        for ($i=0; $i < 1; $i++) { 
            $impneto = rand(1,7500);
            $impiva = $impneto*0.21;
            $imptotal = $impneto+$impiva;

            $comprobante = new Comprobante;
            $comprobante->tipo =8;
            $comprobante->fecha = date('Y-m-d');
            $comprobante->importe_total = $imptotal;
            $comprobante->importe_neto = $impneto;
            $comprobante->importe_iva = $impiva;
            $comprobante->cliente_tipo_doc = 99;
            $comprobante->cliente_num_doc = 0;
    
            $alicuotas = [];
            $alicuota = new AlicuotaComprobante;
            $alicuota->codigo = 5;
            $alicuota->importe_base = $impneto;
            $alicuota->importe_iva = $impiva;
    
            $alicuotas[] = $alicuota;

            $detalles = [];
            $detalle = new DetalleComprobante;
            $detalle->codigo = "0";
            $detalle->descripcion = "Detalle de prueba";
            $detalle->importe = $imptotal;
            $detalle->cantidad = 1;
            
            $detalles[] = $detalle;

            $detalle = new DetalleComprobante;
            $detalle->codigo = "1";
            $detalle->descripcion = "Otro detalle de prueba";
            $detalle->importe = $imptotal;
            $detalle->cantidad = 1;
            
            $detalles[] = $detalle;

            $facturacion = new Facturacion;

            $facturacion->addComprobante($comprobante);
            $facturacion->addAlicuotas(...$alicuotas);
            $facturacion->addDetalles(...$detalles);

            $facturacion->generar_comprobante();
        }
        $tiempo_fin = microtime(true);
        $tiempo = $tiempo_fin - $tiempo_inicio;
        dd($tiempo);

       return response()->json(['success'=> true]);
    }catch(Exception $e){
        $respuesta = array('success' => false, 'mensaje' => $e->getMessage(),'archivo' => $e->getFile(), 'linea' => $e->getLine());
        return response()->json(compact('respuesta'));
    }
    
});

 ?>