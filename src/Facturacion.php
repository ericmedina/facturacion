<?php

/**
 * 
 */

namespace Pampadev\Facturacion;


use Pampadev\Facturacion\Models\AlicuotaComprobante;
use Pampadev\Facturacion\Models\ClienteComprobante;
use Pampadev\Facturacion\Models\DetalleComprobante;
use Pampadev\Facturacion\Models\Comprobante;
use Pampadev\Facturacion\Models\ErrorComprobante;
use Pampadev\Facturacion\Models\EventoComprobante;
use Pampadev\Facturacion\Models\ObservacionComprobante;
use Milon\Barcode\DNS1D;
use PDF;
use SoapClient;

class Facturacion
{

	#--------------------------------------------------------------------------
	#URLS Y CERTIFICADOS
	#==========================================================================


	public $homo;
	public $PASSPHRASE;
	public $cuit_emisor;
	public $punto_venta;
	public $concepto;
	public $cert;
	public $key;

	private $URL_WSAA;
	private $WSDL_WSAA;
	private $URL_ULT;
	private $WSDL_ULT;
	private $WSDL_CAE;
	private $URL_CAE;
	private $URL_TIPO_IVA;
	private $WSDL_PERSONA;
	private $URL_PERSONA;
	private $WSDL_COMP;
	private $URL_COMP;

	#-----------------------------------------------------------------------------
	#CREDENCIALES
	#=============================================================================

	private $token;
	private $sign;


	#-----------------------------------------------------------------------------
	#CLASES ASOCIADAS
	#=============================================================================

	//CLASE COMPROBANTE
	public $comprobante;

	//CLASE ALICUOTAS_COMPROBANTE
	public $alicuotas;

	//CLASE OBSERVACIONES_COMPROBANTE
	public $observaciones;

	//CLASE ERRORES_COMPROBANTE
	public $errores;

	//CLASE EVENTOS_COMPROBANTE
	public $eventos;

	//CLASE DETALLES_COMPROBANTE
	public $detalles;

	//OBJETO CLIENTE
	public $cliente;
	#-----------------------------------------------------------------------------
	#DATOS CLIENTE
	#=============================================================================
	public $tipo_doc;
	public $doc_nro;

	#-----------------------------------------------------------------------------
	#DATOS COMPROBANTE
	#=============================================================================
	public $ultimo_comprobante;
	public $cantidad_registros = 1;
	public $importe_total_concepto = "0";
	public $importe_operaciones_externas = "0";
	public $importe_tributo = "0";
	public $moneda_id = "PES";
	public $moneda_cotiz = "1";

	#------------------------------------------------------------------------------
	#ATRIBUTOS ALMACENAMIENTO RESPUESTA
	#==============================================================================
	public $FECAESolicitar;


	function __construct()
	{
		$this->observaciones = [];
		$this->errores = [];
		$this->eventos = [];

		$this->homo = config('facturacion.homo');
		$this->PASSPHRASE = config('facturacion.PASSPHRASE');
		$this->cuit_emisor = config('facturacion.cuit');
		$this->punto_venta = config('facturacion.punto_venta');
		$this->concepto = config('facturacion.concepto');
		$this->cert = $this->facturacion_path('certs/' . config('facturacion.cert'));
		$this->key = $this->facturacion_path('certs/' . config('facturacion.key'));
		$this->setear_urls();

	}

