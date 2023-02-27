<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleComprobante extends Model{
    protected $table = 'detalles_comprobantes';

    public function comprobante(){
        return $this->belongsTo(Comprobante::class);
    }
}
