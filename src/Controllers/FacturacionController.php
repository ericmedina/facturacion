<?php

namespace Pampadev\Facturacion\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Pampadev\Facturacion\Facturacion;

class FacturacionController extends Controller
{
    //
    public function prueba(){
    	$factura = new Facturacion();
    	dd($factura);
    }
    public function create(){
    	return view('facturacion.create');
    }
}
