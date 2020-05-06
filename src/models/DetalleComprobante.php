<?php

namespace Pampadev\Models;

class DetalleComprobante{
    protected $table = 'detalles_comprobante';

    public function comprobante(){
        return $this->belongsTo('PampaDev\Models\Comprobante');
    }
}