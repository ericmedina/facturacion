<?php  
/**
* 
*/
namespace Pampadev;


use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;
use SoapClient;

class Facturacion
{

	#--------------------------------------------------------------------------
	#URLS Y CERTIFICADOS
	#==========================================================================

	public $cert;
	public $key;
	public $homo;
	public $PASSPHRASE;
	public $URL_WSAA;
	public $WSDL_WSAA;
	public $URL_ULT;
	public $WSDL_ULT;
	public $WSDL_CAE;
	public $URL_CAE;
	public $URL_TIPO_IVA;
	public $WSDL_PERSONA;
	public $URL_PERSONA;
	public $WSDL_COMP;
	public $URL_COMP;

	#-----------------------------------------------------------------------------
	#EMISOR
	#=============================================================================
	public $cuit_emisor;
	public $punto_venta;
	public $concepto;

	#-----------------------------------------------------------------------------
	#CREDENCIALES
	#=============================================================================

	public $token;
	public $sign;

	#-----------------------------------------------------------------------------
	#DATOS CLIENTE
	#=============================================================================
	public $tipo_doc;
	public $doc_nro;

	#-----------------------------------------------------------------------------
	#DATOS COMPROBANTE
	#=============================================================================
	public $cantidad_registros = 1;
	public $tipo_comprobante;
	public $comprobante_desde;
	public $comprobante_hasta;
	public $comprobante_fecha;
	public $fecha_servicio_desde;
	public $fecha_servicio_hasta;
	public $fecha_vencimiento_pago;
	public $importe_total;
	public $importe_total_concepto="0";
	public $importe_neto;
	public $importe_iva;
	public $importe_operaciones_externas="0";
	public $importe_tributo="0";
	public $moneda_id = "PES";
	public $moneda_cotiz ="1";
	public $IVA;
	public $Detalle;
	public $assoc;

	#------------------------------------------------------------------------------
	#ATRIBUTOS ALMACENAMIENTO RESPUESTA
	#==============================================================================

