<?php

namespace Pampadev\Models;

class AlicuotaComprobante{

    protected $table = 'alicuotas_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('PampaDev\Models\Comprobante');
    }

}