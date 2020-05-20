<?php

use Pampadev\Facturacion\Facturacion;
use Pampadev\Facturacion\Models\AlicuotaComprobante;
use Pampadev\Facturacion\Models\DetalleComprobante;
use Pampadev\Facturacion\Models\ClienteComprobante;
use Pampadev\Facturacion\Models\Comprobante;

Route::get('/facturacion/prueba', function(){
    try{
        $tiempo_inicio =microtime(true);
        for ($i=0; $i < 1; $i++) { 
            $impneto = rand(1,7500);
            $impiva = $impneto*0.21;
            $imptotal = $impneto+$impiva;

            $comprobante = new Comprobante;
            $comprobante->tipo =6;
            $comprobante->fecha = date('Y-m-d');
            $comprobante->importe_total = $imptotal;
            $comprobante->importe_neto = $impneto;
            $comprobante->importe_iva = $impiva;
            $comprobante->cliente_tipo_doc = 80;
            $comprobante->cliente_num_doc = 20177307352;
    
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

            $cliente = new ClienteComprobante;
            $cliente->nombre = "ABEL OSVALDO MEDINA";
            $cliente->num_doc = $comprobante->cliente_num_doc;
            $cliente->condicion_iva = "RESPONSABLE INSCRIPTO";
            $cliente->condicion_venta = "CONTADO";

            $facturacion = new Facturacion;

            $facturacion->addComprobante($comprobante);
            $facturacion->addAlicuotas(...$alicuotas);
            $facturacion->addDetalles(...$detalles);
            $facturacion->addCliente($cliente);

            $facturacion->generar_comprobante();
        }
        $tiempo_fin = microtime(true);
        $tiempo = $tiempo_fin - $tiempo_inicio;
       return response()->json(['success'=> true, 'comprobante' => $facturacion->comprobante]);
    }catch(Exception $e){
        $respuesta = array('success' => false, 'mensaje' => $e->getMessage(),'archivo' => $e->getFile(), 'linea' => $e->getLine());
        return response()->json(compact('respuesta'));
    }
    
});

 ?>