	public $ultimo_comprobante;
	public $cae;
	public $vencimiento_cae;
	public $resultado;
	public $observaciones;
	public $errores;

	
	function __construct()
	{
		
		$this->homo= config('facturacion.homo');
		$this->PASSPHRASE=config('facturacion.PASSPHRASE');
		if($this->homo){
			#WSAA
		  	$this->URL_WSAA= config('facturacion.URL_WSAA_HOMO');
		  	$this->WSDL_WSAA=config('facturacion.WSDL_WSAA_HOMO');
		  	#COMP_ULTIMO
		  	$this->URL_ULT= config('facturacion.URL_ULT_HOMO');
		  	$this->WSDL_ULT= config('facturacion.WSDL_ULT_HOMO');
		  	#CAE SOLICITAR
		  	$this->URL_CAE = config('facturacion.URL_CAE_HOMO');
		  	$this->WSDL_CAE = config('facturacion.WSDL_CAE_HOMO');
		  	#TIPOS IVA
		  	$this->URL_TIPO_IVA = config('facturacion.URL_TIPO_IVA_HOMO');
		  	$this->WSDL_TIPO_IVA = config('facturacion.WSDL_TIPO_IVA_HOMO');
		  	#GET PERSONA
		  	$this->WSDL_PERSONA = config('facturacion.WSDL_PERSONA_HOMO');
			$this->URL_PERSONA = config('facturacion.URL_PERSONA_HOMO');

		}else{
			#WSAA
		  	$this->URL_WSAA= config('facturacion.URL_WSAA');
		  	$this->WSDL_WSAA=config('facturacion.WSDL_WSAA');
		  	#COMP_ULTIMO
			$this->URL_ULT= config('facturacion.URL_ULT');
		  	$this->WSDL_ULT= config('facturacion.WSDL_ULT');
		  	#COMP_CONSULT
			$this->URL_COMP= config('facturacion.URL_COMP');
		  	$this->WSDL_COMP= config('facturacion.WSDL_COMP');
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
	#------------------------------------------------------------------------------
	#FUNCION UTILIZABLE DE WSAA CREA XML TA.XML
	#crea credenciales en variables $token y $sign
	#==============================================================================
	public function Autorizacion($ws)
	{
		#CHECKEA SI YA EXISTE ACCESO
		if(file_exists($this->cert)){
			if(file_exists(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA.xml")){
				$xml = simplexml_load_file(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA.xml");
				$exp = $xml->header->expirationTime;
				$now = date('c',date('U'));
			}else{
				$exp= 0;
				$now=1;
			}
		}else{
			$exp= 0;
			$now=1;
		}
		#SI EXPIRO EL TICKET PEDIS UNO NUEVO
		if($now>$exp){
			#INICIA Y CHECKEA SI EXISTE EL CERIFICADO

			ini_set("soap.wsdl_cache_enabled", "0");
			if (!file_exists($this->cert)) {exit("Failed to open ".$this->cert."\n");}
			if (!file_exists($this->key)) {exit("Failed to open ".$this->key."\n");}
			Url();

			#CREAR TICKET DE ACCESO
			$TRA = new \SimpleXMLElement(
		    '<?xml version="1.0" encoding="UTF-8"?>' .
		    '<loginTicketRequest version="1.0">'.
		    '</loginTicketRequest>');
			$TRA->addChild('header');
			$TRA->header->addChild('uniqueId',date('U'));
			$TRA->header->addChild('generationTime',date('c',date('U')-60));
			$TRA->header->addChild('expirationTime',date('c',date('U')+60));
			$TRA->addChild('service',$ws);
			$TRA->asXML(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA.xml");

			#FIRMA TICKET DE ACCESO CON CERTIFICADO

			$STATUS=openssl_pkcs7_sign(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA.xml", storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA.tmp", "file://".$this->cert,
			    array("file://".$this->key, $this->PASSPHRASE),
			    array(),
			    !PKCS7_DETACHED
			    );
			  if (!$STATUS) {exit("ERROR generating PKCS#7 signature\n");}
			  $inf=fopen(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA.tmp", "r");
			  $i=0;
			  $CMS="";
			  while (!feof($inf)) 
			    { 
			      $buffer=fgets($inf);
			      if ( $i++ >= 4 ) {$CMS.=$buffer;}
			    }
			  fclose($inf);
				$client=new \SoapClient($this->WSDL_WSAA, array(
			          'soap_version'   => SOAP_1_2,
			          'location'       => $this->URL_WSAA,
			          'trace'          => 1
			          )); 
			  $results=$client->loginCms(array('in0'=>$CMS));
			  if (is_soap_fault($results)) 
			    {
			      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
			    }
			#Genera XML
			file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA.xml", $results->loginCmsReturn);

		}
		$xml = json_encode(simplexml_load_file(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA.xml"));
		$TA = json_decode($xml,true);
		$token = $TA['credentials']['token'];
		$sign = $TA['credentials']['sign'];
		$this->token = $token;
		$this->sign = $sign;
	}

	#-------------------------------------------------------------------------------
	#FUNCION ULTIMO COMPROBANTE AUTORIZADO
	#Llena el atributo $ultimo_comprobante con el ultimo generado.
	#===============================================================================
	public function UltimoAutorizado(){
		$this->Autorizacion('wsfe');
		$cuitemisor =(float) $this->cuit_emisor;
		$auth = array('Token'=>$this->token, 'Sign'=>$this->sign, 'Cuit'=>$cuitemisor);
		$FECompUltimoAutorizado = array('Auth'=> $auth, 'PtoVta'=>intval($this->punto_venta), 'CbteTipo'=>intval($this->tipo_comprobante));
		//INICIAR CLIENTE SOAP
		 $client=new \SoapClient($this->WSDL_ULT, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_ULT,
		    'trace'          => 1
		    ));
		//ENVIAR DATOS
		$results=$client->FECompUltimoAutorizado($FECompUltimoAutorizado);
		 $json_obj = json_encode($results);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-compultimoautorizado.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-compultimoautorizado.json",$json_obj);
		$response = json_decode($json_obj, true);
		return $response['FECompUltimoAutorizadoResult']['CbteNro'];
	}

	#-------------------------------------------------------------------------------
	#FUNCION OBTENER NUMERO DE TIPO DE COMPROBANTE A PARTIR DE LETRAS
	#ENTRADA: A, B, C, NCA, NCB, NCC, NDA, NDB, NDC, R, P, FP
	#SALIDA:  01, 06, 11, 03, 08, 13, 02, 07, 12, 91, 100, 101
	#===============================================================================
	public static function tipo_comprobante($comprobante){
	 	if($comprobante=='A'){
	        $tipo_comprobante = 1;
	    }else if($comprobante=='B'){
	        $tipo_comprobante = 6;
	    }else if($comprobante == "C"){
	    	$tipo_comprobante = 11;
	    }else if($comprobante =="NCA"){
	    	$tipo_comprobante = 3;
	    }else if($comprobante == "NCB"){
	    	$tipo_comprobante = 8;
	    }else if($comprobante == "NCC"){
	    	$tipo_comprobante = 13;
	    }else if($comprobante == "NDA"){
	    	$tipo_comprobante = 2;
	    }else if($comprobante == "NDB"){
	    	$tipo_comprobante = 7;
	    }else if($comprobante == "NDC"){
	    	$tipo_comprobante = 12;
	    }else if($comprobante == 'R'){
	        $tipo_comprobante = 91;
	    }else if($comprobante == 'P'){
	        $tipo_comprobante= 100;
	    }else if($comprobante == 'FP'){
	        $tipo_comprobante= 101;
	    }
	    return $tipo_comprobante;
	}
	#-------------------------------------------------------------------------------
	#ARMADO COMPROBANTE  ELECTRONICO
	#SALIDA: RESULTADO, CAE, VENCIMIENTO_CAE, OBSERVACIONES, ERRORES
	#===============================================================================
	public function CAE_Factura_A(){
		$this->Autorizacion('wsfe');
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if(intval($this->concepto)>1){
			$this->fecha_servicio_desde =$this->comprobante_fecha;
			$this->fecha_servicio_hasta =$this->comprobante_fecha;
			$this->fecha_vencimiento_pago = $this->comprobante_fecha;
		}else{
			$this->fecha_servicio_desde ="";
			$this->fecha_servicio_hasta ="";
			$this->fecha_vencimiento_pago ="";
		}

		#LLAMAMOS CLIENTE SOAP

		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_CAE,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq= array('CantReg'=>$this->cantidad_registros, 'PtoVta'=>$this->punto_venta, 'CbteTipo' => 1);

		#IVA
		foreach ($this->IVA as $iva => $val) {
			$AlicIva[] = array('Id'=>$val['codigo'], 'BaseImp'=>$val['importe_neto'], 'Importe' =>$val['importe_iva']);
		}

		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto'=> $this->concepto,'DocTipo'=>$this->tipo_doc, 'DocNro'=> doubleval($this->doc_nro), 'CbteDesde'=>$this->comprobante_desde, 'CbteHasta'=> $this->comprobante_hasta, 'CbteFch'=>$this->comprobante_fecha, 'ImpTotal' => $this->importe_total, 'ImpTotConc' =>$this->importe_total_concepto, 'ImpNeto' =>$this->importe_neto, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => $this->importe_iva, 'FchServDesde'=>$this->fecha_servicio_desde, 'FchServHasta'=>$this->fecha_servicio_hasta, 'FchVtoPago'=>$this->fecha_vencimiento_pago, 'MonId'=>$this->moneda_id, 'MonCotiz'=>$this->moneda_cotiz, 'Iva'=>$AlicIva);


		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest'=>$FECAEDetRequest);
		$FeCAEReq = array('FeCabReq'=> $FeCabReq, 'FeDetReq'=>$FeDetReq);
		$FECAESolicitar = array('Auth'=>$Auth, 'FeCAEReq'=>$FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-caesolicitar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		#LEEMOS LOS DATOS

		$data = file_get_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json");
		$json = json_decode($data, true);
		$response=array('success'=>false);
		$contenido = $json['FECAESolicitarResult'];
		if(array_key_exists('FeDetResp',$contenido)){
			if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="A"){
				$this->resultado = "A";
				$this->cae = $contenido['FeDetResp']['FECAEDetResponse']['CAE'];
				$this->vencimiento_cae = $contenido['FeDetResp']['FECAEDetResponse']['CAEFchVto'];
				if(array_key_exists('Obs', $contenido['FeDetResp']['FECAEDetResponse'])){
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Obs'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
				}
			}else if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="R"){
				$this->resultado = "R";
				if(array_key_exists('Observaciones', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Observaciones'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->observaciones = $arr;
				}if(array_key_exists('Errors', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido)){
					$arr = array();
					foreach( $contenido['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido['FeCabResp'])){
					$arr = array();
					foreach($contenido['FeCabResp']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}
			}
		}else{
			$this->resultado = "R";
			$this->errores = $contenido;
		}
	}

	public function CAE_Factura_B(){
		$this->Autorizacion("wsfe");
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if(intval($this->concepto)>1){
			$this->fecha_servicio_desde =$this->comprobante_fecha;
			$this->fecha_servicio_hasta =$this->comprobante_fecha;
			$this->fecha_vencimiento_pago = $this->comprobante_fecha;
		}else{
			$this->fecha_servicio_desde ="";
			$this->fecha_servicio_hasta ="";
			$this->fecha_vencimiento_pago ="";
		}

		#LLAMAMOS CLIENTE SOAP

		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_CAE,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq= array('CantReg'=>$this->cantidad_registros, 'PtoVta'=>$this->punto_venta, 'CbteTipo' => 6);

		#IVA
		foreach ($this->IVA as $iva => $val) {
			$AlicIva[] = array('Id'=>$val['codigo'], 'BaseImp'=>$val['importe_neto'], 'Importe' =>$val['importe_iva']);
		}

		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto'=> $this->concepto,'DocTipo'=>$this->tipo_doc, 'DocNro'=> doubleval($this->doc_nro), 'CbteDesde'=>$this->comprobante_desde, 'CbteHasta'=> $this->comprobante_hasta, 'CbteFch'=>$this->comprobante_fecha, 'ImpTotal' => $this->importe_total, 'ImpTotConc' =>$this->importe_total_concepto, 'ImpNeto' =>$this->importe_neto, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => $this->importe_iva, 'FchServDesde'=>$this->fecha_servicio_desde, 'FchServHasta'=>$this->fecha_servicio_hasta, 'FchVtoPago'=>$this->fecha_vencimiento_pago, 'MonId'=>$this->moneda_id, 'MonCotiz'=>$this->moneda_cotiz, 'Iva'=>$AlicIva);


		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest'=>$FECAEDetRequest);
		$FeCAEReq = array('FeCabReq'=> $FeCabReq, 'FeDetReq'=>$FeDetReq);
		$FECAESolicitar = array('Auth'=>$Auth, 'FeCAEReq'=>$FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-caesolicitar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		#LEEMOS LOS DATOS

		$data = file_get_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json");
		$json = json_decode($data, true);
		$response=array('success'=>false);
		$contenido = $json['FECAESolicitarResult'];
		if(array_key_exists('FeDetResp',$contenido)){
			if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="A"){
				$this->resultado = "A";
				$this->cae = $contenido['FeDetResp']['FECAEDetResponse']['CAE'];
				$this->vencimiento_cae = $contenido['FeDetResp']['FECAEDetResponse']['CAEFchVto'];
				if(array_key_exists('Obs', $contenido['FeDetResp']['FECAEDetResponse'])){
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Obs'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
				}
			}else if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="R"){
				$this->resultado = "R";
				if(array_key_exists('Observaciones', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Observaciones'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->observaciones = $arr;
				}if(array_key_exists('Errors', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido)){
					$arr = array();
					foreach( $contenido['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido['FeCabResp'])){
					$arr = array();
					foreach($contenido['FeCabResp']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}
			}
		}else{
			$this->resultado = "R";
			$this->errores = $contenido;
		}
	}

	public function CAE_Factura_C(){
		$this->Autorizacion("wsfe");
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if(intval($this->concepto)>1){
			$this->fecha_servicio_desde =$this->comprobante_fecha;
			$this->fecha_servicio_hasta =$this->comprobante_fecha;
			$this->fecha_vencimiento_pago = $this->comprobante_fecha;
		}else{
			$this->fecha_servicio_desde ="";
			$this->fecha_servicio_hasta ="";
			$this->fecha_vencimiento_pago ="";
		}

		#LLAMAMOS CLIENTE SOAP

		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_CAE,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq= array('CantReg'=>$this->cantidad_registros, 'PtoVta'=>$this->punto_venta, 'CbteTipo' => 11);

		#IVA
		foreach ($this->IVA as $iva => $val) {
			$AlicIva[] = array('Id'=>$val['codigo'], 'BaseImp'=>$val['importe_neto'], 'Importe' =>$val['importe_iva']);
		}

		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto'=> $this->concepto,'DocTipo'=>$this->tipo_doc, 'DocNro'=> doubleval($this->doc_nro), 'CbteDesde'=>$this->comprobante_desde, 'CbteHasta'=> $this->comprobante_hasta, 'CbteFch'=>$this->comprobante_fecha, 'ImpTotal' => $this->importe_total, 'ImpTotConc' =>$this->importe_total_concepto, 'ImpNeto' =>$this->importe_total, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => 0, 'FchServDesde'=>$this->fecha_servicio_desde, 'FchServHasta'=>$this->fecha_servicio_hasta, 'FchVtoPago'=>$this->fecha_vencimiento_pago, 'MonId'=>$this->moneda_id, 'MonCotiz'=>$this->moneda_cotiz);


		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest'=>$FECAEDetRequest);
		$FeCAEReq = array('FeCabReq'=> $FeCabReq, 'FeDetReq'=>$FeDetReq);
		$FECAESolicitar = array('Auth'=>$Auth, 'FeCAEReq'=>$FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-caesolicitar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		#LEEMOS LOS DATOS

		$data = file_get_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json");
		$json = json_decode($data, true);
		$response=array('success'=>false);
		$contenido = $json['FECAESolicitarResult'];
		if(array_key_exists('FeDetResp',$contenido)){
			if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="A"){
				$this->resultado = "A";
				$this->cae = $contenido['FeDetResp']['FECAEDetResponse']['CAE'];
				$this->vencimiento_cae = $contenido['FeDetResp']['FECAEDetResponse']['CAEFchVto'];
				if(array_key_exists('Obs', $contenido['FeDetResp']['FECAEDetResponse'])){
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Obs'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
				}
			}else if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="R"){
				$this->resultado = "R";
				if(array_key_exists('Observaciones', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Observaciones'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->observaciones = $arr;
				}if(array_key_exists('Errors', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido)){
					$arr = array();
					foreach( $contenido['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido['FeCabResp'])){
					$arr = array();
					foreach($contenido['FeCabResp']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}
			}
		}else{
			$this->resultado = "R";
			$this->errores = $contenido;
		}
	}

	public function CAE_Credito_A(){
		$this->Autorizacion("wsfe");
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if(intval($this->concepto)>1){
			$this->fecha_servicio_desde =$this->comprobante_fecha;
			$this->fecha_servicio_hasta =$this->comprobante_fecha;
			$this->fecha_vencimiento_pago = $this->comprobante_fecha;
		}else{
			$this->fecha_servicio_desde ="";
			$this->fecha_servicio_hasta ="";
			$this->fecha_vencimiento_pago ="";
		}

		#LLAMAMOS CLIENTE SOAP

		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_CAE,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq= array('CantReg'=>$this->cantidad_registros, 'PtoVta'=>$this->punto_venta, 'CbteTipo' => 3);

		#IVA
		foreach ($this->IVA as $iva => $val) {
			$AlicIva[] = array('Id'=>$val['codigo'], 'BaseImp'=>$val['importe_neto'], 'Importe' =>$val['importe_iva']);
		}

		$array_assoc = array('Tipo'=>01, 'PtoVta' => $this->punto_venta, 'Nro'=>$this->assoc);
		$cbtes_assoc = array('CbteAsoc' => $array_assoc);
		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto'=> $this->concepto,'DocTipo'=>$this->tipo_doc, 'DocNro'=> doubleval($this->doc_nro), 'CbteDesde'=>$this->comprobante_desde, 'CbteHasta'=> $this->comprobante_hasta, 'CbteFch'=>$this->comprobante_fecha, 'ImpTotal' => $this->importe_total, 'ImpTotConc' =>$this->importe_total_concepto, 'ImpNeto' =>$this->importe_neto, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => $this->importe_iva, 'FchServDesde'=>$this->fecha_servicio_desde, 'FchServHasta'=>$this->fecha_servicio_hasta, 'FchVtoPago'=>$this->fecha_vencimiento_pago, 'MonId'=>$this->moneda_id, 'MonCotiz'=>$this->moneda_cotiz,'CbtesAsoc'=>$cbtes_assoc, 'Iva'=>$AlicIva);

		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest'=>$FECAEDetRequest);
		$FeCAEReq = array('FeCabReq'=> $FeCabReq, 'FeDetReq'=>$FeDetReq);
		$FECAESolicitar = array('Auth'=>$Auth, 'FeCAEReq'=>$FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-caesolicitar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		#LEEMOS LOS DATOS

		$data = file_get_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json");
		$json = json_decode($data, true);
		$response=array('success'=>false);
		$contenido = $json['FECAESolicitarResult'];
		if(array_key_exists('FeDetResp',$contenido)){
			if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="A"){
				$this->resultado = "A";
				$this->cae = $contenido['FeDetResp']['FECAEDetResponse']['CAE'];
				$this->vencimiento_cae = $contenido['FeDetResp']['FECAEDetResponse']['CAEFchVto'];
				if(array_key_exists('Obs', $contenido['FeDetResp']['FECAEDetResponse'])){
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Obs'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
				}
			}else if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="R"){
				$this->resultado = "R";
				if(array_key_exists('Observaciones', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Observaciones'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->observaciones = $arr;
				}if(array_key_exists('Errors', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido)){
					$arr = array();
					foreach( $contenido['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido['FeCabResp'])){
					$arr = array();
					foreach($contenido['FeCabResp']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}
			}
		}else{
			$this->resultado = "R";
			$this->errores = $contenido;
		}
	}

	public function CAE_Credito_B(){
		$this->Autorizacion("wsfe");
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if(intval($this->concepto)>1){
			$this->fecha_servicio_desde =$this->comprobante_fecha;
			$this->fecha_servicio_hasta =$this->comprobante_fecha;
			$this->fecha_vencimiento_pago = $this->comprobante_fecha;
		}else{
			$this->fecha_servicio_desde ="";
			$this->fecha_servicio_hasta ="";
			$this->fecha_vencimiento_pago ="";
		}

		#LLAMAMOS CLIENTE SOAP

		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_CAE,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq= array('CantReg'=>$this->cantidad_registros, 'PtoVta'=>$this->punto_venta, 'CbteTipo' => 8);

		#IVA
		foreach ($this->IVA as $iva => $val) {
			$AlicIva[] = array('Id'=>$val['codigo'], 'BaseImp'=>$val['importe_neto'], 'Importe' =>$val['importe_iva']);
		}

		$array_assoc = array('Tipo'=>06, 'PtoVta' => $this->punto_venta, 'Nro'=>$this->assoc);
		$cbtes_assoc = array('CbteAsoc' => $array_assoc);
		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto'=> $this->concepto,'DocTipo'=>$this->tipo_doc, 'DocNro'=> doubleval($this->doc_nro), 'CbteDesde'=>$this->comprobante_desde, 'CbteHasta'=> $this->comprobante_hasta, 'CbteFch'=>$this->comprobante_fecha, 'ImpTotal' => $this->importe_total, 'ImpTotConc' =>$this->importe_total_concepto, 'ImpNeto' =>$this->importe_neto, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => $this->importe_iva, 'FchServDesde'=>$this->fecha_servicio_desde, 'FchServHasta'=>$this->fecha_servicio_hasta, 'FchVtoPago'=>$this->fecha_vencimiento_pago, 'MonId'=>$this->moneda_id, 'MonCotiz'=>$this->moneda_cotiz,'CbtesAsoc'=>$cbtes_assoc, 'Iva'=>$AlicIva);

		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest'=>$FECAEDetRequest);
		$FeCAEReq = array('FeCabReq'=> $FeCabReq, 'FeDetReq'=>$FeDetReq);
		$FECAESolicitar = array('Auth'=>$Auth, 'FeCAEReq'=>$FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-caesolicitar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		#LEEMOS LOS DATOS

		$data = file_get_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json");
		$json = json_decode($data, true);
		$response=array('success'=>false);
		$contenido = $json['FECAESolicitarResult'];
		if(array_key_exists('FeDetResp',$contenido)){
			if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="A"){
				$this->resultado = "A";
				$this->cae = $contenido['FeDetResp']['FECAEDetResponse']['CAE'];
				$this->vencimiento_cae = $contenido['FeDetResp']['FECAEDetResponse']['CAEFchVto'];
				if(array_key_exists('Obs', $contenido['FeDetResp']['FECAEDetResponse'])){
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Obs'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
				}
			}else if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="R"){
				$this->resultado = "R";
				if(array_key_exists('Observaciones', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Observaciones'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->observaciones = $arr;
				}if(array_key_exists('Errors', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido)){
					$arr = array();
					foreach( $contenido['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido['FeCabResp'])){
					$arr = array();
					foreach($contenido['FeCabResp']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}
			}
		}else{
			$this->resultado = "R";
			$this->errores = $contenido;
		}
	}
	public function CAE_Credito_C(){
		$this->Autorizacion("wsfe");
		#SETEAMOS FECHA SERVICIO SOLO SI EL CONCEPTO NO ES DE PRODUCTOS
		if(intval($this->concepto)>1){
			$this->fecha_servicio_desde =$this->comprobante_fecha;
			$this->fecha_servicio_hasta =$this->comprobante_fecha;
			$this->fecha_vencimiento_pago = $this->comprobante_fecha;
		}else{
			$this->fecha_servicio_desde ="";
			$this->fecha_servicio_hasta ="";
			$this->fecha_vencimiento_pago ="";
		}

		#LLAMAMOS CLIENTE SOAP

		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_CAE,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		#CABECERA DEL COMPROBANTE

		$FeCabReq= array('CantReg'=>$this->cantidad_registros, 'PtoVta'=>$this->punto_venta, 'CbteTipo' => 13);

		#IVA
		foreach ($this->IVA as $iva => $val) {
			$AlicIva[] = array('Id'=>$val['codigo'], 'BaseImp'=>$val['importe_neto'], 'Importe' =>$val['importe_iva']);
		}
		$array_assoc = array('Tipo'=>11, 'PtoVta' => $this->punto_venta, 'Nro'=>$this->assoc);
		$cbtes_assoc = array('CbteAsoc' => $array_assoc);

		#CUERPO DEL COMPROBANTE
		$FECAEDetRequest = array('Concepto'=> $this->concepto,'DocTipo'=>$this->tipo_doc, 'DocNro'=> doubleval($this->doc_nro), 'CbteDesde'=>$this->comprobante_desde, 'CbteHasta'=> $this->comprobante_hasta, 'CbteFch'=>$this->comprobante_fecha, 'ImpTotal' => $this->importe_total, 'ImpTotConc' =>$this->importe_total_concepto, 'ImpNeto' =>$this->importe_total, 'ImpOpEx' => $this->importe_operaciones_externas, 'ImpTrib' => $this->importe_tributo, 'ImpIVA' => 0, 'FchServDesde'=>$this->fecha_servicio_desde, 'FchServHasta'=>$this->fecha_servicio_hasta, 'FchVtoPago'=>$this->fecha_vencimiento_pago, 'MonId'=>$this->moneda_id, 'MonCotiz'=>$this->moneda_cotiz, 'CbtesAsoc'=>$cbtes_assoc);


		#DAMOS FORMATO AL MENSAJE
		$FeDetReq = array('FECAEDetRequest'=>$FECAEDetRequest);
		$FeCAEReq = array('FeCabReq'=> $FeCabReq, 'FeDetReq'=>$FeDetReq);
		$FECAESolicitar = array('Auth'=>$Auth, 'FeCAEReq'=>$FeCAEReq);
		#ENVIAMOS EL REQUEST

		$results = $client->FECAESolicitar($FECAESolicitar);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-caesolicitar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		#LEEMOS LOS DATOS

		$data = file_get_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-caesolicitar.json");
		$json = json_decode($data, true);
		$response=array('success'=>false);
		$contenido = $json['FECAESolicitarResult'];
		if(array_key_exists('FeDetResp',$contenido)){
			if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="A"){
				$this->resultado = "A";
				$this->cae = $contenido['FeDetResp']['FECAEDetResponse']['CAE'];
				$this->vencimiento_cae = $contenido['FeDetResp']['FECAEDetResponse']['CAEFchVto'];
				if(array_key_exists('Obs', $contenido['FeDetResp']['FECAEDetResponse'])){
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Obs'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
				}
			}else if($contenido['FeDetResp']['FECAEDetResponse']['Resultado']=="R"){
				$this->resultado = "R";
				if(array_key_exists('Observaciones', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Observaciones'] as $error){
						if(!array_key_exists('Obs', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->observaciones = $arr;
				}if(array_key_exists('Errors', $contenido['FeDetResp']['FECAEDetResponse'])){
					$arr = array();
					foreach($contenido['FeDetResp']['FECAEDetResponse']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido)){
					$arr = array();
					foreach( $contenido['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}if(array_key_exists('Errors', $contenido['FeCabResp'])){
					$arr = array();
					foreach($contenido['FeCabResp']['Errors'] as $error){
						if(!array_key_exists('Err', $contenido)){
							array_push($arr, $this->traducir_error($error['Code']));
						}else{
							foreach ($error as $err) {
								array_push($arr, $this->traducir_error($err['Code']));
							}
						}
					}
					$this->errores = $arr;
				}
			}
		}else{
			$this->resultado = "R";
			$this->errores = $contenido;
		}
	}


	#-----------------------------------------------------------------------------------
	#FUNCION OBTENER CONCEPTO
	#ENTRADA: PRODUCTOS, SERVICIOS, PRODUCTOS Y SERVICIOS
	#SALIDA: 1, 2, 3
	#===================================================================================

	public function tipo_actividad($actividad){
		if($actividad == 'productos'){
			return 1;
		}elseif($actividad == 'servicios'){
			return 2;
		}elseif($actividad == 'productos y servicios'){
			return 3;
		}
	}

	#------------------------------------------------------------------------------------
	#FUNCION OBTENER TIPO DE DOC
	#ENTRADA NUMERO CUIT
	#SALIDA DOC_TIPO: 80(CUIT), 96(DNI), 99(OTRO)
	#====================================================================================

	public function doc_tipo($doc){
		$dni = str_replace("-", "", str_replace("/", "", $doc));
		if(strlen($dni) == 8){
			return 96;
		}elseif(strlen($dni) == 11){
			return 80;
		}else{
			return 99;
		}
	}

	#------------------------------------------------------------------------------------
	#FUNCION LLAMADA A WS AFIP TIPOS IVA
	#ENVIO TOKENS Y CUIT
	#RESPUESTA TIPOS DE IVA
	#====================================================================================

	public function tipos_iva(){

		$this->Autorizacion("wsfe");

		#LLAMAMOS CLIENTE SOAP



		$client=new \SoapClient($this->WSDL_CAE, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_TIPO_IVA,
		    'trace'          => 1
		));

		#CREDENCIALES

		$Auth = array('Token'=>$this->token, 'Sign' => $this->sign, 'Cuit' => floatVal($this->cuit_emisor));

		$FEParamGetTiposIva = array('Auth'=>$Auth);
		#ENVIAMOS EL REQUEST

		$results = $client->FEParamGetTiposIva($FEParamGetTiposIva);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-tiposiva.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-tiposiva.json",$json_obj);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
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
		if(file_exists($this->cert)){
			if(file_exists(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA_persona.xml")){
				$xml = simplexml_load_file(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA_persona.xml");
				$exp = $xml->header->expirationTime;
				$now = date('c',date('U'));
			}else{
				$exp= 0;
				$now=1;
			}
		}else{
			$exp= 0;
			$now=1;
		}
		#SI EXPIRO EL TICKET PEDIS UNO NUEVO
		//if($now>$exp){
			#INICIA Y CHECKEA SI EXISTE EL CERIFICADO

			ini_set("soap.wsdl_cache_enabled", "0");
			if (!file_exists($this->cert)) {exit("Failed to open ".$this->cert."\n");}
			if (!file_exists($this->key)) {exit("Failed to open ".$this->key."\n");}
			Url();

			#CREAR TICKET DE ACCESO
			$TRA = new \SimpleXMLElement(
		    '<?xml version="1.0" encoding="UTF-8"?>' .
		    '<loginTicketRequest version="1.0">'.
		    '</loginTicketRequest>');
			$TRA->addChild('header');
			$TRA->header->addChild('uniqueId',date('U'));
			$TRA->header->addChild('generationTime',date('c',date('U')-60));
			$TRA->header->addChild('expirationTime',date('c',date('U')+60));
			$TRA->addChild('service',$ws);
			$TRA->asXML(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA_persona.xml");

			#FIRMA TICKET DE ACCESO CON CERTIFICADO

			$STATUS=openssl_pkcs7_sign(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA_persona.xml", storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA_persona.tmp", "file://".$this->cert,
			    array("file://".$this->key, $this->PASSPHRASE),
			    array(),
			    !PKCS7_DETACHED
			    );
			  if (!$STATUS) {exit("ERROR generating PKCS#7 signature\n");}
			  $inf=fopen(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TRA_persona.tmp", "r");
			  $i=0;
			  $CMS="";
			  while (!feof($inf)) 
			    { 
			      $buffer=fgets($inf);
			      if ( $i++ >= 4 ) {$CMS.=$buffer;}
			    }
			  fclose($inf);
				$client=new \SoapClient($this->WSDL_WSAA, array(
			          'soap_version'   => SOAP_1_2,
			          'location'       => $this->URL_WSAA,
			          'trace'          => 1
			          )); 
			  $results=$client->loginCms(array('in0'=>$CMS));
			  if (is_soap_fault($results)) 
			    {
			      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
			    }
			#Genera XML
			file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA_persona.xml", $results->loginCmsReturn);

		//}
		$xml = json_encode(simplexml_load_file(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_TA_persona.xml"));
		$TA = json_decode($xml,true);
		$token = $TA['credentials']['token'];
		$sign = $TA['credentials']['sign'];
		$this->token = $token;
		$this->sign = $sign;
	}


	public function persona($cuit_persona){
		$this->Autorizacion_persona("ws_sr_constancia_inscripcion");

		#LLAMAMOS CLIENTE SOAP

		$context = stream_context_create(array(
	        'ssl' => array(
	        'verify_peer' => false,
	        'verify_peer_name' => false,
	        'allow_self_signed' => true
	        )
		));

		$client = new \SoapClient("https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA5?WSDL", array(
		    'soap_version'   	=> SOAP_1_1,
		    'trace'          	=> 0,
		    'exceptions' 		=> 0,
		    'stream_context'	=> $context,
		));

		#CREDENCIALES

		$getPersona = array('token'=>$this->token, 'sign' => $this->sign, 'cuitRepresentada' => floatVal($this->cuit_emisor), 'idPersona' => floatval($cuit_persona));

		$FEParamGetTiposIva = array('getPersona'=>$getPersona);
		#ENVIAMOS EL REQUEST
		$results = $client->getPersona($getPersona);

		#GUARDAMOS RESPONSE

		$json_obj = json_encode($results);
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-getPersona.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-Persona.json",$json_obj);
		if (is_soap_fault($results)) 
	    {
	      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
	    }

	}

	public function Consultar(){
		$this->Autorizacion("wsfe");

		$cuitemisor =(float) $this->cuit_emisor;
		$auth = array('Token'=>$this->token, 'Sign'=>$this->sign, 'Cuit'=>$cuitemisor);
		$FeCompConsReq= array('CbteTipo'=>$this->tipo_comprobante, 'CbteNro'=>$this->comprobante_desde, 'PtoVta' => $this->punto_venta);

		$FECompConsultar = array('Auth' =>$auth, 'FeCompConsReq' => $FeCompConsReq);

		//INICIAR CLIENTE SOAP
		 $client=new \SoapClient($this->WSDL_COMP, array(
		    'soap_version'   => SOAP_1_2,
		    'location'       => $this->URL_COMP,
		    'trace'          => 1
		    ));
		//ENVIAR DATOS
		$results=$client->FECompConsultar($FECompConsultar);
		 $json_obj = json_encode($results);
		 if (is_soap_fault($results)) 
		    {
		      exit("SOAP Fault: ".$results->faultcode."\n".$results->faultstring."\n");
		    }

		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_request-compconsultar.xml",$client->__getLastRequest());
		file_put_contents(storage_path()."/app/public/facturacion/xml/".$this->cuit_emisor.'/'.$this->cuit_emisor."_response-compconsultar.json",$json_obj);
		$response = json_decode($json_obj, true);
		return $response;
	}

	/*MANEJAR CODIGOS DE ERROR*/
	public function traducir_error($codigo){
		switch ($codigo) {
			case '10000':
				return 'El CUIT emisor no se encuentra autorizado a emitir el comprobante.';
				break;
			case '10001':
				return 'La cantidad de registros enviados debe estar comprendida entre 1 y 9998.';
				break;
			case '10002':
				return 'Las cantidades de registros enviados declaradas no coinciden.';
				break;
			case '10004':
				return 'El punto de venta debe estar comprendido entre 1 y 99998.';
				break;
			case '10005':
				return 'El punto de venta debe estar dado de alta y ser del tipo RECE.';
				break;
			case '10006':
				return 'El tipo de comprobante no es válido. Debe ser un valor numérico mayor a 0.';
				break;
			case '10007':
				return 'El tipo de comprobante debe ser:
							- 01, 02, 03, 04, 05,34,39,60, 63 para los clase A
							- 06, 07, 08, 09, 10, 35, 40,64, 61 para los clase B.
							- 11, 12, 13, 15 para los clase C.
							- 51, 52, 53, 54 para los clase M.
							- 49 para los Bienes Usados.';
				break;
			case '10008':
				return 'El número de comprobante debe estar comprendido entre 1 y 99999999.';
				break;
			case '10010':
				return 'El número de comprobante debe estar comprendido entre 1 y 99999999.';
				break;
			case '10011':
				return '"Comprobante desde:" debe ser mayor o igual a "Comprobante hasta:"';
				break;
			case '10013':
				return 'Para comprobantes tipo "A" es obligatorio escribir el CUIT del cliente.';
				break;
			case '10014':
				return 'El monto del comprobante debe ser menor a $5000.';
				break;
			case '10015':
				return 'Si el monto del comprobante excede los $5000 se deberá especificar el DNI/CUIT del cliente.';
				break;
			case '10016':
				return 'El N° de comprobante debe ser mayor al último autorizado.';
				break;
			case '10018':
				return 'Error en el envío de los importes de IVA';
				break;
			case '10013':
				return 'El CUIT emisor no se encuentra autorizado a emitir el comprobante';
				break;
			case '10013':
				return 'El CUIT emisor no se encuentra autorizado a emitir el comprobante';
				break;
			case '10013':
				return 'El CUIT emisor no se encuentra autorizado a emitir el comprobante';
				break;
			
			default:
				return 'Error interno al generar comprobante, por favor comuníquese con un administrador.';
				break;
		}
	}

}

?>