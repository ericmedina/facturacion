<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleComprobante extends model{
    protected $table = 'detalles_comprobante';

    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Comprobante');
    }
}