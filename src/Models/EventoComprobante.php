<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class EventoComprobante extends Model{
    protected $table = 'eventos_comprobantes';

    public function comprobante(){
        return $this->belongsTo(Comprobante::class);
    }
}
