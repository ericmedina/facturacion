<?php

namespace Pampadev\Models;

class ErrorComprobante{
    protected $table = 'errores_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('PampaDev\Models\Comprobante');
    }
}