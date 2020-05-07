<?php

namespace Pampadev\Facturacion\Models;

class DetalleComprobante{
    protected $table = 'detalles_comprobante';

    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}