<?php
namespace Pampadev\Facturacion\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model{

	public function cliente(){
        return $this->hasOne(ClienteComprobante::class);
    }

    public function alicuotas(){
        return $this->hasMany(AlicuotaComprobante::class);
    }

    public function eventos(){
        return $this->hasMany(EventoComprobante::class);
    }

    public function observaciones(){
        return $this->hasMany(ObservacionComprobante::class);
    }

    public function errores(){
        return $this->hasMany(ErrorComprobante::class);
    }

    #-------------------------------------------------------------------------------
	#FUNCION OBTENER NUMERO DE TIPO DE COMPROBANTE A PARTIR DE LETRAS
	#ENTRADA: A, B, C, NCA, NCB, NCC, NDA, NDB, NDC, R, P, FP
	#SALIDA:  01, 06, 11, 03, 08, 13, 02, 07, 12, 91, 100, 101
	#===============================================================================
	public function tipo_comprobante($comprobante_tipo = null)
	{
		$tipo = $comprobante_tipo ?? $this->tipo ?? null;
		if($tipo === null){
			trigger_error("SE REQUIERE EL PARÁMETRO TIPO COMPROBANTE", E_USER_ERROR);
		}
		if ($tipo == 1) {
			$tipo_comprobante = 'A';
		} else if ($tipo == 6) {
			$tipo_comprobante = 'B';
		} else if ($tipo == 11) {
			$tipo_comprobante = 'C';
		} else if ($tipo == 3) {
			$tipo_comprobante = 'NCA';
		} else if ($tipo == 8) {
			$tipo_comprobante = 'NCB';
		} else if ($tipo == 13) {
			$tipo_comprobante = 'NCC';
		} else if ($tipo == 2) {
			$tipo_comprobante = 'NDA';
		} else if ($tipo == 7) {
			$tipo_comprobante = 'NDB';
		} else if ($tipo == 12) {
			$tipo_comprobante = 'NDC';
		} else if ($tipo == 91) {
			$tipo_comprobante = 'R';
		} else if ($tipo == 100) {
			$tipo_comprobante = 'P';
		} else if ($tipo == 101) {
			$tipo_comprobante = 'FP';
		}
		return $tipo_comprobante;
    }
    public function titulo_tipo($comprobante_tipo = null){
        $tipo = $comprobante_tipo ?? $this->tipo ?? null;
		if($tipo === null){
			trigger_error("SE REQUIERE EL PARÁMETRO TIPO COMPROBANTE", E_USER_ERROR);
		}
		if ($tipo == 1 || $tipo == 6 || $tipo == 11) {
			$titulo = 'FACTURA';
		} else if ($tipo == 3 || $tipo == 8 ||$tipo == 13) {
			$titulo = 'NOTA DE CRÉDITO';
		} else if ($tipo == 2 || $tipo == 7 || $tipo == 12) {
			$titulo = 'NOTA DE DÉBITO';
		} else if ($tipo == 91) {
			$titulo = 'REMITO';
		} else if ($tipo == 100) {
			$titulo = 'PRESUPUESTO';
		} else if ($tipo == 101) {
			$titulo = 'FACTURA PROFORMA';
		}
		return $titulo;
    }
}
