<?php 

namespace Pampadev\Models;

class Comprobante{

    
    public function alicuotas(){
        return $this->hasMany('PampaDev\Models\AlicuotaComprobante');
    }

    public function eventos(){
        return $this->hasMany('PampaDev\Models\EventoComprobante');
    }

    public function observaciones(){
        return $this->hasMany('PampaDev\Models\ObservacionComprobante');
    }

    public function errores(){
        return $this->hasMany('PampaDev\Models\ErrorComprobante');
    }
}