	public function addComprobante(Comprobante $comprobante){
		$this->comprobante = $comprobante;
	}
	public function addAlicuotas(AlicuotaComprobante ...$alicuotas){
		$this->alicuotas = $alicuotas;
	}
	public function addDetalles(DetalleComprobante ...$detalles){
		$this->detalles = $detalles;
	}
	public function addCliente(ClienteComprobante $cliente){
		$this->cliente = $cliente;
	}
	#------------------------------------------------------------------------------
	#FUNCION UTILIZABLE DE WSAA CREA XML TA.XML
	#crea credenciales en variables $token y $sign
	#==============================================================================
	public function Autorizacion($ws)
	{
		#CHECKEA SI YA EXISTE ACCESO
		if (file_exists($this->cert)) {
			if (file_exists($this->facturacion_path("xml/TA.xml"))) {
				$xml = simplexml_load_file($this->facturacion_path("xml/TA.xml"));
				$exp = $xml->header->expirationTime;
				$now = date('c', date('U'));
			} else {
				$exp = 0;
				$now = 1;
			}
		} else {
			$exp = 0;
			$now = 1;
		}
		#SI EXPIRO EL TICKET PEDIS UNO NUEVO
		if ($now > $exp) {
			#INICIA Y CHECKEA SI EXISTE EL CERIFICADO

			ini_set("soap.wsdl_cache_enabled", "0");
			if (!file_exists($this->cert)) {
				trigger_error('No se ha encontrado el certificado', E_USER_ERROR);
			}
			if (!file_exists($this->key)) {
				trigger_error('No se ha encontrado la llave privada', E_USER_ERROR);
			}
			// Url();

			#CREAR TICKET DE ACCESO
			$TRA = new \SimpleXMLElement(
				'<?xml version="1.0" encoding="UTF-8"?>' .
					'<loginTicketRequest version="1.0">' .
					'</loginTicketRequest>'
			);
			$TRA->addChild('header');
			$TRA->header->addChild('uniqueId', date('U'));
			$TRA->header->addChild('generationTime', date('c', date('U') - 60));
			$TRA->header->addChild('expirationTime', date('c', date('U') + 60));
			$TRA->addChild('service', $ws);
			$TRA->asXML($this->facturacion_path("xml/TRA.xml"));

			#FIRMA TICKET DE ACCESO CON CERTIFICADO

			$STATUS = openssl_pkcs7_sign(
				$this->facturacion_path("/xml/TRA.xml"),
				$this->facturacion_path("/xml/TRA.tmp"),
				"file://" . $this->cert,
				array("file://" . $this->key, $this->PASSPHRASE),
				array(),
				!PKCS7_DETACHED
			);
			if (!$STATUS) {
				exit("ERROR generating PKCS#7 signature\n");
			}
			$inf = fopen($this->facturacion_path("/xml/TRA.tmp"), "r");
			$i = 0;
			$CMS = "";
			while (!feof($inf)) {
				$buffer = fgets($inf);
				if ($i++ >= 4) {
					$CMS .= $buffer;
				}
			}
			fclose($inf);
			$client = new \SoapClient($this->WSDL_WSAA, array(
				'soap_version'   => SOAP_1_2,
				'location'       => $this->URL_WSAA,
				'trace'          => 1
			));
			$results = $client->loginCms(array('in0' => $CMS));
			if (is_soap_fault($results)) {
				exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
			}
			#Genera XML
			file_put_contents($this->facturacion_path("/xml/TA.xml"), $results->loginCmsReturn);
		}
		$xml = json_encode(simplexml_load_file($this->facturacion_path("/xml/TA.xml")));
		$TA = json_decode($xml, true);
		$token = $TA['credentials']['token'];
		$sign = $TA['credentials']['sign'];
		$this->token = $token;
		$this->sign = $sign;
	}

	public function generar_comprobante(){
		$this->validar_atributos();
		if($this->comprobante->tipo == 91 || $this->comprobante->tipo == 100){
			$this->comprobante->numero = $this->UltimoGuardado($this->comprobante->tipo) + 1;
			$this->comprobante->resultado = "X";
			$this->guardar_comprobante();
		}else{
			$this->comprobante->numero = $this->UltimoAutorizado()+1;
			$this->obtener_cae();
		}
		// if($this->comprobante->resultado == 'A' || $this->comprobante->resultado == 'X'){
		// 	$this->guardar_pdf();
		// }
	}

	#-------------------------------------------------------------------------------
	#FUNCION ULTIMO COMPROBANTE AUTORIZADO
	#===============================================================================
	public function UltimoAutorizado()
	{
		$this->Autorizacion('wsfe');
		$cuitemisor = (float) $this->cuit_emisor;
		$auth = array('Token' => $this->token, 'Sign' => $this->sign, 'Cuit' => $cuitemisor);
		$FECompUltimoAutorizado = array('Auth' => $auth, 'PtoVta' => intval($this->punto_venta), 'CbteTipo' => $this->comprobante->tipo);
		//INICIAR CLIENTE SOAP
		$client = new \SoapClient($this->WSDL_ULT, array(
			'soap_version'   => SOAP_1_2,
			'location'       => $this->URL_ULT,
			'trace'          => 1
		));
		//ENVIAR DATOS
		$results = $client->FECompUltimoAutorizado($FECompUltimoAutorizado);
		$json_obj = json_encode($results);
		if (is_soap_fault($results)) {
			exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
		}

		file_put_contents($this->facturacion_path("/xml/request-compultimoautorizado.xml"), $client->__getLastRequest());
		file_put_contents($this->facturacion_path("/xml/response-compultimoautorizado.json"), $json_obj);
		$response = json_decode($json_obj, true);
		return $response['FECompUltimoAutorizadoResult']['CbteNro'];
	}

