<?php 

namespace Pampadev\Facturacion;

class Comprobante{

    
    public function alicuotas(){
        return $this->hasMany('Pampadev\Facturacion\AlicuotaComprobante');
    }

    public function eventos(){
        return $this->hasMany('Pampadev\Facturacion\EventoComprobante');
    }

    public function observaciones(){
        return $this->hasMany('Pampadev\Facturacion\ObservacionComprobante');
    }

    public function errores(){
        return $this->hasMany('Pampadev\Facturacion\ErrorComprobante');
    }
}