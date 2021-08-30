<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteComprobante extends model{
    protected $table = 'cliente_comprobante';
    
    public function comprobante(){
        return $this->belongsTo('Pampadev\Facturacion\Models\Comprobante');
    }
}

?>