	#-------------------------------------------------------------------------------
	#FUNCION OBTENER NUMERACION DE BD POR TIPO
	#===============================================================================
	public function UltimoGuardado($tipo){
		$numero = Comprobante::where('tipo', $tipo)->pluck('numero')->last();
		return $numero ?? 0;
	}

	#-------------------------------------------------------------------------------
	#ARMADO COMPROBANTE  ELECTRONICO
	#SALIDA: RESULTADO, CAE, VENCIMIENTO_CAE, OBSERVACIONES, ERRORES
	#===============================================================================
	public function obtener_cae()
	{
		$this->Autorizacion('wsfe');
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if (intval($this->concepto) > 1) {
			$this->fecha_servicio_desde = $this->comprobante->fecha;
			$this->fecha_servicio_hasta = $this->comprobante->fecha;
			$this->fecha_vencimiento_pago = $this->comprobante->fecha;
		} else {
			$this->fecha_servicio_desde = "";
			$this->fecha_servicio_hasta = "";
			$this->fecha_vencimiento_pago = "";
		}

		#LLAMAMOS CLIENTE SOAP

		$client = new \SoapClient($this->WSDL_CAE, array(
			'soap_version'   => SOAP_1_2,
			'location'       => $this->URL_CAE,
			'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token' => $this->token, 'Sign' => $this->sign, 'Cuit' => floatval($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq = array('CantReg' => $this->cantidad_registros, 'PtoVta' => $this->punto_venta, 'CbteTipo' => $this->comprobante->tipo);

		#IVA
		$AlicIva = [];
		foreach ($this->alicuotas as $alicuota) {
			$AlicIva[] = array('Id' => $alicuota->codigo, 'BaseImp' => $alicuota->importe_base, 'Importe' => $alicuota->importe_iva);
		}
		
		// $array_assoc = array('Tipo' => 11, 'PtoVta' => $this->punto_venta, 'Nro' => $this->assoc);
		// $cbtes_assoc = array('CbteAsoc' => $array_assoc);

		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto' => $this->concepto, 'DocTipo' => $this->comprobante->cliente_tipo_doc, 'DocNro' => $this->comprobante->cliente_num_doc, 'CbteDesde' => $this->comprobante->numero, 'CbteHasta' => $this->comprobante->numero, 'CbteFch' => date('Ymd',strtotime($this->comprobante->fecha)), 'ImpTotal' => $this->comprobante->importe_total, 'ImpTotConc' => $this->importe_total_concepto, 'ImpNeto' => $this->comprobante->importe_neto, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => $this->comprobante->importe_iva, 'FchServDesde' => $this->fecha_servicio_desde, 'FchServHasta' => $this->fecha_servicio_hasta, 'FchVtoPago' => $this->fecha_vencimiento_pago, 'MonId' => $this->moneda_id, 'MonCotiz' => $this->moneda_cotiz, 'Iva' => $AlicIva);

		if($this->comprobante->tipo == 3 || $this->comprobante->tipo == 8 || $this->comprobante->tipo == 11){
			$array_assoc = array('Tipo' => $this->tipo_assoc, 'PtoVta' => $this->punto_venta, 'Nro' => $this->assoc);
			$FECAEDetRequest['CbtesAsoc'] = array('CbteAsoc' => $array_assoc);
		}


		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest' => $FECAEDetRequest);
		$FeCAEReq = array('FeCabReq' => $FeCabReq, 'FeDetReq' => $FeDetReq);
		$this->FECAESolicitar = array('Auth' => $Auth, 'FeCAEReq' => $FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($this->FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents($this->facturacion_path("/xml/request-caesolicitar-".$this->comprobante->tipo."-".$this->comprobante->numero.".xml"), $client->__getLastRequest());
		file_put_contents($this->facturacion_path("/xml/response-caesolicitar-".$this->comprobante->tipo."-".$this->comprobante->numero.".json"), $json_obj);
		if (is_soap_fault($results)) {
			exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
		}

		#LEEMOS LOS DATOS

		$data = $json_obj;
		$response = json_decode($data);

		if (isset($response->FECAESolicitarResult->FeDetResp)) {
			$this->comprobante->resultado = $response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado;
			$cae =$response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE;
			if($cae != '' && $cae != null){
				$this->comprobante->cae = $cae;
			}
			$vencimiento = $response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAEFchVto;
			if($vencimiento != '' && $vencimiento != null){
				$this->comprobante->vencimiento_cae = date('Y-m-d', strtotime($vencimiento));
			}
			$this->obtener_observaciones_cae($response);
			$this->obtener_errores_cae($response);
			$this->obtener_eventos_cae($response);
			$this->guardar_comprobante();
		} else {
			$this->obtener_errores_cae($response);
		}
	}

	
	
	#-----------------------------------------------------------------------------------
	#FUNCION OBTENER CONCEPTO
	#ENTRADA: PRODUCTOS, SERVICIOS, PRODUCTOS Y SERVICIOS
	#SALIDA: 1, 2, 3
	#===================================================================================

	public function tipo_actividad($actividad)
	{
		if ($actividad == 'productos') {
			return 1;
		} elseif ($actividad == 'servicios') {
			return 2;
		} elseif ($actividad == 'productos y servicios') {
			return 3;
		}
	}

	#------------------------------------------------------------------------------------
	#FUNCION OBTENER TIPO DE DOC
	#ENTRADA NUMERO CUIT
	#SALIDA DOC_TIPO: 80(CUIT), 96(DNI), 99(OTRO)
	#====================================================================================

	public function doc_tipo($doc)
	{
		$dni = str_replace("-", "", str_replace("/", "", $doc));
		if (strlen($dni) == 8) {
			return 96;
		} elseif (strlen($dni) == 11) {
			return 80;
		} else {
			return 99;
		}
	}

	#------------------------------------------------------------------------------------
	#FUNCION LLAMADA A WS AFIP TIPOS IVA
	#ENVIO TOKENS Y CUIT
	#RESPUESTA TIPOS DE IVA
	#====================================================================================

	public function tipos_iva()
	{

		$this->Autorizacion("wsfe");

		#LLAMAMOS CLIENTE SOAP

		$client = new \SoapClient($this->WSDL_CAE, array(
			'soap_version'   => SOAP_1_2,
			'location'       => $this->URL_TIPO_IVA,
			'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token' => $this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		$FEParamGetTiposIva = array('Auth' => $Auth);
		#ENVIAMOS EL REQUEST

		$results = $client->FEParamGetTiposIva($FEParamGetTiposIva);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents($this->facturacion_path("/xml/request-tiposiva.xml"), $client->__getLastRequest());
		file_put_contents($this->facturacion_path("/xml/response-tiposiva.json"), $json_obj);
		if (is_soap_fault($results)) {
			exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
		}

		#LEEMOS LOS DATOS
	}

	#-------------------------------------------------------------------------------------
	#FUNCION OBTENER DATOS PERSONA DESDE WS AFIP
	#ENVIO TOKENS Y CUIT
	#RESPUESTA DATOS PERSONA
	#=====================================================================================

	public function Autorizacion_persona($ws)
	{
		#CHECKEA SI YA EXISTE ACCESO
		if (file_exists($this->cert)) {
			if (file_exists($this->facturacion_path("/xml/TA_persona.xml"))) {
				$xml = simplexml_load_file($this->facturacion_path("/xml/TA_persona.xml"));
				$exp = $xml->header->expirationTime;
				$now = date('c', date('U'));
			} else {
				$exp = 0;
				$now = 1;
			}
		} else {
			$exp = 0;
			$now = 1;
		}
		#SI EXPIRO EL TICKET PEDIS UNO NUEVO
		//if($now>$exp){
		#INICIA Y CHECKEA SI EXISTE EL CERIFICADO

		ini_set("soap.wsdl_cache_enabled", "0");
		if (!file_exists($this->cert)) {
			exit("Failed to open " . $this->cert . "\n");
		}
		if (!file_exists($this->key)) {
			exit("Failed to open " . $this->key . "\n");
		}
		Url();

		#CREAR TICKET DE ACCESO
		$TRA = new \SimpleXMLElement(
			'<?xml version="1.0" encoding="UTF-8"?>' .
				'<loginTicketRequest version="1.0">' .
				'</loginTicketRequest>'
		);
		$TRA->addChild('header');
		$TRA->header->addChild('uniqueId', date('U'));
		$TRA->header->addChild('generationTime', date('c', date('U') - 60));
		$TRA->header->addChild('expirationTime', date('c', date('U') + 60));
		$TRA->addChild('service', $ws);
		$TRA->asXML($this->facturacion_path("/xml/TRA_persona.xml"));

		#FIRMA TICKET DE ACCESO CON CERTIFICADO

		$STATUS = openssl_pkcs7_sign(
			$this->facturacion_path("/xml/TRA_persona.xml"),
			$this->facturacion_path("/xml/TRA_persona.tmp"),
			"file://" . $this->cert,
			array("file://" . $this->key, $this->PASSPHRASE),
			array(),
			!PKCS7_DETACHED
		);
		if (!$STATUS) {
			exit("ERROR generating PKCS#7 signature\n");
		}
		$inf = fopen($this->facturacion_path("/xml/TRA_persona.tmp"), "r");
		$i = 0;
		$CMS = "";
		while (!feof($inf)) {
			$buffer = fgets($inf);
			if ($i++ >= 4) {
				$CMS .= $buffer;
			}
		}
		fclose($inf);
		$client = new \SoapClient($this->WSDL_WSAA, array(
			'soap_version'   => SOAP_1_2,
			'location'       => $this->URL_WSAA,
			'trace'          => 1
		));
		$results = $client->loginCms(array('in0' => $CMS));
		if (is_soap_fault($results)) {
			exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
		}
		#Genera XML
		file_put_contents($this->facturacion_path("/xml/TA_persona.xml"), $results->loginCmsReturn);

		//}
		$xml = json_encode(simplexml_load_file($this->facturacion_path("/xml/TA_persona.xml")));
		$TA = json_decode($xml, true);
		$token = $TA['credentials']['token'];
		$sign = $TA['credentials']['sign'];
		$this->token = $token;
		$this->sign = $sign;
	}


	public function persona($cuit_persona)
	{
		$this->Autorizacion_persona("ws_sr_constancia_inscripcion");

		#LLAMAMOS CLIENTE SOAP

		$context = stream_context_create(array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		));

		$client = new \SoapClient("https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL", array(
			'soap_version'   	=> SOAP_1_1,
			'trace'          	=> 0,
			'exceptions' 		=> 0,
			'stream_context'	=> $context,
		));

		#CREDENCIALES

		$getPersona = array('token' => $this->token, 'sign' => $this->sign, 'cuitRepresentada' => floatVal($this->cuit_emisor), 'idPersona' => floatval($cuit_persona));

		$FEParamGetTiposIva = array('getPersona' => $getPersona);
		#ENVIAMOS EL REQUEST
		$results = $client->getPersona_v2($getPersona);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents($this->facturacion_path("/xml/request-getPersona.xml"), $client->__getLastRequest());
		file_put_contents($this->facturacion_path("/xml/response-Persona.json"), $json_obj);
		if (is_soap_fault($results)) {
			exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
		}
		return $json_obj;
	}

	public function Consultar()
	{
		$this->Autorizacion("wsfe");

		$cuitemisor = (float) $this->cuit_emisor;
		$auth = array('Token' => $this->token, 'Sign' => $this->sign, 'Cuit' => $cuitemisor);
		$FeCompConsReq = array('CbteTipo' => $this->tipo_comprobante, 'CbteNro' => $this->comprobante_desde, 'PtoVta' => $this->punto_venta);

		$FECompConsultar = array('Auth' => $auth, 'FeCompConsReq' => $FeCompConsReq);

		//INICIAR CLIENTE SOAP
		$client = new \SoapClient($this->WSDL_COMP, array(
			'soap_version'   => SOAP_1_2,
			'location'       => $this->URL_COMP,
			'trace'          => 1
		));
		//ENVIAR DATOS
		$results = $client->FECompConsultar($FECompConsultar);
		$json_obj = json_encode($results);
		if (is_soap_fault($results)) {
			exit("SOAP Fault: " . $results->faultcode . "\n" . $results->faultstring . "\n");
		}

		file_put_contents($this->facturacion_path("/xml/request-compconsultar.xml"), $client->__getLastRequest());
		file_put_contents($this->facturacion_path("/xml/response-compconsultar.json"), $json_obj);
		$response = json_decode($json_obj, true);
		return $response;
	}

	#-------------------------------------------------------------------------------------
	#MANEJO DE EVENTOS, ERRORES Y OBSERVACIONES
	#SETEA LOS ATRIBUTOS CORRESPONDIENTES EN LA CLASE
	#=====================================================================================
	public function obtener_observaciones_cae($response){
		$observaciones = [];
		if(isset($response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones)){
			$observaciones = $response->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Observaciones->Obs;
			if(is_array($observaciones)){
				foreach($observaciones as $obs){
					$observacion = new ObservacionComprobante;
					$observacion->codigo = $obs->Code;
					$observacion->descripcion = $obs->Msg;
					$this->observaciones[] = $observacion;
				}
			}else{
				$observacion = new ObservacionComprobante;
				$observacion->codigo = $observaciones->Code;
				$observacion->descripcion = $observaciones->Msg;
				$this->observaciones[] = $observacion;
			}
		}
	}
	public function obtener_errores_cae($response){
		$errores = [];
		if(isset($response->FECAESolicitarResult->Errors)){
				//SI ES ARRAY
			$errores = $response->FECAESolicitarResult->Errors->Err;
			if(is_array($errores)){
				foreach($errores as $err){
					$error_comprobante = new ErrorComprobante;
					$error_comprobante->codigo = $err->Code;
					$error_comprobante->descripcion = $err->Msg;
					$this->Errores[] = $error_comprobante;
				}
			}else{
				$error_comprobante = new ErrorComprobante;
				$error_comprobante->codigo = $errores->Code;
				$error_comprobante->descripcion = $errores->Msg;
				$this->Errores[] = $error_comprobante;
			}
		}
	
	}
	public function obtener_eventos_cae($response){
		$eventos = [];
		if(isset($response->FECAESolicitarResult->Events)){
			$eventos = $response->FECAESolicitarResult->Events->Evt;
			if(is_array($eventos)){
				foreach($eventos as $evt){
					$evento_comprobante = new EventoComprobante;
					$evento_comprobante->codigo = $evt->Code;
					$evento_comprobante->descripcion = $evt->Msg;
					$this->Eventos[] = $evento_comprobante;
				}
			}else{
				$evento_comprobante = new EventoComprobante;
				$evento_comprobante->codigo = $eventos->Code;
				$evento_comprobante->descripcion = $eventos->Msg;
				$this->Eventos[] = $evento_comprobante;
			}
		}
	}


	public function guardar_comprobante(){
		if($this->comprobante->save()){
			foreach($this->alicuotas as $alicuota){
				$alicuota->comprobante_id = $this->comprobante->id;
				$alicuota->save();
			}
			foreach($this->observaciones as $observacion){
				$observacion->comprobante_id = $this->comprobante->id;
				$observacion->save();
			}
			foreach($this->errores as $error){
				$error->comprobante_id = $this->comprobante->id;
				$error->save();
			}
			foreach($this->eventos as $evento){
				$evento->comprobante_id = $this->comprobante->id;
				$evento->save();
			}
			foreach($this->detalles as $detalle){
				$detalle->comprobante_id = $this->comprobante->id;
				$detalle->save();
			}
			// $this->cliente->comprobante_id = $this->comprobante->id;
			// $this->cliente->save();
			// $this->comprobante->cliente;
			$this->comprobante->detalles;
			$this->comprobante->alicuotas;
			$this->comprobante->observaciones;
			$this->comprobante->errores;
			$this->comprobante->eventos;
		}
	}


	/* CREAR URLS PARA EL MANEJO DE CLASES */
	private function setear_urls()
	{
		if ($this->homo) {
			#WSAA
			$this->URL_WSAA = config('facturacion.URL_WSAA_HOMO');
			$this->WSDL_WSAA = config('facturacion.WSDL_WSAA_HOMO');
			#COMP_ULTIMO
			$this->URL_ULT = config('facturacion.URL_ULT_HOMO');
			$this->WSDL_ULT = config('facturacion.WSDL_ULT_HOMO');
			#CAE SOLICITAR
			$this->URL_CAE = config('facturacion.URL_CAE_HOMO');
			$this->WSDL_CAE = config('facturacion.WSDL_CAE_HOMO');
			#TIPOS IVA
			$this->URL_TIPO_IVA = config('facturacion.URL_TIPO_IVA_HOMO');
			$this->WSDL_TIPO_IVA = config('facturacion.WSDL_TIPO_IVA_HOMO');
			#GET PERSONA
			$this->WSDL_PERSONA = config('facturacion.WSDL_PERSONA_HOMO');
			$this->URL_PERSONA = config('facturacion.URL_PERSONA_HOMO');
		} else {
			#WSAA
			$this->URL_WSAA = config('facturacion.URL_WSAA');
			$this->WSDL_WSAA = config('facturacion.WSDL_WSAA');
			#COMP_ULTIMO
			$this->URL_ULT = config('facturacion.URL_ULT');
			$this->WSDL_ULT = config('facturacion.WSDL_ULT');
			#COMP_CONSULT
			$this->URL_COMP = config('facturacion.URL_COMP');
			$this->WSDL_COMP = config('facturacion.WSDL_COMP');
			#CAE SOLICITAR
			$this->URL_CAE = config('facturacion.URL_CAE');
			$this->WSDL_CAE = config('facturacion.WSDL_CAE');
			#TIPOS IVA
			$this->URL_TIPO_IVA = config('facturacion.URL_TIPO_IVA');
			$this->WSDL_TIPO_IVA = config('facturacion.WSDL_TIPO_IVA');
			#GET PERSONA
			$this->WSDL_PERSONA = config('facturacion.WSDL_PERSONA');
			$this->URL_PERSONA = config('facturacion.URL_PERSONA');
		}
	}

	private function validar_atributos()
	{
		if($this->comprobante === null){
			trigger_error("Debe añadir un comprobante", E_USER_ERROR);
		}
		if ($this->comprobante->tipo === null) {
			trigger_error('Tipo de comprobante no puede ser nulo', E_USER_ERROR);
		}
		if ($this->comprobante->fecha === null) {
			trigger_error('Fecha de comprobante no puede ser nulo', E_USER_ERROR);
		}
		if ($this->comprobante->importe_total === null) {
			trigger_error('El importe total del comprobante no puede ser nulo', E_USER_ERROR);
		}
		if ($this->comprobante->importe_neto === null) {
			trigger_error('El importe neto del comprobante no puede ser nulo', E_USER_ERROR);
		}
		if ($this->comprobante->importe_iva === null) {
			trigger_error('El importe de iva del comprobante no puede ser nulo', E_USER_ERROR);
		}
		if(floatval($this->comprobante->importe_neto) > 0){
			$imp = $this->comprobante->importe_neto + $this->comprobante->importe_iva;
			if (round($this->comprobante->importe_total,2) != round($imp,2)) {
				trigger_error('El importe total ($'.$this->comprobante->importe_total.') debe coincidir con la suma del importe neto y el importe iva ($'.$imp.').', E_USER_ERROR);
			}
		}
		if($this->comprobante->tipo != 91 && $this->comprobante->tipo != 100 && $this->alicuotas === null){
			trigger_error('Para comprobantes enviados a AFIP debe añadir el array de alicuotas', E_USER_ERROR);
		}
		if($this->detalles === null){
			trigger_error('Debe añadir el array de detalles del comprobante', E_USER_ERROR);
		}
	}

	public static function facturacion_path($path)
	{
		return public_path() . '/facturacion/' . $path;
	}

	public function guardar_pdf(){
		$codigo = $this->cuit_emisor.$this->comprobante->tipo.$this->punto_venta.$this->comprobante->cae.strval(date('Ymd', strtotime($this->comprobante->vencimiento_cae)));
		$bar = new DNS1D;
		$barcode = '<img src="data:image/png;base64,' . $bar->getBarcodePNG("$codigo", "I25+",1.2,45,array(1,1,1)) . '" alt="barcode"   />';
		$name = $this->comprobante->tipo."_".str_pad($this->comprobante->numero,8,'0',STR_PAD_LEFT).'.pdf';
		fopen($this->facturacion_path($name),'w+');
		$pdf = PDF::loadView('pdf.comprobante', [
			'barcode'=>$barcode,
			'codigo' => $codigo, 
			'comprobante'=>$this->comprobante,
			'detalles'=>$this->detalles, 
			'cliente' => $this->cliente]);
		$pdf->save($this->facturacion_path($name));
	}
}
