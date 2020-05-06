<?php

namespace Pampadev\Models;

class ObservacionComprobante{
    protected $table = 'observaciones_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('PampaDev\Models\Comprobante');
    }
}