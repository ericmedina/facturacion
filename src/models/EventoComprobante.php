<?php

namespace Pampadev\Models;

class EventoComprobante{
    protected $table = 'eventos_comprobante';

    public function comprobante(){
        return $this->belongsTo('PampaDev\Models\Comprobante');
    }
}