<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorComprobante extends Model{
    protected $table = 'errores_comprobantes';

    public function comprobante(){
        return $this->belongsTo(Comprobante::class);
    }
}
