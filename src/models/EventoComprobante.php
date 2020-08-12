<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class EventoComprobante extends model{
    protected $table = 'eventos_comprobantes';

    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Models\Comprobante');
    }
}