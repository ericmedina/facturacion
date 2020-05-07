<?php

namespace Pampadev\Facturacion;

class AlicuotaComprobante{

    protected $table = 'alicuotas_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }

}