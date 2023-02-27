<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteComprobante extends Model{
    protected $table = 'cliente_comprobante';

    public function comprobante(){
        return $this->belongsTo(Comprobante::class);
    }
}

?>
