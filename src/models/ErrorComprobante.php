<?php


class ErrorComprobante{
    protected $table = 'errores_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}