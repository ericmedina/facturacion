<?php

namespace Pampadev\Facturacion;

class EventoComprobante{
    protected $table = 'eventos_comprobante';

    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}