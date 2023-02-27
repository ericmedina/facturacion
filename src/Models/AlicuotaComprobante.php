<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class AlicuotaComprobante extends Model{

    protected $table = 'alicuotas_comprobantes';

    public function comprobante(){
        return $this->belongsTo(Comprobante::class);
    }

}
