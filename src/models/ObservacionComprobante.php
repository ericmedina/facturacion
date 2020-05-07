<?php

namespace Pampadev\Facturacion;

class ObservacionComprobante{
    protected $table = 'observaciones_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}