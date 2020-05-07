<?php

namespace Pampadev\Facturacion\Models;
class EventoComprobante{
    protected $table = 'eventos_comprobante';

    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}