<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class AlicuotaComprobante extends model{

    protected $table = 'alicuotas_comprobantes';
    
    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Models\Comprobante');
    }

}