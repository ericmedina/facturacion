<?php

namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class ObservacionComprobante extends Model{
    protected $table = 'observaciones_comprobantes';
    
    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}